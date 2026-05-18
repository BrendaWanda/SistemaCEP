<?php
namespace App\Models;

use App\Core\Model;

class StockMP extends Model
{
    protected string $table    = 'stock_mp';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'insumo_id','recepcion_id','lote_proveedor','fecha_vencimiento',
        'cantidad_inicial','cantidad_disponible','unidad_medida','estado'
    ];

    // Crear entrada de stock al aprobar una recepción
    public function ingresarDesdeRecepcion(array $recepcion): int
    {
        return $this->create([
            'insumo_id'           => $recepcion['insumo_id'],
            'recepcion_id'        => $recepcion['id'],
            'lote_proveedor'      => $recepcion['lote_proveedor'],
            'fecha_vencimiento'   => $recepcion['fecha_vencimiento'],
            'cantidad_inicial'    => $recepcion['stock_disponible_kg'],
            'cantidad_disponible' => $recepcion['stock_disponible_kg'],
            'unidad_medida'       => $recepcion['unidad_medida'],
            'estado'              => 'disponible',
        ]);
    }

    // Descontar stock al consumir MP en producción
    public function descontarStock(int $stockId, float $cantidad): bool
    {
        $stock = $this->find($stockId);
        if (!$stock) return false;

        $nueva = (float)$stock['cantidad_disponible'] - $cantidad;
        if ($nueva < 0) return false;

        $estado = $nueva <= 0 ? 'agotado' : 'disponible';
        $this->update($stockId, [
            'cantidad_disponible' => max(0, $nueva),
            'estado'              => $estado,
        ]);
        return true;
    }

    // Stock disponible de un insumo específico (para select en M4)
    public function porInsumo(int $insumoId): array
    {
        return $this->query(
            "SELECT s.*,
                    DATEDIFF(s.fecha_vencimiento, CURDATE()) AS dias_para_vencer
            FROM stock_mp s
            WHERE s.insumo_id = ? AND s.estado = 'disponible'
                AND s.cantidad_disponible > 0
            ORDER BY s.fecha_vencimiento ASC",
            [$insumoId]
        );
    }

    // Para select en formularios: muestra lote + vencimiento + cantidad
    public function paraSelect(int $insumoId): array
    {
        $rows = $this->query(
            "SELECT id,
                    CONCAT('Lote ', lote_proveedor,
                            ' — Vence: ', DATE_FORMAT(fecha_vencimiento, '%d/%m/%Y'),
                            ' — Disp: ', ROUND(cantidad_disponible,2), ' ', unidad_medida
                    ) AS label
            FROM stock_mp
            WHERE insumo_id = ? AND estado = 'disponible' AND cantidad_disponible > 0
            ORDER BY fecha_vencimiento ASC",
            [$insumoId]
        );
        return array_column($rows, 'label', 'id');
    }
}