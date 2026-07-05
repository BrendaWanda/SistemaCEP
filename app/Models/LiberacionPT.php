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

    // Guardar liberación y actualizar estado del lote
    public function liberarYActualizarLote(array $data, int $loteId,
                                            string $codigoLote,
                                            int $usuarioId): int
    {
        return (int)$this->db->transaction(function($db) use (
            $data, $loteId, $codigoLote, $usuarioId
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