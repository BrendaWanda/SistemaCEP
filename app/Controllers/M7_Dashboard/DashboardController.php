<?php
// =============================================================================
//  SIACEP — M7: Dashboard Controller (versión inicial)
//  Archivo: app/Controllers/M7_Dashboard/DashboardController.php
// =============================================================================

namespace App\Controllers\M7_Dashboard;

use App\Core\Controller;
use App\Core\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::require();
    }

    // GET / y GET /dashboard
    public function index(): void
    {
        // Datos básicos para el dashboard inicial
        $usuario = Auth::user();

        // Conteos rápidos desde la BD
        $stats = [
            'productos'  => (int) $this->db->fetchScalar("SELECT COUNT(*) FROM productos WHERE activo = 1"),
            'insumos'    => (int) $this->db->fetchScalar("SELECT COUNT(*) FROM insumos WHERE activo = 1"),
            'equipos'    => (int) $this->db->fetchScalar("SELECT COUNT(*) FROM equipos WHERE activo = 1"),
            'lotes_hoy'  => (int) $this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE DATE(creado_en) = CURDATE()"
            ),
            'mp_stock'   => (int) $this->db->fetchScalar(
                "SELECT COUNT(*) FROM stock_mp WHERE estado = 'disponible'"
            ),
            'calibraciones_vencidas' => (int) $this->db->fetchScalar(
                "SELECT COUNT(*) FROM equipos 
                WHERE requiere_calibracion = 1 
                AND activo = 1
                AND (fecha_prox_calibr IS NULL OR fecha_prox_calibr < CURDATE())"
            ),
        ];

        // Últimos lotes de producción
        $ultimosLotes = $this->db->fetchAll(
            "SELECT l.*, p.nombre AS producto_nombre
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            ORDER BY l.creado_en DESC
            LIMIT 5"
        );

        // Alertas activas
        $alertas = [];
        if ($stats['calibraciones_vencidas'] > 0) {
            $alertas[] = [
                'tipo'    => 'warning',
                'mensaje' => $stats['calibraciones_vencidas'] . ' equipo(s) con calibración vencida o sin registro.',
                'url'     => '/m3/calibraciones',
            ];
        }

        // MP próximas a vencer (30 días)
        $mpPorVencer = (int) $this->db->fetchScalar(
            "SELECT COUNT(*) FROM stock_mp 
            WHERE estado = 'disponible' 
            AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        );
        if ($mpPorVencer > 0) {
            $alertas[] = [
                'tipo'    => 'warning',
                'mensaje' => $mpPorVencer . ' lote(s) de materia prima próximos a vencer (30 días).',
                'url'     => '/m1/alertas-vencimiento',
            ];
        }

        $this->render('m7_dashboard/index', [
            'pageTitle'   => 'Dashboard',
            'breadcrumb'  => [['label' => 'Dashboard']],
            'usuario'     => $usuario,
            'stats'       => $stats,
            'ultimosLotes'=> $ultimosLotes,
            'alertas'     => $alertas,
        ]);
    }

    // GET /api/kpi-resumen (AJAX para widgets del dashboard)
    public function kpiResumen(): void
    {
        Auth::require();
        $data = [
            'oee'            => null, // Se calculará cuando haya datos
            'lotes_hoy'      => (int) $this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE DATE(creado_en) = CURDATE()"
            ),
            'senales_activas'=> (int) $this->db->fetchScalar(
                "SELECT COUNT(*) FROM spc_senales_detectadas WHERE estado = 'nueva'"
            ),
        ];
        $this->jsonSuccess($data);
    }
}
