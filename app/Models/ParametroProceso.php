<?php
// =============================================================================
//  SIACEP — Modelo: Parámetro de Proceso
//  Archivo: app/Models/ParametroProceso.php
// =============================================================================

namespace App\Models;

use App\Core\Model;

class ParametroProceso extends Model
{
    protected string $table    = 'parametros_proceso';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'producto_id','etapa','nombre','unidad','tipo_dato',
        'valor_nominal','valor_min','valor_max',
        'es_variable_spc','tamanio_subgrupo',
        'opciones_json','obligatorio','orden_display','activo','creado_por'
    ];

    // Etapas del proceso (para UI y filtros)
    public const ETAPAS = [
        'amasado'            => 'Amasado / Mezclado',
        'formado'            => 'Formado / Boleado',
        'fermentacion'       => 'Fermentación',
        'horneado'           => 'Horneado',
        'envasado'           => 'Envasado',
        'producto_terminado' => 'Producto Terminado',
    ];

    public const TIPOS = [
        'numerico'   => 'Numérico',
        'texto'      => 'Texto libre',
        'booleano'   => 'Sí / No',
        'seleccion'  => 'Lista de opciones',
    ];

    // Parámetros de un producto agrupados por etapa
    public function porProductoAgrupado(int $productoId): array
    {
        $rows = $this->query(
            "SELECT * FROM parametros_proceso
             WHERE producto_id = ? AND activo = 1
             ORDER BY etapa, orden_display, nombre",
            [$productoId]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['etapa']][] = $row;
        }
        return $grouped;
    }

    // Solo los parámetros SPC de un producto (para el M6)
    public function variablesSPC(int $productoId): array
    {
        return $this->query(
            "SELECT * FROM parametros_proceso
             WHERE producto_id = ? AND es_variable_spc = 1 AND activo = 1
             ORDER BY etapa, orden_display",
            [$productoId]
        );
    }

    // Para construir el formulario dinámico del M2
    public function paraFormulario(int $productoId): array
    {
        $rows = $this->query(
            "SELECT pp.*,
                    p.peso_nominal_g, p.lse_g, p.lie_g, p.tolerancia_pct
             FROM parametros_proceso pp
             JOIN productos p ON p.id = pp.producto_id
             WHERE pp.producto_id = ? AND pp.activo = 1
             ORDER BY pp.etapa, pp.orden_display",
            [$productoId]
        );

        // Decodificar opciones JSON
        foreach ($rows as &$row) {
            if ($row['opciones_json']) {
                $row['opciones'] = json_decode($row['opciones_json'], true);
            }
        }
        return $rows;
    }

    public function siguienteOrden(int $productoId, string $etapa): int
    {
        $max = $this->db->fetchScalar(
            "SELECT MAX(orden_display) FROM parametros_proceso
             WHERE producto_id = ? AND etapa = ?",
            [$productoId, $etapa]
        );
        return ($max ?? 0) + 10;
    }
}