<?php
namespace App\Controllers\M7_Dashboard;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\OeeService;

class DashboardController extends Controller
{
    private OeeService $oeeService;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m7_dashboard');
        $this->oeeService = new OeeService();
    }

    // GET / y /dashboard — Dashboard operativo del día
    public function index(): void
    {
        $hoy = date('Y-m-d');

        // Stats del día
        $stats = [
            'lotes_hoy' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion
                 WHERE fecha_produccion = ?", [$hoy]
            ),
            'liberados_hoy' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion
                 WHERE fecha_produccion = ? AND estado = 'liberado'", [$hoy]
            ),
            'en_proceso' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE estado = 'en_proceso'"
            ),
            'recepciones_hoy' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM recepciones_mp
                 WHERE fecha_analisis = ?", [$hoy]
            ),
            'rend_promedio' => (float)($this->db->fetchScalar(
                "SELECT AVG(porcentaje_rendimiento)
                 FROM lotes_produccion
                 WHERE fecha_produccion = ?
                 AND porcentaje_rendimiento > 0", [$hoy]
            ) ?? 0),
            'alertas' => 0,
        ];

        // Lotes en proceso
        $lotesEnProceso = $this->db->fetchAll(
            "SELECT l.codigo_lote, l.fecha_produccion, l.estado,
                    p.nombre AS producto_nombre
             FROM lotes_produccion l
             JOIN productos p ON p.id = l.producto_id
             WHERE l.estado = 'en_proceso'
             ORDER BY l.creado_en DESC LIMIT 5"
        );

        // Últimos 8 lotes
        $ultimosLotes = $this->db->fetchAll(
            "SELECT l.codigo_lote, l.fecha_produccion, l.estado,
                    l.porcentaje_rendimiento,
                    p.nombre AS producto_nombre
             FROM lotes_produccion l
             JOIN productos p ON p.id = l.producto_id
             ORDER BY l.creado_en DESC LIMIT 8"
        );

        // Alertas stock (vencimiento próximo)
        $alertasStock = $this->db->fetchAll(
            "SELECT s.fecha_vencimiento,
                    i.descripcion AS insumo_nombre,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM stock_mp s
             JOIN insumos i ON i.id = s.insumo_id
             WHERE s.estado = 'disponible'
               AND s.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY s.fecha_vencimiento ASC LIMIT 3"
        );

        // Alertas mantenimiento
        $alertasMant = $this->db->fetchAll(
            "SELECT e.nombre AS equipo_nombre,
                    e.fecha_prox_mant,
                    DATEDIFF(e.fecha_prox_mant, CURDATE()) AS dias_restantes
             FROM equipos e
             WHERE e.activo = 1
               AND e.fecha_prox_mant IS NOT NULL
               AND e.fecha_prox_mant <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)
             ORDER BY e.fecha_prox_mant ASC LIMIT 3"
        );

        $stats['alertas'] = count($alertasStock) + count($alertasMant)
            + (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM spc_senales_detectadas WHERE estado = 'nueva'"
            );

        $this->render('m7_dashboard/index', [
            'pageTitle'      => 'Dashboard',
            'breadcrumb'     => [['label' => 'Dashboard']],
            'stats'          => $stats,
            'lotesEnProceso' => $lotesEnProceso,
            'ultimosLotes'   => $ultimosLotes,
            'alertasStock'   => $alertasStock,
            'alertasMant'    => $alertasMant,
        ]);
    }

    // GET /m7 — KPIs y OEE con filtros
    public function kpi(): void
    {
        // Filtros del formulario
        $fechaDesde  = $this->input('fecha_desde') ?: date('Y-m-01');
        $fechaHasta  = $this->input('fecha_hasta') ?: date('Y-m-d');
        $productoId  = $this->inputInt('producto_id') ?: null;
        $agrupar     = $this->input('agrupar') ?: 'dia';

        // OEE del período
        $oee = $this->oeeService->calcularOee($fechaDesde, $fechaHasta);

        // KPIs por producto con filtro
        $whereProducto = $productoId ? 'AND l.producto_id = ?' : '';
        $paramsProducto = $productoId
            ? [$fechaDesde, $fechaHasta, $productoId]
            : [$fechaDesde, $fechaHasta];

        $kpisProducto = $this->db->fetchAll(
            "SELECT p.nombre AS producto_nombre, p.codigo AS producto_codigo,
                    COUNT(l.id) AS total_lotes,
                    SUM(l.rendimiento_real_total)    AS und_reales,
                    SUM(l.rendimiento_teorico_total) AS und_teoricas,
                    AVG(l.porcentaje_rendimiento)    AS rend_promedio,
                    SUM(l.merma_producto_kg + l.merma_envase_kg +
                        l.merma_reproceso_kg + l.merma_no_conforme_kg +
                        l.merma_quemado_kg) AS merma_total_kg,
                    COUNT(CASE WHEN l.estado='liberado'   THEN 1 END) AS liberados,
                    COUNT(CASE WHEN l.estado='cuarentena' THEN 1 END) AS cuarentena,
                    COUNT(CASE WHEN l.estado='rechazado'  THEN 1 END) AS rechazados
             FROM lotes_produccion l
             JOIN productos p ON p.id = l.producto_id
             WHERE l.fecha_produccion BETWEEN ? AND ? {$whereProducto}
             GROUP BY p.id ORDER BY und_reales DESC",
            $paramsProducto
        );

        // Tendencia agrupada
        $groupBy = match($agrupar) {
            'semana' => "YEARWEEK(fecha_produccion,1)",
            'mes'    => "DATE_FORMAT(fecha_produccion,'%Y-%m')",
            default  => "fecha_produccion",
        };
        $labelFormat = match($agrupar) {
            'semana' => "CONCAT('Sem ', WEEK(fecha_produccion,1))",
            'mes'    => "DATE_FORMAT(fecha_produccion,'%b %Y')",
            default  => "DATE_FORMAT(fecha_produccion,'%d/%m')",
        };

        $tendencia = $this->db->fetchAll(
            "SELECT {$labelFormat} AS periodo,
                    COUNT(id) AS lotes,
                    SUM(rendimiento_real_total) AS unidades,
                    AVG(porcentaje_rendimiento) AS rend_pct
             FROM lotes_produccion
             WHERE fecha_produccion BETWEEN ? AND ?
             ".($productoId ? 'AND producto_id = ?' : '')."
             GROUP BY {$groupBy}
             ORDER BY fecha_produccion ASC",
            $productoId ? [$fechaDesde, $fechaHasta, $productoId]
                        : [$fechaDesde, $fechaHasta]
        );

        // Mermas por tipo
        $mermas = $this->oeeService->mermasPorTipo($fechaDesde, $fechaHasta);

        // Señales SPC en el período
        $senalesSpc = $this->db->fetchAll(
            "SELECT p.nombre AS producto_nombre,
                    COUNT(s.id) AS total_senales,
                    COUNT(CASE WHEN s.estado='nueva' THEN 1 END) AS sin_resolver
             FROM spc_senales_detectadas s
             JOIN lotes_produccion l ON l.id = s.lote_id
             JOIN productos p ON p.id = l.producto_id
             WHERE l.fecha_produccion BETWEEN ? AND ?
             GROUP BY p.id",
            [$fechaDesde, $fechaHasta]
        );

        // Lista de productos para filtro
        $productos = $this->db->fetchAll(
            "SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre"
        );

        $this->render('m7_dashboard/kpi', [
            'pageTitle'    => 'KPIs y OEE',
            'breadcrumb'   => [['label' => 'KPIs y OEE']],
            'oee'          => $oee,
            'kpisProducto' => $kpisProducto,
            'tendencia'    => $tendencia,
            'mermas'       => $mermas,
            'senalesSpc'   => $senalesSpc,
            'productos'    => $productos,
            'filtros'      => compact('fechaDesde','fechaHasta','productoId','agrupar'),
        ]);
    }

    // API para resumen rápido
    public function kpiResumen(): void
    {
        $this->jsonSuccess([
            'lotes_hoy'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE fecha_produccion = CURDATE()"
            ),
            'en_proceso' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE estado = 'en_proceso'"
            ),
        ]);
    }
}