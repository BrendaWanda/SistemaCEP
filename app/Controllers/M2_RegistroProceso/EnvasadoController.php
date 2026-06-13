<?php
namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;

class EnvasadoController extends Controller
{
    private SesionRegistro $modelSesion;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->modelSesion = new SesionRegistro();
    }

    // POST /m2/sesion/:id/envasado
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

        // Recoger pesos de unidades
        $pesos = [];
        $suma  = 0;
        $count = 0;
        for ($i = 1; $i <= 4; $i++) {
            $val = $this->inputFloat("peso_unidad_{$i}") ?: null;
            $pesos["peso_unidad_{$i}"] = $val;
            if ($val !== null) { $suma += $val; $count++; }
        }
        $promedio = $count > 0 ? round($suma / $count, 4) : null;

        // Verificar si está fuera de especificación
        $lseG = (float)$this->db->fetchScalar(
            "SELECT p.lse_g FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            WHERE l.id = ?",
            [$sesion['lote_id']]
        );
        $lieG = (float)$this->db->fetchScalar(
            "SELECT p.lie_g FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            WHERE l.id = ?",
            [$sesion['lote_id']]
        );

        $fueraEsp = $promedio !== null
            && ($promedio > $lseG || $promedio < $lieG) ? 1 : 0;

        // Campos de conformidad
        $confCampos = [
            'codif_horizontal','codif_vertical',
            'sellado_horizontal','sellado_vertical'
        ];
        $confData = [];
        foreach ($confCampos as $campo) {
            $val = $this->input($campo);
            $confData[$campo] = in_array($val, ['conforme','no_conforme','na'])
                ? $val : null;
        }

        $this->db->execute(
            "INSERT INTO reg_control_envasado
                (sesion_id, hora,
                temperatura_producto_c, temperatura_ambiente_c,
                peso_unidad_1, peso_unidad_2,
                peso_unidad_3, peso_unidad_4,
                promedio_peso_unidad, fuera_especificacion,
                codif_horizontal, codif_vertical,
                sellado_horizontal, sellado_vertical,
                total_unidades, registrado_por_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $sesionId,
                $this->input('hora') ?: date('H:i:s'),
                $this->inputFloat('temperatura_producto_c') ?: null,
                $this->inputFloat('temperatura_ambiente_c') ?: null,
                $pesos['peso_unidad_1'],
                $pesos['peso_unidad_2'],
                $pesos['peso_unidad_3'],
                $pesos['peso_unidad_4'],
                $promedio,
                $fueraEsp,
                $confData['codif_horizontal'],
                $confData['codif_vertical'],
                $confData['sellado_horizontal'],
                $confData['sellado_vertical'],
                $this->inputInt('total_unidades') ?: null,
                Auth::id(),
            ]
        );

        if ($this->isAjax()) {
            $this->jsonSuccess(['fuera_especificacion' => $fueraEsp],
                $fueraEsp
                    ? '⚠️ Peso fuera de especificación detectado.'
                    : 'Control de envasado guardado correctamente.');
        }

        $msg = $fueraEsp
            ? '⚠️ Envasado guardado — peso fuera de especificación.'
            : 'Control de envasado guardado correctamente.';

        $this->redirectWithSuccess("/m2/sesion/{$sesionId}", $msg);
    }
}