<?php
namespace App\Controllers\M5_Trazabilidad;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Trazabilidad;

class TrazabilidadController extends Controller
{
    private Trazabilidad $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m5_trazabilidad');
        $this->model = new Trazabilidad();
    }

    // GET /m5
    public function index(): void
    {
        $termino    = $this->input('q');
        $resultados = [];

        if (!empty($termino)) {
            $resultados = $this->model->buscar($termino);
        }

        // Últimos 10 lotes para acceso rápido
        $ultimosLotes = $this->db->fetchAll(
            "SELECT l.codigo_lote, l.fecha_produccion, l.estado,
                    p.nombre AS producto_nombre
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            ORDER BY l.creado_en DESC LIMIT 10"
        );

        $this->render('m5_trazabilidad/index', [
            'pageTitle'   => 'Trazabilidad',
            'breadcrumb'  => [['label' => 'Trazabilidad']],
            'termino'     => $termino,
            'resultados'  => $resultados,
            'ultimosLotes'=> $ultimosLotes,
        ]);
    }

    // GET /m5/lote/:codigo
    public function lote(array $params): void
    {
        $codigo = $params['codigo'];
        $datos  = $this->model->porLote($codigo);

        if (!$datos) {
            $this->flash('error', "Lote '{$codigo}' no encontrado.");
            $this->redirect('/m5');
        }

        $this->render('m5_trazabilidad/lote', [
            'pageTitle'  => 'Trazabilidad — Lote '.$codigo,
            'breadcrumb' => [
                ['label'=>'Trazabilidad','url'=>APP_URL.'/m5'],
                ['label'=>'Lote '.$codigo],
            ],
            'datos'  => $datos,
            'codigo' => $codigo,
        ]);
    }

    // GET /m5/insumo/:codigo
    public function insumo(array $params): void
    {
        $codigo  = $params['codigo'];
        $lotes   = $this->model->porInsumo($codigo);
        $insumo  = $this->db->fetchOne(
            "SELECT * FROM insumos WHERE codigo = ?", [$codigo]
        );

        $this->render('m5_trazabilidad/insumo', [
            'pageTitle'  => 'Trazabilidad — Insumo '.$codigo,
            'breadcrumb' => [
                ['label'=>'Trazabilidad','url'=>APP_URL.'/m5'],
                ['label'=>'Insumo '.$codigo],
            ],
            'lotes'  => $lotes,
            'insumo' => $insumo,
            'codigo' => $codigo,
        ]);
    }

    // GET /m5/lote/:codigo/pdf
    public function exportarPdf(array $params): void
    {
        $codigo = $params['codigo'];
        $datos  = $this->model->porLote($codigo);

        if (!$datos) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m5');
        }

        $this->renderPlain('m5_trazabilidad/pdf', [
            'datos'  => $datos,
            'codigo' => $codigo,
        ]);
    }
}