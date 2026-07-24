<?php
namespace App\Models;

use App\Core\Model;

class LiberacionPT extends Model
{
    protected string $table    = 'reg_liberacion_pt';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'sesion_id','hora','decision_final','observaciones',
        'supervisor_calidad_id','encargado_nutricion_id','jefe_produccion_id'
    ];

    // Guardar liberación, actualizar estado del lote, y cerrar la
    // sesión — TODO en una sola transacción.
    //
    // Hallazgo de auditoría: antes el cierre de la sesión (estado
    // 'completo' + hora_fin_registro) se hacía con una llamada SEPARADA
    // desde el controller, DESPUÉS de que esta transacción ya hubiera
    // confirmado. Si esa segunda llamada fallaba por cualquier motivo
    // (ej. una desconexión transitoria), el lote quedaba liberado o en
    // cuarentena, pero su sesión de registro seguía en 'en_proceso' —
    // lo que significa que los datos de proceso de un lote YA LIBERADO
    // seguirían siendo editables indefinidamente (RegistroDinamicoController
    // bloquea la edición únicamente según sesion.estado). Se integra el
    // cierre de sesión en la misma transacción para que ambas cosas
    // sucedan juntas o ninguna.
    public function liberarYActualizarLote(array $data, int $loteId,
                                            string $codigoLote,
                                            int $sesionId,
                                            int $usuarioId): int
    {
        return (int)$this->db->transaction(function($db) use (
            $data, $loteId, $codigoLote, $sesionId, $usuarioId
        ) {
            // Insertar liberación
            $cols  = implode(',', array_keys($data));
            $marks = implode(',', array_fill(0, count($data), '?'));
            $db->execute(
                "INSERT INTO reg_liberacion_pt ({$cols}) VALUES ({$marks})",
                array_values($data)
            );
            $libId = (int)$db->lastInsertId();

            // Actualizar estado del lote
            $nuevoEstado = $data['decision_final'] === 'liberado'
                ? 'liberado' : 'cuarentena';

            $db->execute(
                "UPDATE lotes_produccion SET estado = ? WHERE id = ?",
                [$nuevoEstado, $loteId]
            );

            // Cerrar la sesión de registro — ya no se puede seguir
            // editando datos de proceso de un lote que Calidad ya
            // liberó o puso en cuarentena.
            $db->execute(
                "UPDATE sesiones_registro
                SET estado = 'completo', hora_fin_registro = ?
                WHERE id = ?",
                [date('Y-m-d H:i:s'), $sesionId]
            );

            // Registrar evento de trazabilidad
            $db->execute(
                "INSERT INTO trazabilidad_eventos
                (codigo_lote, tipo_evento, descripcion,
                tabla_origen, registro_id, usuario_id)
                VALUES (?,?,?,?,?,?)",
                [
                    $codigoLote,
                    $data['decision_final'] === 'liberado'
                        ? 'liberacion' : 'cuarentena',
                    "Producto "
                        . ($data['decision_final'] === 'liberado'
                            ? 'LIBERADO' : 'en CUARENTENA')
                        . ". Decisión registrada por supervisor de calidad.",
                    'reg_liberacion_pt', $libId, $usuarioId
                ]
            );

            return $libId;
        });
    }
}