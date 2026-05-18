<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;

class HorneadoController extends Controller
{
    private SesionRegistro $modelSesion;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->modelSesion = new SesionRegistro();
    }

    // POST /m2/sesion/:id/horneado
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

        $confCampos = [
            'conf_olor','conf_sabor','conf_color',
            'conf_aspecto','conf_textura'
        ];
        $confData = [];
        foreach ($confCampos as $campo) {
            $val = $this->input($campo);
            $confData[$campo] = in_array($val, ['conforme','no_conforme','na'])
                ? $val : null;
        }

        $this->db->execute(
            "INSERT INTO reg_proceso_horneado
                (sesion_id, hora, tiempo_fermentacion_min,
                temperatura_horno_c, tiempo_horneado_min,
                conf_olor, conf_sabor, conf_color,
                conf_aspecto, conf_textura,
                observaciones, registrado_por_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $sesionId,
                $this->input('hora') ?: date('H:i:s'),
                $this->inputInt('tiempo_fermentacion_min') ?: null,
                $this->inputFloat('temperatura_horno_c') ?: null,
                $this->inputInt('tiempo_horneado_min') ?: null,
                $confData['conf_olor'],
                $confData['conf_sabor'],
                $confData['conf_color'],
                $confData['conf_aspecto'],
                $confData['conf_textura'],
                $this->input('observaciones'),
                Auth::id(),
            ]
        );

        if ($this->isAjax()) {
            $this->jsonSuccess(null, 'Datos de horneado guardados.');
        }
        $this->redirectWithSuccess("/m2/sesion/{$sesionId}",
            'Datos de horneado guardados correctamente.');
    }
}