<?php
namespace App\Controllers\M1_RecepcionMP;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\RecepcionMP;
use App\Models\StockMP;
use App\Models\Insumo;
use App\Models\Proveedor;
use App\Models\Usuario;

class RecepcionController extends Controller
{
    private RecepcionMP $model;
    private StockMP     $modelStock;
    private Insumo      $modelInsumo;
    private Proveedor   $modelProveedor;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m1_recepcion_mp');
        $this->model          = new RecepcionMP();
        $this->modelStock     = new StockMP();
        $this->modelInsumo    = new Insumo();
        $this->modelProveedor = new Proveedor();
    }

    // GET /m1
    public function index(): void
    {
        // Últimas 10 recepciones + estadísticas rápidas
        $ultimas = $this->db->fetchAll(
            "SELECT r.*, i.descripcion AS insumo_nombre,
                    i.codigo AS insumo_codigo,
                    p.nombre AS proveedor_nombre
             FROM recepciones_mp r
             JOIN insumos i ON i.id = r.insumo_id
             JOIN proveedores p ON p.id = r.proveedor_id
             ORDER BY r.creado_en DESC LIMIT 10"
        );

        $stats = [
            'total_mes'   => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM recepciones_mp
                 WHERE MONTH(fecha_analisis) = MONTH(CURDATE())
                   AND YEAR(fecha_analisis) = YEAR(CURDATE())"
            ),
            'aprobadas'   => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM recepciones_mp
                 WHERE decision = 'aprobado'
                   AND MONTH(fecha_analisis) = MONTH(CURDATE())"
            ),
            'rechazadas'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM recepciones_mp
                 WHERE decision = 'rechazado'
                   AND MONTH(fecha_analisis) = MONTH(CURDATE())"
            ),
            'por_vencer'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM stock_mp
                 WHERE estado = 'disponible'
                   AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
            ),
        ];

        $alertas = $this->model->alertasVencimiento(30);

        $this->render('m1_recepcion_mp/index', [
            'pageTitle'  => 'Recepción de Materia Prima',
            'breadcrumb' => [['label' => 'Recepción MP']],
            'ultimas'    => $ultimas,
            'stats'      => $stats,
            'alertas'    => $alertas,
            'decisiones' => RecepcionMP::DECISIONES,
            'canWrite'   => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    // GET /m1/nueva
    public function nueva(): void
    {
        Auth::requireWrite('m1_recepcion_mp');

        $modelUsuario = new Usuario();
        $this->render('m1_recepcion_mp/nueva', [
            'pageTitle'  => 'Nueva Recepción de MP',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Nueva recepción'],
            ],
            'insumos'       => $this->modelInsumo->paraSelect(),
            'proveedores'   => $this->modelProveedor->paraSelect(),
            'usuarios'      => $modelUsuario->toSelectList('id', 'nombre'),
            'codigo'        => $this->model->generarCodigo(),
            'fecha_hoy'     => date('Y-m-d'),
            'conf_opciones' => RecepcionMP::CONF,
            'decisiones'    => RecepcionMP::DECISIONES,
            'canWrite'      => true,
        ]);
    }

    // POST /m1/nueva
    public function guardar(): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $this->verifyCsrf();

        $decision = $this->input('decision');
        $errores  = [];

        if (!$this->inputInt('insumo_id'))    $errores[] = 'Seleccione el insumo.';
        if (!$this->inputInt('proveedor_id')) $errores[] = 'Seleccione el proveedor.';
        if (empty($this->input('lote_proveedor'))) $errores[] = 'El lote del proveedor es requerido.';
        if (empty($this->input('fecha_vencimiento'))) $errores[] = 'La fecha de vencimiento es requerida.';
        if (empty($decision)) $errores[] = 'La decisión es requerida.';
        // Hallazgo de auditoría: cantidad_recibida y unidad_medida son
        // NOT NULL en la BD pero solo se validaban con "required" del
        // HTML — sin respaldo del servidor.
        if (!$this->inputFloat('cantidad_recibida')) $errores[] = 'La cantidad recibida es requerida.';
        if (empty($this->input('unidad_medida'))) $errores[] = 'La unidad de medida es requerida.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m1/nueva');
        }

        // Cantidad que ingresa al stock (como disponible o reservado
        // según la decisión — ver más abajo en la transacción).
        //
        // Hallazgo de auditoría: antes SOLO "aprobado" generaba stock;
        // "cuarentena" y "observado" no dejaban ningún registro físico
        // del insumo, aunque la mercadería sí existe en planta esperando
        // resolución. Ahora también se reserva stock para esos 2 casos
        // (estado='reservado', que ya existía en el enum pero nunca se
        // usaba), y se libera o rechaza cuando se resuelve la decisión
        // (ver resolverDecision() en el modelo).
        $cantRecibida  = $this->inputFloat('cantidad_recibida');
        $requiereStock = in_array($decision, ['aprobado', 'cuarentena', 'observado'], true);
        $stockDisp     = $requiereStock
            ? ($this->inputFloat('stock_disponible_kg') ?: $cantRecibida)
            : 0;

        $data = [
            'insumo_id'              => $this->inputInt('insumo_id'),
            'proveedor_id'           => $this->inputInt('proveedor_id'),
            'codigo_recepcion'       => $this->input('codigo_recepcion')
                                        ?: $this->model->generarCodigo(),
            'lote_proveedor'         => $this->input('lote_proveedor'),
            'fecha_fabricacion'      => $this->input('fecha_fabricacion') ?: null,
            'fecha_vencimiento'      => $this->input('fecha_vencimiento'),
            'fecha_muestreo'         => $this->input('fecha_muestreo') ?: date('Y-m-d'),
            'fecha_analisis'         => $this->input('fecha_analisis') ?: date('Y-m-d'),
            'cantidad_recibida'      => $cantRecibida,
            'unidad_medida'          => $this->input('unidad_medida'),
            'tamanio_muestra'        => $this->inputFloat('tamanio_muestra') ?: null,
            'nro_registro_sanitario' => $this->input('nro_registro_sanitario'),
            'presentacion'           => $this->input('presentacion'),
            'envase_primario'        => $this->input('envase_primario'),
            'envase_secundario'      => $this->input('envase_secundario'),
            // Organoléptico
            'sabor_olor'             => $this->input('sabor_olor'),
            'conf_sabor_olor'        => $this->input('conf_sabor_olor') ?: null,
            'color'                  => $this->input('color'),
            'conf_color'             => $this->input('conf_color') ?: null,
            'descripcion_fisica'     => $this->input('descripcion_fisica'),
            'conf_descripcion_fisica'=> $this->input('conf_descripcion_fisica') ?: null,
            'obs_organoleptico'      => $this->input('obs_organoleptico'),
            // Fisicoquímico
            'esp_humedad'            => $this->input('esp_humedad'),
            'res_humedad'            => $this->inputFloat('res_humedad') ?: null,
            'conf_humedad'           => $this->input('conf_humedad') ?: null,
            'esp_ph'                 => $this->input('esp_ph'),
            'res_ph'                 => $this->inputFloat('res_ph') ?: null,
            'conf_ph'                => $this->input('conf_ph') ?: null,
            'esp_densidad'           => $this->input('esp_densidad'),
            'res_densidad'           => $this->inputFloat('res_densidad') ?: null,
            'conf_densidad'          => $this->input('conf_densidad') ?: null,
            'esp_gluten'             => $this->input('esp_gluten'),
            'res_gluten'             => $this->inputFloat('res_gluten') ?: null,
            'conf_gluten'            => $this->input('conf_gluten') ?: null,
            'esp_brix'               => $this->input('esp_brix'),
            'res_brix'               => $this->inputFloat('res_brix') ?: null,
            'conf_brix'              => $this->input('conf_brix') ?: null,
            'esp_actividad_agua'     => $this->input('esp_actividad_agua'),
            'res_actividad_agua'     => $this->inputFloat('res_actividad_agua') ?: null,
            'conf_actividad_agua'    => $this->input('conf_actividad_agua') ?: null,
            'obs_fisicoquimico'      => $this->input('obs_fisicoquimico'),
            // Impurezas
            'esp_impurezas'          => $this->input('esp_impurezas'),
            'res_impurezas'          => $this->inputFloat('res_impurezas') ?: null,
            'conf_impurezas'         => $this->input('conf_impurezas') ?: null,
            'obs_impurezas'          => $this->input('obs_impurezas'),
            // Conclusión
            'conclusion'             => $this->input('conclusion'),
            'decision'               => $decision,
            'fecha_decision'         => date('Y-m-d'),
            'responsable_muestreo_id'=> $this->inputInt('responsable_muestreo_id')
                                        ?: Auth::id(),
            'responsable_analisis_id'=> $this->inputInt('responsable_analisis_id')
                                        ?: Auth::id(),
            'visto_bueno_id'         => $this->inputInt('visto_bueno_id') ?: null,
            'obs_generales'          => $this->input('obs_generales'),
            'stock_disponible_kg'    => $stockDisp,
        ];

        // Usar transacción para guardar recepción y stock juntos
        $recepcionId = $this->db->transaction(function($db) use ($data, $decision) {
            // Insertar recepción
            $db->execute(
                "INSERT INTO recepciones_mp (" . implode(',', array_keys($data)) . ")
                 VALUES (" . implode(',', array_fill(0, count($data), '?')) . ")",
                array_values($data)
            );
            $id = (int)$db->lastInsertId();

            // Si fue aprobado, crear stock disponible de inmediato.
            // Si quedó pendiente (cuarentena/observado), se reserva el
            // stock físico — existe en planta pero no se puede usar
            // hasta que se resuelva la decisión.
            $estadoStock = match ($decision) {
                'aprobado' => 'disponible',
                'cuarentena', 'observado' => 'reservado',
                default => null,
            };
            if ($estadoStock !== null && $data['stock_disponible_kg'] > 0) {
                $db->execute(
                    "INSERT INTO stock_mp
                     (insumo_id, recepcion_id, lote_proveedor, fecha_vencimiento,
                      cantidad_inicial, cantidad_disponible, unidad_medida, estado)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $data['insumo_id'],
                        $id,
                        $data['lote_proveedor'],
                        $data['fecha_vencimiento'],
                        $data['stock_disponible_kg'],
                        $data['stock_disponible_kg'],
                        $data['unidad_medida'],
                        $estadoStock,
                    ]
                );
            }
            return $id;
        });

        $this->redirectWithSuccess("/m1/{$recepcionId}",
            "Recepción {$data['codigo_recepcion']} registrada correctamente.");
    }

    // GET /m1/:id
    public function ver(array $params): void
    {
        $recepcion = $this->model->conDetalle((int)$params['id']);
        if (!$recepcion) {
            $this->flash('error', 'Recepción no encontrada.');
            $this->redirect('/m1');
            return;
        }
        $this->render('m1_recepcion_mp/ver', [
            'pageTitle'  => 'Recepción '.$recepcion['codigo_recepcion'],
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => $recepcion['codigo_recepcion']],
            ],
            'recepcion'  => $recepcion,
            'decisiones' => RecepcionMP::DECISIONES,
            'conf'       => RecepcionMP::CONF,
            'canWrite'   => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    // GET /m1/:id/resolver — solo si la recepción está en cuarentena/observado
    public function resolverForm(array $params): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $recepcion = $this->model->conDetalle((int)$params['id']);
        if (!$recepcion) {
            $this->flash('error', 'Recepción no encontrada.');
            $this->redirect('/m1');
            return;
        }
        if (!in_array($recepcion['decision'], ['cuarentena', 'observado'], true)) {
            $this->flash('error', 'Esta recepción ya tiene una decisión final, no se puede resolver de nuevo.');
            $this->redirect("/m1/{$recepcion['id']}");
            return;
        }
        $this->render('m1_recepcion_mp/resolver', [
            'pageTitle'  => 'Resolver Recepción '.$recepcion['codigo_recepcion'],
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => $recepcion['codigo_recepcion'], 'url' => APP_URL.'/m1/'.$recepcion['id']],
                ['label' => 'Resolver'],
            ],
            'recepcion' => $recepcion,
        ]);
    }

    // POST /m1/:id/resolver
    public function resolverGuardar(array $params): void
    {
        Auth::requireWrite('m1_recepcion_mp');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $nuevaDecision = $this->input('nueva_decision');
        $nota          = $this->input('nota_resolucion');

        if (!in_array($nuevaDecision, ['aprobado', 'rechazado'], true)) {
            $this->flash('error', 'Seleccione la resolución final (aprobado o rechazado).');
            $this->redirect("/m1/{$id}/resolver");
            return;
        }
        if (empty($nota)) {
            $this->flash('error', 'Indique el motivo/nota de la resolución.');
            $this->redirect("/m1/{$id}/resolver");
            return;
        }

        $ok = $this->model->resolverDecision($id, $nuevaDecision, $nota, Auth::id());
        if (!$ok) {
            $this->flash('error', 'No se pudo resolver — verifique que la recepción siga pendiente.');
            $this->redirect("/m1/{$id}");
            return;
        }

        $this->redirectWithSuccess("/m1/{$id}",
            'Recepción resuelta como "' . strtoupper($nuevaDecision) . '" correctamente.');
    }

    // GET /m1/:id/imprimir
    public function imprimir(array $params): void
    {
        $recepcion = $this->model->conDetalle((int)$params['id']);
        if (!$recepcion) {
            $this->flash('error', 'Recepción no encontrada.');
            $this->redirect('/m1');
        }
        $this->renderPlain('m1_recepcion_mp/imprimir', [
            'recepcion'  => $recepcion,
            'decisiones' => RecepcionMP::DECISIONES,
            'conf'       => RecepcionMP::CONF,
        ]);
    }

    // GET /m1/historial
    public function historial(): void
    {
        $filtros = [
            'insumo_id'    => $this->inputInt('insumo_id') ?: null,
            'proveedor_id' => $this->inputInt('proveedor_id') ?: null,
            'decision'     => $this->input('decision'),
            'fecha_desde'  => $this->input('fecha_desde'),
            'fecha_hasta'  => $this->input('fecha_hasta'),
        ];
        $pagina    = max(1, $this->inputInt('pagina', 1));
        $resultado = $this->model->historial($filtros, $pagina);

        $this->render('m1_recepcion_mp/historial', [
            'pageTitle'   => 'Historial de Recepciones',
            'breadcrumb'  => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Historial'],
            ],
            'resultado'   => $resultado,
            'filtros'     => $filtros,
            'insumos'     => $this->modelInsumo->paraSelect(),
            'proveedores' => $this->modelProveedor->paraSelect(),
            'decisiones'  => RecepcionMP::DECISIONES,
            'canWrite'    => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    // GET /m1/stock
    public function stock(): void
    {
        $stock = $this->model->stockDisponible();
        $this->render('m1_recepcion_mp/stock', [
            'pageTitle'  => 'Stock de Materia Prima',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Stock disponible'],
            ],
            'stock'    => $stock,
            'canWrite' => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    // GET /m1/alertas-vencimiento
    public function alertasVencimiento(): void
    {
        $alertas = $this->model->alertasVencimiento(60);
        $this->render('m1_recepcion_mp/alertas', [
            'pageTitle'  => 'Alertas de Vencimiento',
            'breadcrumb' => [
                ['label' => 'Recepción MP', 'url' => APP_URL.'/m1'],
                ['label' => 'Alertas de vencimiento'],
            ],
            'alertas'  => $alertas,
            'canWrite' => Auth::canWrite('m1_recepcion_mp'),
        ]);
    }

    // API GET /api/stock-disponible?insumo_id=1
    public function stockDisponible(): void
    {
        $insumoId = $this->inputInt('insumo_id');
        $this->jsonSuccess($this->modelStock->paraSelect($insumoId));
    }
}