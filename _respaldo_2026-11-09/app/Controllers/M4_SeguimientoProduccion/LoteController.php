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
            'supervisores'=> $modelUsuario->toSelectList('id','nombre','activo = 1'),
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
        $vidaUtilInput = $this->inputInt('vida_util_dias') ?: null;
        $observaciones = $this->input('observaciones');

        $errores = [];
        if (!$productoId)  $errores[] = 'Seleccione el producto.';
        if (!$recetaId)    $errores[] = 'Seleccione la receta.';
        if (!$numRecetas)  $errores[] = 'Ingrese el número de recetas.';
        if (empty($fecha)) $errores[] = 'La fecha de producción es requerida.';
        if (empty($turno)) $errores[] = 'Seleccione el turno.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m4/nuevo-lote');
            return;
        }

        // Obtener datos del producto para calcular rendimiento teórico
        $producto = $this->modelProducto->find($productoId);
        if (!$producto) {
            $this->flash('error', 'Producto no encontrado.');
            $this->redirect('/m4/nuevo-lote');
            return;
        }

        // Hallazgo de auditoría: nada validaba que la receta elegida
        // realmente perteneciera al producto elegido — se confiaba solo
        // en que el JS del formulario filtrara las opciones. Si esa carga
        // fallaba o alguien mandaba el POST directo, se podía abrir un
        // lote con receta y producto que no coinciden.
        $receta = $this->modelReceta->find($recetaId);
        if (!$receta || (int)$receta['producto_id'] !== $productoId) {
            $this->flash('error', 'La receta seleccionada no corresponde a este producto.');
            $this->redirect('/m4/nuevo-lote');
            return;
        }

        $undPorReceta = (int)($producto['unidades_por_receta'] ?? 0);
        $rendTeorico  = (int)($numRecetas * $undPorReceta);

        // Hallazgo de auditoría: antes caía a un "3" fijo sin importar el
        // producto — ahora usa la vida útil configurada en M0 para ESE
        // producto si el formulario no manda un valor explícito.
        $vidaUtil         = $vidaUtilInput ?? ((int)($producto['vida_util_dias'] ?? 0) ?: 3);
        $fechaVencimiento = date('Y-m-d', strtotime($fecha . ' +' . $vidaUtil . ' days'));

        // Hallazgo de auditoría: generarCodigo() + create() por separado
        // tenía una condición de carrera real (ver LoteProduccion.php) —
        // se usa el método con reintento ante colisión de codigo_lote.
        $resultado = $this->model->crearConCodigoSeguro($fecha, [
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
            'observaciones'             => $observaciones,
            'creado_por'                => Auth::id(),
        ]);
        $loteId = $resultado['id'];
        $codigo = $resultado['codigo'];

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

        // Hallazgo de auditoría: se consultaba consumoMP() completo (con
        // todo el detalle por registro) en CADA visita a la ficha del
        // lote, pero ver_lote.php nunca lo usaba — solo usa el resumen
        // agrupado por insumo. Se quita la consulta innecesaria.
        $modelConsumo = new \App\Models\ConsumoMP();
        $resumenMP    = $modelConsumo->resumenPorLote($lote['id']);

        $this->render('m4_seguimiento/ver_lote', [
            'pageTitle'  => 'Lote ' . $lote['codigo_lote'],
            'breadcrumb' => [
                ['label' => 'Seguimiento', 'url' => APP_URL.'/m4'],
                ['label' => 'Lote '.$lote['codigo_lote']],
            ],
            'lote'      => $lote,
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
            return;
        }

        // Hallazgo CRÍTICO de auditoría: una vez 'cerrado', un lote nunca
        // vuelve a 'en_proceso' — no existe ningún método de reapertura
        // en todo M4. Y RendimientoController::guardar() BLOQUEA la
        // edición si el estado no es 'en_proceso'. Esto significa que
        // cerrar un lote sin haber registrado su rendimiento real lo deja
        // PERMANENTEMENTE sin esos datos, sin ninguna forma de corregirlo
        // — justo el dato central de tu análisis de rendimiento/mermas.
        // Se exige una confirmación explícita en ese caso específico,
        // igual que ya hicimos en M6 con "guardar límites de un proceso
        // inestable".
        if ($lote['rendimiento_real_total'] === null && !$this->input('confirmar_sin_rendimiento')) {
            if ($this->isAjax()) {
                $this->jsonError(
                    'Este lote no tiene rendimiento registrado todavía. Una vez cerrado, '
                    . 'no habrá forma de registrarlo después. ¿Confirma cerrar de todas formas?',
                    409,
                    ['requiere_confirmacion' => true]
                );
                return;
            }
            $this->flash('error',
                'Este lote no tiene rendimiento registrado. Regístrelo antes de cerrar, '
                . 'o confirme el cierre sabiendo que no podrá agregarlo después.');
            $this->redirect("/m4/lote/{$params['codigo']}");
            return;
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