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

    // GET /dashboard
    public function index(): void
    {
        $fechaDesde = $this->input('fecha_desde')
            ?: date('Y-m-01'); // Primer día del mes
        $fechaHasta = $this->input('fecha_hasta')
            ?: date('Y-m-d');

        // OEE del período
        $oee = $this->oeeService->calcularOee($fechaDesde, $fechaHasta);

        // KPIs por producto
        $kpisProducto = $this->oeeService->kpisPorProducto($fechaDesde, $fechaHasta);

        // Tendencia diaria
        $tendencia = $this->oeeService->tendenciaDiaria($fechaDesde, $fechaHasta);

        // Mermas por tipo
        $mermas = $this->oeeService->mermasPorTipo($fechaDesde, $fechaHasta);

        // Stock MP crítico
        $stockCritico = $this->oeeService->stockActual();

        // KPIs generales del sistema
        $kpisGenerales = [
            'lotes_mes'      => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion
                    WHERE fecha_produccion BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            ),
            'recepciones_mes'=> (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM recepciones_mp
                    WHERE fecha_analisis BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            ),
            'sesiones_mes'   => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM sesiones_registro
                    WHERE fecha BETWEEN ? AND ?",
                [$fechaDesde, $fechaHasta]
            ),
            'senales_spc'    => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM spc_senales_detectadas
                    WHERE estado = 'nueva'"
            ),
            'mant_pendientes'=> (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM mantenimientos
                    WHERE resultado IN ('pendiente','en_proceso')"
            ),
            'stock_alertas'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM stock_mp
                    WHERE estado = 'disponible'
                    AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
            ),
        ];

        $this->render('m7_dashboard/index', [
            'pageTitle'     => 'Dashboard — KPIs y OEE',
            'breadcrumb'    => [['label' => 'Dashboard']],
            'oee'           => $oee,
            'kpisProducto'  => $kpisProducto,
            'tendencia'     => $tendencia,
            'mermas'        => $mermas,
            'stockCritico'  => $stockCritico,
            'kpisGenerales' => $kpisGenerales,
            'fechaDesde'    => $fechaDesde,
            'fechaHasta'    => $fechaHasta,
        ]);
    }
}