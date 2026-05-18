<?php
namespace App\Models;

use App\Core\Model;

class ConsumoMP extends Model
{
    protected string $table    = 'consumo_mp_por_lote';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'lote_id','stock_mp_id','insumo_id',
        'lote_proveedor','cantidad_usada',
        'unidad_medida','registrado_por_id'
    ];

    // Registrar consumo y descontar del stock
    public function registrar(
        int    $loteId,
        int    $stockMpId,
        float  $cantidad,
        int    $usuarioId
    ): bool {
        // Verificar stock suficiente
        $stock = $this->db->fetchOne(
            "SELECT * FROM stock_mp WHERE id = ? AND estado = 'disponible'",
            [$stockMpId]
        );

        if (!$stock || (float)$stock['cantidad_disponible'] < $cantidad) {
            return false;
        }

        return (bool)$this->db->transaction(function($db) use (
            $loteId, $stockMpId, $stock, $cantidad, $usuarioId
        ) {
            // Insertar consumo
            $db->execute(
                "INSERT INTO consumo_mp_por_lote
                (lote_id, stock_mp_id, insumo_id, lote_proveedor,
                    cantidad_usada, unidad_medida, registrado_por_id)
                VALUES (?,?,?,?,?,?,?)",
                [
                    $loteId,
                    $stockMpId,
                    $stock['insumo_id'],
                    $stock['lote_proveedor'],
                    $cantidad,
                    $stock['unidad_medida'],
                    $usuarioId,
                ]
            );

            // Descontar del stock
            $nueva  = (float)$stock['cantidad_disponible'] - $cantidad;
            $estado = $nueva <= 0 ? 'agotado' : 'disponible';
            $db->execute(
                "UPDATE stock_mp SET cantidad_disponible = ?, estado = ? WHERE id = ?",
                [max(0, $nueva), $estado, $stockMpId]
            );

            return true;
        });
    }

    // Eliminar un consumo y devolver al stock
    public function eliminarYDevolverStock(int $consumoId): bool
    {
        $consumo = $this->find($consumoId);
        if (!$consumo) return false;

        return (bool)$this->db->transaction(function($db) use ($consumo) {
            // Devolver cantidad al stock
            $db->execute(
                "UPDATE stock_mp
                SET cantidad_disponible = cantidad_disponible + ?,
                    estado = 'disponible'
                WHERE id = ?",
                [$consumo['cantidad_usada'], $consumo['stock_mp_id']]
            );

            // Eliminar el registro de consumo
            $db->execute(
                "DELETE FROM consumo_mp_por_lote WHERE id = ?",
                [$consumo['id']]
            );

            return true;
        });
    }

    // Total de MP consumida por lote agrupada por insumo
    public function resumenPorLote(int $loteId): array
    {
        return $this->query(
            "SELECT i.codigo, i.descripcion, i.unidad_medida,
                    SUM(c.cantidad_usada) AS total_usado,
                    COUNT(c.id) AS lotes_usados
            FROM consumo_mp_por_lote c
            JOIN insumos i ON i.id = c.insumo_id
            WHERE c.lote_id = ?
            GROUP BY i.id
            ORDER BY i.tipo, i.descripcion",
            [$loteId]
        );
    }
}