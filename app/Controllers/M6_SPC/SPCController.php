<?php
namespace App\Controllers\M6_SPC;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Producto;
use App\Models\LoteProduccion;
use App\Services\SpcService;

class SPCController extends Controller
{
    private SpcService     $spc;
    private Producto       $modelProducto;
    private LoteProduccion $modelLote;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m6_spc');
        $this->spc           = new SpcService();
        $this->modelProducto = new Producto();
        $this->modelLote     = new LoteProduccion();
    }

    // GET /m6
    public function index(): void
    {
        $productoId = $this->inputInt('producto_id') ?: null;
        $loteId     = $this->inputInt('lote_id') ?: null;
        $fechaDesde = $this->input('fecha_desde')
            ?: date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $this->input('fecha_hasta') ?: date('Y-m-d');

        // Obtener subgrupos de pesos filtrados
        $subgrupos = $this->obtenerSubgrupos(
            $productoId, $loteId, $fechaDesde, $fechaHasta
        );

        // Calcular límites y capacidad
        $limites   = [];
        $capacidad = [];
        $grafico   = [];
        $estadistica = [];
        $producto  = null;

        if (!empty($subgrupos) && $productoId) {
            $producto = $this->modelProducto->find($productoId);
            $limites  = $this->spc->calcularLimitesXbarR($subgrupos);
            $grafico  = $this->spc->datosGraficoXbarR($subgrupos, $limites);

            if ($producto && $limites['sigma_est']) {
                $capacidad = $this->spc->calcularCapacidad(
                    $subgrupos,
                    (float)$producto['lse_g'],
                    (float)$producto['lie_g']
                );
            }

            // Estadística descriptiva de X̄
            $xbars = array_filter(
                array_column($subgrupos, 'promedio_xbar'),
                fn($v) => $v !== null
            );
            if (!empty($xbars)) {
                $estadistica = $this->spc->estadisticaDescriptiva(
                    array_values($xbars)
                );
            }
        }

        // Estadísticas generales del M6
        $stats = [
            'total_subgrupos' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM reg_pesos_masa_cruda"
            ),
            'senales_nuevas'  => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM spc_senales_detectadas
                WHERE estado = 'nueva'"
            ),
            'lotes_con_senales' => (int)$this->db->fetchScalar(
                "SELECT COUNT(DISTINCT lote_id)
                FROM spc_senales_detectadas"
            ),
            'productos_analizados' => (int)$this->db->fetchScalar(
                "SELECT COUNT(DISTINCT l.producto_id)
                FROM reg_pesos_masa_cruda p
                JOIN sesiones_registro s ON s.id = p.sesion_id
                JOIN lotes_produccion l ON l.id = s.lote_id"
            ),
        ];

        $this->render('m6_spc/index', [
            'pageTitle'   => 'SPC — Control Estadístico de Proceso',
            'breadcrumb'  => [['label' => 'SPC']],
            'productos'   => $this->modelProducto->paraSelect(1),
            'lotes'       => $productoId
                ? $this->modelLote->activosParaSelect()
                : [],
            'filtros'     => compact('productoId','loteId','fechaDesde','fechaHasta'),
            'subgrupos'   => $subgrupos,
            'limites'     => $limites,
            'capacidad'   => $capacidad,
            'grafico'     => $grafico,
            'estadistica' => $estadistica,
            'producto'    => $producto,
            'stats'       => $stats,
            'canWrite'    => Auth::canWrite('m6_spc'),
        ]);
    }

    // API GET /api/spc-tiempo-real/:sesion
    public function tiempoReal(array $params): void
    {
        $sesionId = (int)$params['sesion'];

        $subgrupos = $this->db->fetchAll(
            "SELECT promedio_xbar, rango_r, hora, fuera_de_control
            FROM reg_pesos_masa_cruda
            WHERE sesion_id = ? AND promedio_xbar IS NOT NULL
            ORDER BY hora ASC",
            [$sesionId]
        );

        $limites = $this->spc->calcularLimitesXbarR($subgrupos);
        $grafico = $this->spc->datosGraficoXbarR($subgrupos, $limites);

        $this->jsonSuccess([
            'grafico' => $grafico,
            'limites' => $limites,
        ]);
    }

    // Helper privado
    private function obtenerSubgrupos(
        ?int   $productoId,
        ?int   $loteId,
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $where  = ['s.fecha BETWEEN ? AND ?'];
        $params = [$fechaDesde, $fechaHasta];

        if ($productoId) {
            $where[]  = 'l.producto_id = ?';
            $params[] = $productoId;
        }
        if ($loteId) {
            $where[]  = 's.lote_id = ?';
            $params[] = $loteId;
        }

        $whereStr = implode(' AND ', $where);

        return $this->db->fetchAll(
            "SELECT p.*, s.fecha, l.codigo_lote,
                    l.producto_id
            FROM reg_pesos_masa_cruda p
            JOIN sesiones_registro s ON s.id = p.sesion_id
            JOIN lotes_produccion l  ON l.id = s.lote_id
            WHERE {$whereStr}
                AND p.promedio_xbar IS NOT NULL
            ORDER BY s.fecha ASC, p.hora ASC",
            $params
        );
    }
}