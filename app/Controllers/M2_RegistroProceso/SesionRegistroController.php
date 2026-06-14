<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;
use App\Models\LoteProduccion;
use App\Models\Usuario;
use App\Models\ParametroProceso;
use App\Models\RegistroValorSimple;
use App\Models\RegistroSubgrupoSpc;

class SesionRegistroController extends Controller
{
    private SesionRegistro $model;
    private LoteProduccion $modelLote;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->model     = new SesionRegistro();
        $this->modelLote = new LoteProduccion();
    }

    // GET /m2
    public function index(): void
    {
        $filtros = [
            'fecha_desde' => $this->input('fecha_desde'),
            'fecha_hasta' => $this->input('fecha_hasta'),
            'estado'      => $this->input('estado'),
        ];

        $sesiones = $this->model->todasConDetalle($filtros);

        $stats = [
            'total_hoy'    => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM sesiones_registro WHERE fecha = CURDATE()"
            ),
            'con_senales'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(DISTINCT sesion_id)
                FROM reg_subgrupos_spc
                WHERE fuera_de_control = 1
                AND sesion_id IN (
                    SELECT id FROM sesiones_registro
                    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                )"
            ),
            'liberados_hoy'=> (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM reg_liberacion_pt
                WHERE DATE(hora) = CURDATE()
                AND decision_final = 'liberado'"
            ),
            'cuarentena'   => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE estado = 'cuarentena'"
            ),
        ];

        $this->render('m2_registro_proceso/index', [
            'pageTitle'  => 'Registro de Proceso',
            'breadcrumb' => [['label' => 'Registro de Proceso']],
            'sesiones'   => $sesiones,
            'stats'      => $stats,
            'filtros'    => $filtros,
            'estados'    => SesionRegistro::ESTADOS,
            'canWrite'   => Auth::canWrite('m2_registro_proceso'),
        ]);
    }

    // GET /m2/nueva-sesion
    public function nuevaSesion(): void
    {
        Auth::requireWrite('m2_registro_proceso');

        $modelUsuario = new Usuario();
        $lotes        = $this->modelLote->activosParaSelect();

        $this->render('m2_registro_proceso/nueva_sesion', [
            'pageTitle'  => 'Nueva Sesión de Registro',
            'breadcrumb' => [
                ['label' => 'Registro Proceso', 'url' => APP_URL.'/m2'],
                ['label' => 'Nueva sesión'],
            ],
            'lotes'       => $lotes,
            'supervisores'=> $modelUsuario->toSelectList('id','nombre'),
            'turnos'      => \App\Models\LoteProduccion::TURNOS,
            'fecha_hoy'   => date('Y-m-d'),
            'hora_ahora'  => date('H:i'),
        ]);
    }

    // POST /m2/nueva-sesion
    public function crearSesion(): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $loteId       = $this->inputInt('lote_id');
        $supervisorId = $this->inputInt('supervisor_id') ?: Auth::id();
        $turno        = $this->input('turno');
        $fecha        = $this->input('fecha') ?: date('Y-m-d');
        $nivel        = $this->input('nivel');

        $errores = [];
        if (!$loteId)     $errores[] = 'Seleccione el lote de producción.';
        if (empty($turno)) $errores[] = 'Seleccione el turno.';

        if (!empty($errores)) {
            foreach ($errores as $e) $this->flash('error', $e);
            $this->redirect('/m2/nueva-sesion');
        }

        $lote = $this->modelLote->find($loteId);
        if (!$lote || $lote['estado'] !== 'en_proceso') {
            $this->flash('error', 'El lote seleccionado no está disponible para registro.');
            $this->redirect('/m2/nueva-sesion');
        }

        $sesionId = $this->model->create([
            'lote_id'              => $loteId,
            'fecha'                => $fecha,
            'turno'                => $turno,
            'supervisor_id'        => $supervisorId,
            'nivel'                => $nivel,
            'hora_inicio_registro' => date('Y-m-d H:i:s'),
            'estado'               => 'en_proceso',
        ]);

        $this->redirectWithSuccess(
            "/m2/sesion/{$sesionId}",
            'Sesión de registro creada. Complete los sub-registros.'
        );
    }

    // GET /m2/sesion/:id
    public function ver(array $params): void
    {
        $sesionId = (int)$params['id'];
        $sesion   = $this->model->conSubregistros($sesionId);
        if (!$sesion) {
            $this->flash('error', 'Sesión no encontrada.');
            $this->redirect('/m2');
        }

        // Obtener producto_id del lote
        $productoId = (int)$this->db->fetchScalar(
            "SELECT producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        // ── Parámetros configurados (M0), agrupados por etapa ────────────────
        $modelParametro   = new ParametroProceso();
        $modelValorSimple = new RegistroValorSimple();
        $modelSubgrupo    = new RegistroSubgrupoSpc();

        $parametrosPorEtapa = $modelParametro->porProductoAgrupado($productoId);

        // ── Valores ya registrados (no-SPC), agrupados por etapa ─────────────
        $valoresPorEtapa = $modelValorSimple->porSesionAgrupadoPorEtapa($sesionId);

        // ── Para cada parámetro numérico SPC: límites + subgrupos registrados ─
        $subgruposPorParametro = [];
        $limitesPorParametro   = [];
        foreach ($parametrosPorEtapa as $etapaParams) {
            foreach ($etapaParams as $p) {
                if ((int)$p['es_variable_spc'] === 1 && $p['tipo_dato'] === 'numerico') {
                    $pid = (int)$p['id'];
                    $subgruposPorParametro[$pid] = $modelSubgrupo->porSesionYParametro($sesionId, $pid);

                    $lim = $this->db->fetchOne(
                        "SELECT ucl_xbar, lcl_xbar, cl_xbar FROM spc_limites_control
                         WHERE parametro_id = ? AND vigente = 1
                         ORDER BY calculado_en DESC LIMIT 1",
                        [$pid]
                    );
                    if (!$lim) {
                        $lim = [
                            'ucl_xbar' => $p['valor_max'],
                            'lcl_xbar' => $p['valor_min'],
                            'cl_xbar'  => $p['valor_nominal'],
                        ];
                    }
                    $limitesPorParametro[$pid] = $lim;
                }
            }
        }

        // Límites generales del producto (encabezado informativo de la página)
        $limites = $this->db->fetchOne(
            "SELECT ucl_xbar, lcl_xbar, cl_xbar, ucl_r, lcl_r, cl_r
             FROM spc_limites_control
             WHERE producto_id = ? AND parametro_id IS NULL AND vigente = 1
             ORDER BY calculado_en DESC LIMIT 1",
            [$productoId]
        );

        if (!$limites) {
            $limites = [
                'ucl_xbar' => $sesion['lse_g'],
                'lcl_xbar' => $sesion['lie_g'],
                'cl_xbar'  => $sesion['peso_nominal_g'],
                'ucl_r'    => null,
                'lcl_r'    => null,
                'cl_r'     => null,
            ];
        }

        // ── Atributos SPC (carta p) — ya genérico, se mantiene ───────────────
        $parametrosAtributo = $this->db->fetchAll(
            "SELECT id, nombre, etapa, tamanio_subgrupo
             FROM parametros_proceso
             WHERE producto_id = ?
               AND es_variable_spc = 1
               AND tipo_dato IN ('seleccion','si_no')
               AND activo = 1
             ORDER BY etapa, nombre",
            [$productoId]
        );

        $inspeccionesAtributos = $this->db->fetchAll(
            "SELECT ia.*, pp.nombre AS parametro_nombre
             FROM reg_inspeccion_atributos ia
             JOIN parametros_proceso pp ON pp.id = ia.parametro_id
             WHERE ia.sesion_id = ?
             ORDER BY ia.creado_en ASC",
            [$sesionId]
        );

        $this->render('m2_registro_proceso/sesion', [
            'pageTitle'  => 'Sesión '.$sesion['codigo_lote'],
            'breadcrumb' => [
                ['label' => 'Registro Proceso', 'url' => APP_URL.'/m2'],
                ['label' => 'Sesión '.$sesion['codigo_lote']],
            ],
            'sesion'                  => $sesion,
            'limites'                 => $limites,
            'estados'                 => SesionRegistro::ESTADOS,
            'canWrite'                => Auth::canWrite('m2_registro_proceso')
                                            && $sesion['estado'] === 'en_proceso',
            'etapas'                  => ParametroProceso::ETAPAS,
            'parametros_por_etapa'    => $parametrosPorEtapa,
            'valores_por_etapa'       => $valoresPorEtapa,
            'subgrupos_por_parametro' => $subgruposPorParametro,
            'limites_por_parametro'   => $limitesPorParametro,
            'parametros_atributo'     => $parametrosAtributo,
            'inspecciones_atributos'  => $inspeccionesAtributos,
        ]);
    }

    // GET /m2/sesion/:id/imprimir
    public function imprimir(array $params): void
    {
        $sesion = $this->model->conSubregistros((int)$params['id']);
        if (!$sesion) {
            $this->flash('error', 'Sesión no encontrada.');
            $this->redirect('/m2');
        }
        $this->renderPlain('m2_registro_proceso/imprimir', [
            'sesion'  => $sesion,
            'confOpc' => \App\Models\LiberacionPT::OPCIONES_CONF,
        ]);
    }
}