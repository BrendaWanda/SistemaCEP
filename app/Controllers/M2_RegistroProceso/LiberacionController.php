<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\LiberacionPT;
use App\Models\SesionRegistro;
use App\Models\LoteProduccion;

class LiberacionController extends Controller
{
    private LiberacionPT   $model;
    private SesionRegistro $modelSesion;
    private LoteProduccion $modelLote;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->model       = new LiberacionPT();
        $this->modelSesion = new SesionRegistro();
        $this->modelLote   = new LoteProduccion();
    }

    // POST /m2/sesion/:id/liberacion
    public function guardar(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->flash('error', 'Sesión no disponible.');
            $this->redirect('/m2');
        }

        $decision = $this->input('decision_final');
        if (!in_array($decision, ['liberado', 'cuarentena'])) {
            $this->flash('error', 'Decisión inválida.');
            $this->redirect("/m2/sesion/{$sesionId}");
        }

        $lote = $this->modelLote->find($sesion['lote_id']);
        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect('/m2');
        }

        $confCampos = [
            'codif_lote_legible','codif_lote_correcto',
            'codif_fvenc_legible','codif_fvenc_correcto',
            'envase_primario','envase_secundario','envase_terciario','inocuo',
            'conf_color','conf_olor','conf_sabor','conf_apariencia',
            'conf_textura','conf_particulas_extranas',
        ];

        $data = ['sesion_id' => $sesionId, 'hora' => date('H:i:s')];
        foreach ($confCampos as $campo) {
            $val = $this->input($campo);
            $data[$campo] = in_array($val, ['conforme','no_conforme','na'])
                ? $val : null;
        }

        $data['resultado_humedad_pct']  = $this->inputFloat('resultado_humedad_pct') ?: null;
        $data['resultado_ph']           = $this->inputFloat('resultado_ph') ?: null;
        $data['decision_final']         = $decision;
        $data['observaciones']          = $this->input('observaciones');
        $data['supervisor_calidad_id']  = $this->inputInt('supervisor_calidad_id')
                                            ?: Auth::id();
        $data['encargado_nutricion_id'] = $this->inputInt('encargado_nutricion_id')
                                            ?: null;
        $data['jefe_produccion_id']     = $this->inputInt('jefe_produccion_id')
                                            ?: null;

        $this->model->liberarYActualizarLote(
            $data,
            $lote['id'],
            $lote['codigo_lote'],
            Auth::id()
        );

        // Marcar sesión como completa
        $this->modelSesion->update($sesionId, [
            'estado'            => 'completo',
            'hora_fin_registro' => date('Y-m-d H:i:s'),
        ]);

        $msg = $decision === 'liberado'
            ? '✅ Producto LIBERADO correctamente.'
            : '⚠️ Producto enviado a CUARENTENA.';

        $this->redirectWithSuccess("/m2/sesion/{$sesionId}", $msg);
    }
}