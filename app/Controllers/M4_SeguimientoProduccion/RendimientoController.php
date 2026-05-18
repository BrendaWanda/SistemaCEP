<?php
namespace App\Controllers\M4_SeguimientoProduccion;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\LoteProduccion;

class RendimientoController extends Controller
{
    private LoteProduccion $model;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m4_seguimiento');
        $this->model = new LoteProduccion();
    }

    // GET /m4/lote/:codigo/rendimiento
    public function ver(array $params): void
    {
        $lote = $this->model->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }
        $this->render('m4_seguimiento/rendimiento', [
            'pageTitle'  => 'Rendimiento — Lote '.$lote['codigo_lote'],
            'breadcrumb' => [
                ['label' => 'Seguimiento', 'url' => APP_URL.'/m4'],
                ['label' => 'Lote '.$lote['codigo_lote'],
                'url'   => APP_URL.'/m4/lote/'.$lote['codigo_lote']],
                ['label' => 'Rendimiento'],
            ],
            'lote'     => $lote,
            'canWrite' => Auth::canWrite('m4_seguimiento')
                            && $lote['estado'] === 'en_proceso',
        ]);
    }

    // POST /m4/lote/:codigo/rendimiento
    public function guardar(array $params): void
    {
        Auth::requireWrite('m4_seguimiento');
        $this->verifyCsrf();

        $lote = $this->model->porCodigo($params['codigo']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m4');
        }

        if ($lote['estado'] !== 'en_proceso') {
            $this->flash('error', 'No se puede editar un lote que no está en proceso.');
            $this->redirect("/m4/lote/{$params['codigo']}");
        }

        $datos = [
            'rendimiento_teorico_total' => $this->inputInt('rendimiento_teorico_total'),
            'rendimiento_real_total'    => $this->inputInt('rendimiento_real_total'),
            'merma_producto_kg'         => $this->inputFloat('merma_producto_kg'),
            'merma_envase_kg'           => $this->inputFloat('merma_envase_kg'),
            'merma_reproceso_kg'        => $this->inputFloat('merma_reproceso_kg'),
            'merma_no_conforme_kg'      => $this->inputFloat('merma_no_conforme_kg'),
            'merma_quemado_kg'          => $this->inputFloat('merma_quemado_kg'),
        ];

        // Validar que el rendimiento real no sea mayor al doble del teórico
        if ($datos['rendimiento_real_total'] > $datos['rendimiento_teorico_total'] * 2) {
            $this->flash('error',
                'El rendimiento real parece muy alto. Verifique los datos.');
            $this->redirect("/m4/lote/{$params['codigo']}/rendimiento");
        }

        $this->model->actualizarRendimiento($lote['id'], $datos);

        // Registrar evento de trazabilidad
        $pct = $datos['rendimiento_teorico_total'] > 0
            ? round(($datos['rendimiento_real_total']
                     / $datos['rendimiento_teorico_total']) * 100, 2)
            : 0;

        $this->db->execute(
            "INSERT INTO trazabilidad_eventos
            (codigo_lote, tipo_evento, descripcion, tabla_origen, registro_id, usuario_id)
            VALUES (?,?,?,?,?,?)",
            [
                $lote['codigo_lote'], 'registro_proceso',
                "Rendimiento registrado: {$datos['rendimiento_real_total']} und reales "
                . "({$pct}% del teórico). Merma total: "
                . ($datos['merma_producto_kg'] + $datos['merma_envase_kg']
                    + $datos['merma_reproceso_kg'] + $datos['merma_no_conforme_kg']
                    + $datos['merma_quemado_kg']) . " kg.",
                'lotes_produccion', $lote['id'], Auth::id()
            ]
        );

        $this->redirectWithSuccess("/m4/lote/{$params['codigo']}",
            'Rendimiento actualizado correctamente.');
    }
}