<?php
namespace App\Models;

use App\Core\Model;

class LoteProduccion extends Model
{
    protected string $table    = 'lotes_produccion';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'codigo_lote','producto_id','receta_id','numero_recetas',
        'fecha_produccion','turno','supervisor_id','nivel',
        'rendimiento_teorico_total','rendimiento_real_total',
        'diferencia_unidades','porcentaje_rendimiento',
        'merma_producto_kg','merma_envase_kg','merma_reproceso_kg',
        'merma_no_conforme_kg','merma_quemado_kg',
        'fecha_elaboracion','fecha_vencimiento',
        'estado','observaciones','cronograma_produccion_id','creado_por'
    ];

    public const ESTADOS = [
        'en_proceso' => ['label'=>'En proceso', 'badge'=>'badge-info'],
        'cerrado'    => ['label'=>'Cerrado',     'badge'=>'badge-muted'],
        'liberado'   => ['label'=>'Liberado',    'badge'=>'badge-success'],
        'cuarentena' => ['label'=>'Cuarentena',  'badge'=>'badge-warning'],
        'rechazado'  => ['label'=>'Rechazado',   'badge'=>'badge-danger'],
    ];

    public const TURNOS = ['mañana'=>'Mañana','tarde'=>'Tarde','noche'=>'Noche'];

    // Todos los lotes con producto y estado
    public function todosConDetalle(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $where[]  = 'l.producto_id = ?';
            $params[] = $filtros['producto_id'];
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 'l.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'l.fecha_produccion >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'l.fecha_produccion <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $whereStr = implode(' AND ', $where);

        return $this->query(
            "SELECT l.*,
                    p.nombre  AS producto_nombre,
                    p.codigo  AS producto_codigo,
                    p.unidades_por_receta,
                    p.peso_nominal_g,
                    r.nombre  AS receta_nombre,
                    r.version AS receta_version,
                    u.nombre  AS supervisor_nombre,
                    u.apellidos AS supervisor_apellidos,
                    li.nombre AS linea_nombre,
                    (SELECT COUNT(*) FROM consumo_mp_por_lote
                    WHERE lote_id = l.id) AS total_insumos_usados
            FROM lotes_produccion l
            JOIN productos p  ON p.id  = l.producto_id
            JOIN recetas r    ON r.id  = l.receta_id
            JOIN usuarios u   ON u.id  = l.supervisor_id
            JOIN lineas_produccion li ON li.id = p.linea_id
            WHERE {$whereStr}
            ORDER BY l.fecha_produccion DESC, l.creado_en DESC",
            $params
        );
    }

    // Detalle completo de un lote por código
    public function porCodigo(string $codigo): array|false
    {
        return $this->db->fetchOne(
            "SELECT l.*,
                    p.nombre AS producto_nombre, p.codigo AS producto_codigo,
                    p.peso_nominal_g, p.lse_g, p.lie_g,
                    p.unidades_por_receta, p.vida_util_dias,
                    r.nombre AS receta_nombre, r.version AS receta_version,
                    u.nombre AS supervisor_nombre, u.apellidos AS supervisor_apellidos,
                    li.nombre AS linea_nombre, li.codigo AS linea_codigo
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            JOIN recetas r   ON r.id = l.receta_id
            JOIN usuarios u  ON u.id = l.supervisor_id
            JOIN lineas_produccion li ON li.id = p.linea_id
            WHERE l.codigo_lote = ?",
            [$codigo]
        );
    }

    // Lotes activos para select en M2
    public function activosParaSelect(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT l.id, l.codigo_lote,
                    CONCAT(l.codigo_lote,' — ',p.nombre,' (',l.turno,')') AS label
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            WHERE l.estado = 'en_proceso'
            ORDER BY l.fecha_produccion DESC"
        );
        return array_column($rows, 'label', 'id');
    }

    // Generar código de lote: formato NroDia-NroMes Ej: 29-01
    // Basado en el archivo SEGUIMIENTO_RENDIMIENTOS_HORNEADOS
    public function generarCodigo(string $fecha): string
    {
        $dia = (int)date('d', strtotime($fecha));
        $mes = (int)date('m', strtotime($fecha));

        // Número correlativo del día en el mes (cuántos lotes ese día)
        $countHoy = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM lotes_produccion
            WHERE fecha_produccion = ?",
            [$fecha]
        );

        // Formato: DIA-MES-CORRELATIVO
        $correlativo = $countHoy + 1;
        return str_pad($dia, 2, '0', STR_PAD_LEFT)
            . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT)
            . ($correlativo > 1 ? '-'.$correlativo : '');
    }

    // Calcular y actualizar rendimiento del lote
    public function actualizarRendimiento(int $id, array $datos): void
    {
        $lote = $this->find($id);
        if (!$lote) return;

        $teorico = (int)$datos['rendimiento_teorico_total'];
        $real    = (int)$datos['rendimiento_real_total'];
        $pct     = $teorico > 0 ? round(($real / $teorico) * 100, 2) : 0;

        $this->update($id, [
            'rendimiento_teorico_total' => $teorico,
            'rendimiento_real_total'    => $real,
            'diferencia_unidades'       => $real - $teorico,
            'porcentaje_rendimiento'    => $pct,
            'merma_producto_kg'         => $datos['merma_producto_kg'] ?? 0,
            'merma_envase_kg'           => $datos['merma_envase_kg'] ?? 0,
            'merma_reproceso_kg'        => $datos['merma_reproceso_kg'] ?? 0,
            'merma_no_conforme_kg'      => $datos['merma_no_conforme_kg'] ?? 0,
            'merma_quemado_kg'          => $datos['merma_quemado_kg'] ?? 0,
        ]);
    }

    // Consumo de MP del lote
    public function consumoMP(int $loteId): array
    {
        return $this->query(
            "SELECT c.*,
                    i.descripcion AS insumo_nombre,
                    i.codigo AS insumo_codigo,
                    i.unidad_medida AS insumo_unidad,
                    s.fecha_vencimiento, s.lote_proveedor AS stock_lote
            FROM consumo_mp_por_lote c
            JOIN insumos i ON i.id = c.insumo_id
            JOIN stock_mp s ON s.id = c.stock_mp_id
            WHERE c.lote_id = ?
            ORDER BY i.tipo, i.descripcion",
            [$loteId]
        );
    }

    // Estadísticas para el dashboard M4
    public function estadisticasMes(): array
    {
        $mes = date('Y-m');
        return [
            'total_lotes'    => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion
                WHERE DATE_FORMAT(fecha_produccion,'%Y-%m') = ?", [$mes]
            ),
            'liberados'      => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion
                WHERE estado = 'liberado'
                AND DATE_FORMAT(fecha_produccion,'%Y-%m') = ?", [$mes]
            ),
            'en_proceso'     => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE estado = 'en_proceso'"
            ),
            'cuarentena'     => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE estado = 'cuarentena'"
            ),
            'rend_promedio'  => (float)$this->db->fetchScalar(
                "SELECT AVG(porcentaje_rendimiento) FROM lotes_produccion
                WHERE porcentaje_rendimiento > 0
                AND DATE_FORMAT(fecha_produccion,'%Y-%m') = ?", [$mes]
            ) ?? 0,
        ];
    }
}