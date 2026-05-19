<?php
namespace App\Controllers\Reportes;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\SpcService;
use App\Services\OeeService;

class ReporteController extends Controller
{
    private SpcService $spc;
    private OeeService $oee;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m7_dashboard');
        $this->spc = new SpcService();
        $this->oee = new OeeService();
    }

    // GET /reportes
    public function index(): void
    {
        $this->render('reportes/index', [
            'pageTitle'  => 'Reportes',
            'breadcrumb' => [['label' => 'Reportes']],
        ]);
    }

    // POST /reportes/recepcion-mp
    public function recepcionMP(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');

        $datos = $this->db->fetchAll(
            "SELECT r.*,
                    i.descripcion AS insumo_nombre,
                    i.codigo AS insumo_codigo,
                    p.nombre AS proveedor_nombre,
                    u1.nombre AS muestreo_nombre,
                    u2.nombre AS analisis_nombre
            FROM recepciones_mp r
            JOIN insumos i ON i.id = r.insumo_id
            JOIN proveedores p ON p.id = r.proveedor_id
            JOIN usuarios u1 ON u1.id = r.responsable_muestreo_id
            JOIN usuarios u2 ON u2.id = r.responsable_analisis_id
            WHERE r.fecha_analisis BETWEEN ? AND ?
            ORDER BY r.fecha_analisis DESC",
            [$fechaDesde, $fechaHasta]
        );

        $stats = [
            'total'      => count($datos),
            'aprobadas'  => count(array_filter($datos, fn($r) => $r['decision']==='aprobado')),
            'rechazadas' => count(array_filter($datos, fn($r) => $r['decision']==='rechazado')),
            'observadas' => count(array_filter($datos, fn($r) => $r['decision']==='observado')),
        ];

        $this->renderPlain('reportes/recepcion_mp', [
            'datos'      => $datos,
            'stats'      => $stats,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    // POST /reportes/seguimiento-produccion
    public function seguimientoProduccion(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');
        $productoId = $this->inputInt('producto_id') ?: null;

        $where  = ['l.fecha_produccion BETWEEN ? AND ?'];
        $params = [$fechaDesde, $fechaHasta];

        if ($productoId) {
            $where[]  = 'l.producto_id = ?';
            $params[] = $productoId;
        }

        $datos = $this->db->fetchAll(
            "SELECT l.*,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    p.peso_nominal_g,
                    r.nombre AS receta_nombre,
                    u.nombre AS supervisor_nombre,
                    u.apellidos AS supervisor_apellidos
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            JOIN recetas r   ON r.id = l.receta_id
            JOIN usuarios u  ON u.id = l.supervisor_id
            WHERE ".implode(' AND ', $where)."
            ORDER BY l.fecha_produccion DESC",
            $params
        );

        $stats = [
            'total_lotes'    => count($datos),
            'und_reales'     => array_sum(array_column($datos,'rendimiento_real_total')),
            'und_teoricas'   => array_sum(array_column($datos,'rendimiento_teorico_total')),
            'rend_promedio'  => count($datos) > 0
                ? array_sum(array_column($datos,'porcentaje_rendimiento')) / count($datos)
                : 0,
            'liberados'      => count(array_filter($datos, fn($l) => $l['estado']==='liberado')),
            'cuarentena'     => count(array_filter($datos, fn($l) => $l['estado']==='cuarentena')),
        ];

        $this->renderPlain('reportes/seguimiento_produccion', [
            'datos'      => $datos,
            'stats'      => $stats,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    // POST /reportes/liberacion-lote
    public function liberacionLote(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');

        $datos = $this->db->fetchAll(
            "SELECT lib.*,
                    l.codigo_lote,
                    l.fecha_produccion,
                    l.rendimiento_real_total,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    u1.nombre AS supervisor_cal_nombre,
                    u1.apellidos AS supervisor_cal_apellidos
            FROM reg_liberacion_pt lib
            JOIN sesiones_registro s ON s.id = lib.sesion_id
            JOIN lotes_produccion l  ON l.id = s.lote_id
            JOIN productos p         ON p.id = l.producto_id
            JOIN usuarios u1         ON u1.id = lib.supervisor_calidad_id
            WHERE l.fecha_produccion BETWEEN ? AND ?
            ORDER BY l.fecha_produccion DESC",
            [$fechaDesde, $fechaHasta]
        );

        $stats = [
            'total'      => count($datos),
            'liberados'  => count(array_filter($datos, fn($d) => $d['decision_final']==='liberado')),
            'cuarentena' => count(array_filter($datos, fn($d) => $d['decision_final']==='cuarentena')),
        ];

        $this->renderPlain('reportes/liberacion_lote', [
            'datos'      => $datos,
            'stats'      => $stats,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    // POST /reportes/spc-xbar
    public function spcXbar(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');
        $productoId = $this->inputInt('producto_id');

        $subgrupos = $this->db->fetchAll(
            "SELECT p.*, s.fecha, l.codigo_lote, l.producto_id,
                    prod.nombre AS producto_nombre,
                    prod.lse_g, prod.lie_g, prod.peso_nominal_g
            FROM reg_pesos_masa_cruda p
            JOIN sesiones_registro s ON s.id = p.sesion_id
            JOIN lotes_produccion l  ON l.id = s.lote_id
            JOIN productos prod      ON prod.id = l.producto_id
            WHERE s.fecha BETWEEN ? AND ?
                AND p.promedio_xbar IS NOT NULL
                ".($productoId ? 'AND l.producto_id = ?' : '')."
            ORDER BY s.fecha ASC, p.hora ASC",
            $productoId ? [$fechaDesde, $fechaHasta, $productoId]
                        : [$fechaDesde, $fechaHasta]
        );

        $limites   = $this->spc->calcularLimitesXbarR($subgrupos);
        $producto  = $productoId ? $this->db->fetchOne(
            "SELECT * FROM productos WHERE id = ?", [$productoId]
        ) : null;
        $capacidad = ($producto && $limites['sigma_est'])
            ? $this->spc->calcularCapacidad(
                $subgrupos,
                (float)$producto['lse_g'],
                (float)$producto['lie_g']
            ) : [];

        $this->renderPlain('reportes/spc_xbar', [
            'subgrupos'  => $subgrupos,
            'limites'    => $limites,
            'capacidad'  => $capacidad,
            'producto'   => $producto,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    // POST /reportes/mantenimiento
    public function mantenimiento(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');

        $datos = $this->db->fetchAll(
            "SELECT m.*,
                    e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre,
                    l.nombre AS linea_nombre,
                    u1.nombre AS ejecutado_nombre
            FROM mantenimientos m
            JOIN equipos e ON e.id = m.equipo_id
            JOIN lineas_produccion l ON l.id = e.linea_id
            LEFT JOIN usuarios u1 ON u1.id = m.ejecutado_por_id
            WHERE DATE(m.fecha_inicio) BETWEEN ? AND ?
            ORDER BY m.fecha_inicio DESC",
            [$fechaDesde, $fechaHasta]
        );

        $stats = [
            'total'       => count($datos),
            'preventivos' => count(array_filter($datos, fn($d) => $d['tipo']==='preventivo')),
            'correctivos' => count(array_filter($datos, fn($d) => $d['tipo']==='correctivo')),
            'horas_paro'  => round(
                array_sum(array_map(
                    fn($d) => $d['paro_produccion'] ? ($d['tiempo_paro_min'] ?? 0) : 0,
                    $datos
                )) / 60, 2
            ),
        ];

        $this->renderPlain('reportes/mantenimiento', [
            'datos'      => $datos,
            'stats'      => $stats,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    // POST /reportes/kpi-periodo
    public function kpiPeriodo(): void
    {
        $this->verifyCsrf();
        $fechaDesde = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');

        $oee          = $this->oee->calcularOee($fechaDesde, $fechaHasta);
        $kpisProducto = $this->oee->kpisPorProducto($fechaDesde, $fechaHasta);
        $mermas       = $this->oee->mermasPorTipo($fechaDesde, $fechaHasta);

        $this->renderPlain('reportes/kpi_periodo', [
            'oee'          => $oee,
            'kpisProducto' => $kpisProducto,
            'mermas'       => $mermas,
            'fechaDesde'   => $fechaDesde,
            'fechaHasta'   => $fechaHasta,
        ]);
    }

    // POST /reportes/trazabilidad-lote
    public function trazabilidadLote(): void
    {
        $this->verifyCsrf();
        $codigoLote = $this->input('codigo_lote');

        if (empty($codigoLote)) {
            $this->flash('error', 'Ingrese el código de lote.');
            $this->redirect('/reportes');
        }

        $this->redirect("/m5/lote/{$codigoLote}/pdf");
    }

    // POST /reportes/capacidad-proceso y calibraciones
    public function capacidadProceso(): void { $this->spcXbar(); }
    public function calibraciones(): void    { $this->mantenimiento(); }
    public function controlProceso(): void   { $this->seguimientoProduccion(); }
}