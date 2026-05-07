<?php
// =============================================================================
//  SIACEP — Modelo: Insumo (Materia Prima / Empaque)
//  Archivo: app/Models/Insumo.php
// =============================================================================

namespace App\Models;

use App\Core\Model;

class Insumo extends Model
{
    protected string $table    = 'insumos';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'codigo','tipo','descripcion','unidad_medida',
        'esp_sabor_olor','esp_color','esp_descripcion_fisica',
        'esp_humedad_max','esp_densidad_min','esp_densidad_max',
        'esp_ph_min','esp_ph_max','esp_brix_max','esp_gluten_min','esp_impurezas_max',
        'vida_util_referencia','activo','creado_por'
    ];

    public const TIPOS = [
        'materia_prima'    => 'Materia Prima',
        'material_empaque' => 'Material de Empaque',
        'insumo_proceso'   => 'Insumo de Proceso',
        'otro'             => 'Otro',
    ];

    public function todosConStock(): array
    {
        return $this->query(
            "SELECT i.*,
                    COALESCE(SUM(s.cantidad_disponible), 0) AS stock_total,
                    COUNT(s.id) AS lotes_en_stock,
                    MIN(s.fecha_vencimiento) AS proximo_vencimiento
            FROM insumos i
            LEFT JOIN stock_mp s ON s.insumo_id = i.id AND s.estado = 'disponible'
            WHERE i.activo = 1
            GROUP BY i.id
            ORDER BY i.tipo, i.descripcion"
        );
    }

    public function codigoExiste(string $codigo, ?int $exceptoId = null): bool
    {
        if ($exceptoId) {
            return $this->exists('codigo = ? AND id != ?', [$codigo, $exceptoId]);
        }
        return $this->exists('codigo = ?', [$codigo]);
    }

    public function paraSelect(string $tipo = ''): array
    {
        if ($tipo) {
            $rows = $this->query(
                "SELECT id, CONCAT(codigo, ' — ', descripcion) AS label
                FROM insumos WHERE tipo = ? AND activo = 1 ORDER BY descripcion",
                [$tipo]
            );
        } else {
            $rows = $this->query(
                "SELECT id, CONCAT(codigo, ' — ', descripcion, ' (', unidad_medida, ')') AS label
                FROM insumos WHERE activo = 1 ORDER BY tipo, descripcion"
            );
        }
        return array_column($rows, 'label', 'id');
    }
}