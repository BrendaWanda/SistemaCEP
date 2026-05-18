<?php
namespace App\Controllers\M3_Mantenimiento;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Usuario;

class MantenimientoController extends Controller
{
    private Mantenimiento $model;
    private Equipo        $modelEquipo;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m3_mantenimiento');
        $this->model       = new Mantenimiento();
        $this->modelEquipo = new Equipo();
    }

    public function index(): void
    {
        $filtros = [
            'equipo_id'  => $this->inputInt('equipo_id') ?: null,
            'tipo'       => $this->input('tipo'),
            'estado'     => $this->input('estado'),
            'fecha_desde'=> $this->input('fecha_desde'),
            'fecha_hasta'=> $this->input('fecha_hasta'),
        ];

        $mantenimientos = $this->model->todosConEquipo($filtros);
        $stats          = $this->model->estadisticasMes();
        $alertas        = $this->model->alertas(15);

        $this->render('m3_mantenimiento/index', [
            'pageTitle'      => 'Mantenimiento',
            'breadcrumb'     => [['label' => 'Mantenimiento']],
            'mantenimientos' => $mantenimientos,
            'stats'          => $stats,
            'alertas'        => $alertas,
            'filtros'        => $filtros,
            'tipos'          => Mantenimiento::TIPOS,
            'estados'        => Mantenimiento::RESULTADOS,
            'canWrite'       => Auth::canWrite('m3_mantenimiento'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::requireWrite('m3_mantenimiento');
        $modelUsuario = new Usuario();
        $this->render('m3_mantenimiento/form', [
            'pageTitle'     => 'Nuevo Mantenimiento',
            'breadcrumb'    => [
                ['label'=>'Mantenimiento','url'=>APP_URL.'/m3'],
                ['label'=>'Nuevo'],
            ],
            'mantenimiento' => null,
            'equipos'       => $this->modelEquipo->todosConEstado(),
            'usuarios'      => $modelUsuario->toSelectList('id','nombre'),
            'tipos'         => Mantenimiento::TIPOS,
            'resultados'    => Mantenimiento::RESULTADOS,
            'fecha_hoy'     => date('Y-m-d'),
            'accion'        => 'crear',
        ]);
    }

    public function guardar(): void
    {
        Auth::requireWrite('m3_mantenimiento');
        $this->verifyCsrf();

        $equipoId = $this->inputInt('equipo_id');
        $tipo     = $this->input('tipo');

        $errores = [];
        if (!$equipoId)   $errores[] = 'Seleccione el equipo.';
        if (empty($tipo)) $errores[] = 'Seleccione el tipo.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m3/nuevo');
        }

        $fechaInicio = $this->input('fecha_inicio') ?: null;
        $fechaFin    = $this->input('fecha_fin') ?: null;
        $duracion    = null;

        if ($fechaInicio && $fechaFin) {
            $ini = strtotime($fechaInicio);
            $fin = strtotime($fechaFin);
            if ($fin > $ini) {
                $duracion = (int)(($fin - $ini) / 60);
            }
        }

        $resultado = $this->input('resultado') ?: 'completado';

        $id = $this->model->create([
            'equipo_id'            => $equipoId,
            'tipo'                 => $tipo,
            'fecha_programada'     => $this->input('fecha_programada') ?: null,
            'fecha_inicio'         => $fechaInicio,
            'fecha_fin'            => $fechaFin,
            'duracion_min'         => $duracion
                                        ?: ($this->inputInt('duracion_min') ?: null),
            'descripcion_trabajo'  => $this->input('descripcion_trabajo'),
            'falla_detectada'      => $this->input('falla_detectada'),
            'causa_raiz'           => $this->input('causa_raiz'),
            'accion_correctiva'    => $this->input('accion_correctiva'),
            'paro_produccion'      => isset($_POST['paro_produccion']) ? 1 : 0,
            'tiempo_paro_min'      => $this->inputInt('tiempo_paro_min') ?: null,
            'lote_afectado_id'     => $this->inputInt('lote_afectado_id') ?: null,
            'resultado'            => $resultado,
            'componentes_cambiados'=> $this->input('componentes_cambiados'),
            'costo_estimado'       => $this->inputFloat('costo_estimado') ?: null,
            'ejecutado_por_id'     => $this->inputInt('ejecutado_por_id') ?: Auth::id(),
            'supervisado_por_id'   => $this->inputInt('supervisado_por_id') ?: null,
            'observaciones'        => $this->input('observaciones'),
        ]);

        // Actualizar fechas del equipo si está completado
        if ($resultado === 'completado' && $fechaInicio) {
            $fechaDate = substr($fechaInicio, 0, 10);
            if ($tipo === 'calibracion') {
                $this->modelEquipo->actualizarFechaProxCalibr($equipoId, $fechaDate);
            } else {
                $this->modelEquipo->actualizarFechaProxMant($equipoId, $fechaDate);
            }
        }

        $this->redirectWithSuccess("/m3/{$id}",
            'Mantenimiento registrado correctamente.');
    }

    public function ver(array $params): void
    {
        $mant = $this->model->conDetalle((int)$params['id']);
        if (!$mant) {
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect('/m3');
        }
        $this->render('m3_mantenimiento/ver', [
            'pageTitle'  => 'Mantenimiento — '.$mant['equipo_nombre'],
            'breadcrumb' => [
                ['label'=>'Mantenimiento','url'=>APP_URL.'/m3'],
                ['label'=>$mant['equipo_codigo']],
            ],
            'mant'      => $mant,
            'tipos'     => Mantenimiento::TIPOS,
            'estados'   => Mantenimiento::RESULTADOS,
            'canWrite'  => Auth::canWrite('m3_mantenimiento'),
        ]);
    }

    public function editar(array $params): void
    {
        Auth::requireWrite('m3_mantenimiento');
        $mant = $this->model->conDetalle((int)$params['id']);
        if (!$mant) {
            $this->flash('error', 'Registro no encontrado.');
            $this->redirect('/m3');
        }
        $modelUsuario = new Usuario();
        $this->render('m3_mantenimiento/form', [
            'pageTitle'     => 'Editar Mantenimiento',
            'breadcrumb'    => [
                ['label'=>'Mantenimiento','url'=>APP_URL.'/m3'],
                ['label'=>'Editar'],
            ],
            'mantenimiento' => $mant,
            'equipos'       => $this->modelEquipo->todosConEstado(),
            'usuarios'      => $modelUsuario->toSelectList('id','nombre'),
            'tipos'         => Mantenimiento::TIPOS,
            'resultados'    => Mantenimiento::RESULTADOS,
            'fecha_hoy'     => date('Y-m-d'),
            'accion'        => 'editar',
        ]);
    }

    public function actualizar(array $params): void
    {
        Auth::requireWrite('m3_mantenimiento');
        $this->verifyCsrf();
        $id = (int)$params['id'];

        $fechaInicio = $this->input('fecha_inicio') ?: null;
        $fechaFin    = $this->input('fecha_fin') ?: null;
        $duracion    = null;

        if ($fechaInicio && $fechaFin) {
            $ini = strtotime($fechaInicio);
            $fin = strtotime($fechaFin);
            if ($fin > $ini) $duracion = (int)(($fin - $ini) / 60);
        }

        $resultado = $this->input('resultado') ?: 'completado';
        $tipo      = $this->input('tipo');
        $equipoId  = $this->inputInt('equipo_id');

        $this->model->update($id, [
            'equipo_id'            => $equipoId,
            'tipo'                 => $tipo,
            'fecha_programada'     => $this->input('fecha_programada') ?: null,
            'fecha_inicio'         => $fechaInicio,
            'fecha_fin'            => $fechaFin,
            'duracion_min'         => $duracion
                                      ?: ($this->inputInt('duracion_min') ?: null),
            'descripcion_trabajo'  => $this->input('descripcion_trabajo'),
            'falla_detectada'      => $this->input('falla_detectada'),
            'causa_raiz'           => $this->input('causa_raiz'),
            'accion_correctiva'    => $this->input('accion_correctiva'),
            'paro_produccion'      => isset($_POST['paro_produccion']) ? 1 : 0,
            'tiempo_paro_min'      => $this->inputInt('tiempo_paro_min') ?: null,
            'resultado'            => $resultado,
            'componentes_cambiados'=> $this->input('componentes_cambiados'),
            'costo_estimado'       => $this->inputFloat('costo_estimado') ?: null,
            'ejecutado_por_id'     => $this->inputInt('ejecutado_por_id') ?: Auth::id(),
            'supervisado_por_id'   => $this->inputInt('supervisado_por_id') ?: null,
            'observaciones'        => $this->input('observaciones'),
        ]);

        if ($resultado === 'completado' && $fechaInicio) {
            $fechaDate = substr($fechaInicio, 0, 10);
            if ($tipo === 'calibracion') {
                $this->modelEquipo->actualizarFechaProxCalibr($equipoId, $fechaDate);
            } else {
                $this->modelEquipo->actualizarFechaProxMant($equipoId, $fechaDate);
            }
        }

        $this->redirectWithSuccess("/m3/{$id}", 'Mantenimiento actualizado.');
    }

    public function alertas(): void
    {
        $alertas = $this->model->alertas(30);
        $this->render('m3_mantenimiento/alertas', [
            'pageTitle'  => 'Alertas de Mantenimiento',
            'breadcrumb' => [
                ['label'=>'Mantenimiento','url'=>APP_URL.'/m3'],
                ['label'=>'Alertas'],
            ],
            'alertas'  => $alertas,
            'canWrite' => Auth::canWrite('m3_mantenimiento'),
        ]);
    }

    public function calendario(): void
    {
        $programados = $this->db->fetchAll(
            "SELECT m.*, e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre
                FROM mantenimientos m
                JOIN equipos e ON e.id = m.equipo_id
                WHERE m.resultado IN ('pendiente','en_proceso')
                ORDER BY m.fecha_programada ASC"
        );
        $this->render('m3_mantenimiento/calendario', [
            'pageTitle'   => 'Calendario de Mantenimientos',
            'breadcrumb'  => [
                ['label'=>'Mantenimiento','url'=>APP_URL.'/m3'],
                ['label'=>'Calendario'],
            ],
            'programados' => $programados,
            'tipos'       => Mantenimiento::TIPOS,
            'canWrite'    => Auth::canWrite('m3_mantenimiento'),
        ]);
    }
}