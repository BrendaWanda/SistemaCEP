<?php
// app/Controllers/M2_RegistroProceso/InspeccionAtributosController.php

namespace App\Controllers\M2_RegistroProceso;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\SesionRegistro;

class InspeccionAtributosController extends Controller
{
    private SesionRegistro $modelSesion;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m2_registro_proceso');
        $this->modelSesion = new SesionRegistro();
    }

    // POST /m2/sesion/:id/inspeccion-atributos
    public function guardar(array $params): void
    {
        Auth::requireWrite('m2_registro_proceso');
        $this->verifyCsrf();

        $sesionId   = (int)$params['id'];
        $sesion     = $this->modelSesion->find($sesionId);

        if (!$sesion || $sesion['estado'] !== 'en_proceso') {
            $this->flash('error', 'Sesión no disponible.');
            $this->redirect('/m2');
            return;
        }

        $parametroId    = (int)($_POST['parametro_id']    ?? 0);
        $nInspeccionado = (int)($_POST['n_inspeccionado'] ?? 50);
        $nNoConformes   = (int)($_POST['n_no_conformes']  ?? 0);
        $observaciones  = $this->input('observaciones') ?: null;

        // Validaciones
        if (!$parametroId) {
            $this->flash('error', 'Seleccione un parámetro de atributo.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        if ($nInspeccionado < 1) {
            $this->flash('error', 'El n inspeccionado debe ser mayor a 0.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        if ($nNoConformes < 0 || $nNoConformes > $nInspeccionado) {
            $this->flash('error', 'El número de no conformes no puede ser mayor al n inspeccionado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        // Obtener lote y producto
        $lote = $this->db->fetchOne(
            "SELECT id, producto_id FROM lotes_produccion WHERE id = ?",
            [$sesion['lote_id']]
        );

        if (!$lote) {
            $this->flash('error', 'Lote no encontrado.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        // Verificar que el parámetro pertenece al producto y es de atributo SPC
        $parametro = $this->db->fetchOne(
            "SELECT id, nombre FROM parametros_proceso
            WHERE id = ? AND producto_id = ?
                AND es_variable_spc = 1
                AND tipo_dato IN ('seleccion','si_no')
                AND activo = 1",
            [$parametroId, $lote['producto_id']]
        );

        if (!$parametro) {
            $this->flash('error', 'Parámetro no válido para este producto.');
            $this->redirect("/m2/sesion/{$sesionId}");
            return;
        }

        // Si ya existe para esta sesión+parámetro → actualizar, si no → insertar
        $existe = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM reg_inspeccion_atributos
            WHERE sesion_id = ? AND parametro_id = ?",
            [$sesionId, $parametroId]
        );

        if ($existe > 0) {
            $this->db->execute(
                "UPDATE reg_inspeccion_atributos
                SET n_inspeccionado = ?, n_no_conformes = ?,
                    observaciones = ?, registrado_por_id = ?
                WHERE sesion_id = ? AND parametro_id = ?",
                [
                    $nInspeccionado, $nNoConformes,
                    $observaciones, Auth::id(),
                    $sesionId, $parametroId,
                ]
            );
            $this->redirectWithSuccess(
                "/m2/sesion/{$sesionId}",
                "Inspección de '{$parametro['nombre']}' actualizada."
            );
        } else {
            $this->db->execute(
                "INSERT INTO reg_inspeccion_atributos
                (sesion_id, lote_id, producto_id, parametro_id,
                    fecha, turno, n_inspeccionado, n_no_conformes,
                    observaciones, registrado_por_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $sesionId,
                    $lote['id'],
                    $lote['producto_id'],
                    $parametroId,
                    $sesion['fecha'],
                    $sesion['turno'],
                    $nInspeccionado,
                    $nNoConformes,
                    $observaciones,
                    Auth::id(),
                ]
            );
            $this->redirectWithSuccess(
                "/m2/sesion/{$sesionId}",
                "Inspección de '{$parametro['nombre']}' registrada correctamente."
            );
        }
    }
}