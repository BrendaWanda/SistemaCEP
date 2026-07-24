<?php
// =============================================================================
//  SIACEP — Modelo: Parámetro de Insumo (dinámico, por insumo)
//  Archivo: app/Models/ParametroInsumo.php
//
//  Espejo de ParametroProceso.php — mismo patrón: cada insumo define qué
//  parámetros se le exigen en recepción (M1), en vez de columnas fijas
//  que se pedían a todos los insumos por igual (ej. gluten a un
//  saborizante, que nunca aplicó).
// =============================================================================

namespace App\Models;

use App\Core\Model;

class ParametroInsumo extends Model
{
    protected string $table    = 'parametros_insumo';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'insumo_id','nombre','unidad','tipo_dato',
        'valor_min','valor_max','opciones_json',
        'obligatorio','orden_display','activo','creado_por'
    ];

    // Mismos tipos que ParametroProceso::TIPOS, por consistencia —
    // el formulario de recepción (M1) interpreta el valor según esto,
    // igual que ya hace RegistroDinamicoController con reg_valores_simples.
    public const TIPOS = [
        'numerico'  => 'Numérico',
        'texto'     => 'Texto libre',
        'si_no'     => 'Sí / No',
        'seleccion' => 'Lista de opciones',
    ];

    // Todos los parámetros activos de un insumo, en orden de despliegue
    public function porInsumo(int $insumoId): array
    {
        $rows = $this->query(
            "SELECT * FROM parametros_insumo
            WHERE insumo_id = ? AND activo = 1
            ORDER BY orden_display, nombre",
            [$insumoId]
        );
        foreach ($rows as &$row) {
            if ($row['opciones_json']) {
                $row['opciones'] = json_decode($row['opciones_json'], true);
            }
        }
        return $rows;
    }

    public function siguienteOrden(int $insumoId): int
    {
        $max = $this->db->fetchScalar(
            "SELECT MAX(orden_display) FROM parametros_insumo WHERE insumo_id = ?",
            [$insumoId]
        );
        return ($max ?? 0) + 10;
    }
}