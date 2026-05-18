<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;

class AmasadoController extends Controller
{
    private SesionRegistro $modelSesion;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->modelSesion = new SesionRegistro();
    }

    // POST /m2/sesion/:id/amasado
    public function guardar(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId = (int)$params['id'];
        $sesion   = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->jsonError('Sesión no disponible.', 400);
        }

        $this->db->execute(
            "INSERT INTO reg_proceso_amasado
            (sesion_id, hora, temperatura_masa_c, ph_masa,
                observaciones_masa, registrado_por_id)
            VALUES (?,?,?,?,?,?)",
            [
                $sesionId,
                $this->input('hora') ?: date('H:i:s'),
                $this->inputFloat('temperatura_masa_c') ?: null,
                $this->inputFloat('ph_masa') ?: null,
                $this->input('observaciones_masa'),
                Auth::id(),
            ]
        );

        if ($this->isAjax()) {
            $this->jsonSuccess(null, 'Datos de amasado guardados.');
        }
        $this->redirectWithSuccess("/m2/sesion/{$sesionId}",
            'Datos de amasado guardados.');
    }
}