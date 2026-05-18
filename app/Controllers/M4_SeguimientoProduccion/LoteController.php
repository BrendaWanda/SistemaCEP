<?php
namespace App\Controllers\M4_SeguimientoProduccion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\LoteProduccion;
use App\Models\Producto;
use App\Models\Receta;
use App\Models\Usuario;

class LoteController extends Controller
{
    private LoteProduccion $model;
    private Producto       $modelProducto;
    private Receta         $modelReceta;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m4_seguimiento');
        $this->model         = new LoteProduccion();
        $this->modelProducto = new Producto();
        $this->modelReceta   = new Receta();
    }

    // GET /m4
    public function index(): void
    {
        $filtros = [
            'producto_id' => $this->inputInt('producto_id') ?: null,
            'estado'      => $this->input('estado'),
            'fecha_desde' => $this->input('fecha_desde'),
            'fecha_hasta' => $this->input('fecha_hasta'),
        ];

        $lotes    = $this->model->todosConDetalle($filtros);
        $stats    = $this->model->estadisticasMes();
        $productos = $this->modelProducto->paraSelect(1);

        $this->render('m4_seguimiento/index', [
            'pageTitle'  => 'Seguimiento de Producción',
            'breadcrumb' => [['label' => 'Seguimiento de Producción']],
            'lotes'      => $lotes,
            'stats'      => $stats,
            'filtros'    => $filtros,
            'productos'  => $productos,
            'estados'    => LoteProduccion::ESTADOS,
            'canWrite'   => Auth::canWrite('m4_seguimiento'),
        ]);
    }

    // GET /m4/nuevo-lote
    public function nuevoLote(): void
    {
        Auth::requireWrite('m4_seguimiento');

        $modelUsuario = new Usuario();
        $productos    = $this->modelProducto->todosConLinea();
        $fechaHoy     = date('Y-m-d');

        $this->render('m4_seguimiento/nuevo_lote', [
            'pageTitle'  => 'Abrir Nuevo Lote de Producción',
            'breadcrumb' => [
                ['label' => 'Seguimiento', 'url' => APP_URL.'/m4'],
                ['label' => 'Nuevo lote'],
            ],
            'productos'  => $productos,
            'supervisores'=> $modelUsuario->toSelectList('id','nombre'),
            'turnos'     => LoteProduccion::TURNOS,
            'fecha_hoy'  => $fechaHoy,
            'codigo_preview' => $this->model->generarCodigo($fechaHoy),
        ]);
    }

    // POST /m4/nuevo-lote
    public function crearLote(): void
    {
        Auth::requireWrite('m4_seguimiento');
        $this->verifyCsrf();

        $productoId   = $this->inputInt('producto_id');
        $recetaId     = $this->inputInt('receta_id');
        $numRecetas   = $this->inputFloat('numero_recetas');
        $fecha        = $this->input('fecha_produccion');
        $turno        = $this->input('turno');
        $supervisorId = $this->inputInt('supervisor_id') ?: Auth::id();
        $nivel        = $this->input('nivel');
        $vidaUtil     = $this->inputInt('vida_util_dias') ?: 3;

        $errores = [];
        if (!$productoId)  $errores[] = 'Seleccione el producto.';
        if (!$recetaId)    $errores[] = 'Seleccione la receta.';
        if (!$numRecetas)  $errores[] = 'Ingrese el número de recetas.';
        if (empty($fecha)) $errores[] = 'La fecha de producción es requerida.';
        if (empty($turno)) $errores[] = 'Seleccione el turno.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m4/nuevo-lote');
        }

        // Obtener datos del producto para calcular rendimiento teórico
        $producto     = $this->modelProducto->find($productoId);
        $undPorReceta = (int)($producto['unidades_por_receta'] ?? 0);
        $rendTeorico  = (int)($numRecetas * $undPorReceta);

        $codigo           = $this->model->generarCodigo($fecha);
        $fechaVencimiento = date('Y-m-d', strtotime($fecha . ' +' . $vidaUtil . ' days'));

        $loteId = $this->model->create([
            'codigo_lote'               => $codigo,
            'producto_id'               => $productoId,
            'receta_id'                 => $recetaId,
            'numero_recetas'            => $numRecetas,
            'fecha_produccion'          => $fecha,
            'turno'                     => $turno,
            'supervisor_id'             => $supervisorId,
            'nivel'                     => $nivel,
            'rendimiento_teorico_total' => $rendTeorico,
            'fecha_elaboracion'         => $fecha,
            'fecha_vencimiento'         => $fechaVencimiento,
            'estado'                    => 'en_proceso',
            'creado_por'                => Auth::id(),
        ]);

        // Registrar evento de trazabilidad
        $this->db->execute(
            "INSERT INTO trazabilidad_eventos
            (codigo_lote, tipo_evento, descripcion, tabla_origen, registro_id, usuario_id)
            VALUES (?,?,?,?,?,?)",
            [
                $codigo, 'apertura_lote',
                "Lote abierto. Producto: {$producto['nombre']}. "
                . "Recetas: {$numRecetas}. Teórico: {$rendTeorico} und.",
                'lotes_produccion', $loteId, Auth::id()
            ]
        );

        $this->redirectWithSuccess("/m4/lote/{$codigo}",
            "Lote {$codigo} abierto correctamente.");
    }

    // GET /m4/lote/:codigo
    public function ver(array $params): void
    {
        $lote = $this->model->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }

        $consumoMP = $this->model->consumoMP($lote['id']);
        $modelConsumo = new \App\Models\ConsumoMP();
        $resumenMP    = $modelConsumo->resumenPorLote($lote['id']);

        $this->render('m4_seguimiento/ver_lote', [
            'pageTitle'  => 'Lote ' . $lote['codigo_lote'],
            'breadcrumb' => [
                ['label' => 'Seguimiento', 'url' => APP_URL.'/m4'],
                ['label' => 'Lote '.$lote['codigo_lote']],
            ],
            'lote'      => $lote,
            'consumoMP' => $consumoMP,
            'resumenMP' => $resumenMP,
            'estados'   => LoteProduccion::ESTADOS,
            'canWrite'  => Auth::canWrite('m4_seguimiento'),
        ]);
    }

    // POST /m4/lote/:codigo/cerrar
    public function cerrarLote(array $params): void
    {
        Auth::requireWrite('m4_seguimiento');
        $this->verifyCsrf();

        $lote = $this->model->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }

        if ($lote['estado'] !== 'en_proceso') {
            $this->flash('error', 'Solo se pueden cerrar lotes en proceso.');
            $this->redirect("/m4/lote/{$params['codigo']}");
        }

        $this->model->update($lote['id'], ['estado' => 'cerrado']);

        $this->db->execute(
            "INSERT INTO trazabilidad_eventos
            (codigo_lote, tipo_evento, descripcion, tabla_origen, registro_id, usuario_id)
            VALUES (?,?,?,?,?,?)",
            [
                $lote['codigo_lote'], 'cierre_lote',
                "Lote cerrado manualmente. Estado anterior: en_proceso.",
                'lotes_produccion', $lote['id'], Auth::id()
            ]
        );

        $this->redirectWithSuccess("/m4/lote/{$params['codigo']}",
            "Lote {$params['codigo']} cerrado correctamente.");
    }

    // GET /m4/lote/:codigo/imprimir
    public function imprimir(array $params): void
    {
        $lote = $this->model->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }

        $consumoMP = $this->model->consumoMP($lote['id']);
        $this->renderPlain('m4_seguimiento/imprimir', [
            'lote'      => $lote,
            'consumoMP' => $consumoMP,
            'estados'   => LoteProduccion::ESTADOS,
        ]);
    }

    // API GET /api/lotes-activos
    public function activos(): void
    {
        $this->jsonSuccess($this->model->activosParaSelect());
    }
}