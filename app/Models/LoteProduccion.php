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
        'merma_envase_kg',
        'merma_panes_mordidos_kg','merma_panes_besados_kg',
        'merma_panes_quemados_kg','merma_panes_blancos_kg',
        'merma_panes_sobrados_kg','merma_aspecto_no_deseado_kg',
        'fecha_elaboracion','fecha_vencimiento',
        'estado','observaciones','creado_por'
    ];

    public const ESTADOS = [
        'en_proceso' => ['label'=>'En proceso', 'badge'=>'badge-info'],
        'cerrado'    => ['label'=>'Cerrado',     'badge'=>'badge-muted'],
        'liberado'   => ['label'=>'Liberado',    'badge'=>'badge-success'],
        'cuarentena' => ['label'=>'Cuarentena',  'badge'=>'badge-warning'],
        'rechazado'  => ['label'=>'Rechazado',   'badge'=>'badge-danger'],
    ];

    public const TURNOS = ['mañana'=>'Mañana','tarde'=>'Tarde','noche'=>'Noche'];

    // Categorías de merma (kg) — usadas en rendimiento.php, ver_lote.php e imprimir.php
    public const MERMAS = [
        'merma_panes_mordidos_kg'     => ['label' => 'Panes mordidos',            'hint' => 'Dañados por mordidas durante la manipulación'],
        'merma_panes_besados_kg'      => ['label' => 'Panes besados',             'hint' => 'Pegados entre sí durante el horneado'],
        'merma_panes_quemados_kg'     => ['label' => 'Panes quemados',            'hint' => 'Quemados en el horno'],
        'merma_panes_blancos_kg'      => ['label' => 'Panes blancos',             'hint' => 'Mal cocidos / subhorneados'],
        'merma_panes_sobrados_kg'     => ['label' => 'Panes sobrados',            'hint' => 'Excedente de producción'],
        'merma_aspecto_no_deseado_kg' => ['label' => 'Panes con aspecto no deseado', 'hint' => 'Defectos de forma o apariencia'],
        'merma_envase_kg'             => ['label' => 'Merma de bobina',           'hint' => 'Material de empaque desperdiciado'],
    ];

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
    /**
     * Cuántos registros dependen del lote (para el mensaje de
     * confirmación antes de eliminarlo).
     */
    public function contarDependencias(int $loteId): array
    {
        $n = fn(string $sql) => (int)$this->db->fetchScalar($sql, [$loteId]);
        return [
            'sesiones'     => $n("SELECT COUNT(*) FROM sesiones_registro WHERE lote_id = ?"),
            'subgrupos'    => $n("SELECT COUNT(*) FROM reg_subgrupos_spc sg
                                  JOIN sesiones_registro s ON s.id = sg.sesion_id
                                  WHERE s.lote_id = ?"),
            'inspecciones' => $n("SELECT COUNT(*) FROM reg_inspeccion_atributos WHERE lote_id = ?"),
            'senales'      => $n("SELECT COUNT(*) FROM spc_senales_detectadas WHERE lote_id = ?"),
            'consumos'     => $n("SELECT COUNT(*) FROM consumo_mp_por_lote WHERE lote_id = ?"),
        ];
    }

    /**
     * Elimina el lote y TODO lo que depende de él, en una sola
     * transacción y en el orden que exigen las claves foráneas:
     *  - la MP consumida se devuelve al stock antes de borrar el consumo;
     *  - sesiones de registro con sus subgrupos SPC, valores simples,
     *    inspecciones de atributos, liberación y señales SPC;
     *  - tiempos de producción y eventos de trazabilidad del lote;
     *  - los mantenimientos que lo tenían como "lote afectado" se
     *    conservan, solo se desvinculan (lote_afectado_id = NULL).
     */
    public function eliminarConDependencias(int $loteId, string $codigoLote): void
    {
        $this->db->transaction(function($db) use ($loteId, $codigoLote) {
            // 1. Devolver al stock la MP consumida y borrar los consumos
            $consumos = $db->fetchAll(
                "SELECT id, stock_mp_id, cantidad_usada FROM consumo_mp_por_lote WHERE lote_id = ?",
                [$loteId]
            );
            foreach ($consumos as $c) {
                $db->execute(
                    "UPDATE stock_mp
                    SET cantidad_disponible = cantidad_disponible + ?,
                        estado = IF(estado = 'agotado', 'disponible', estado)
                    WHERE id = ?",
                    [$c['cantidad_usada'], $c['stock_mp_id']]
                );
            }
            $db->execute("DELETE FROM consumo_mp_por_lote WHERE lote_id = ?", [$loteId]);

            // 2. Registros de proceso (señales antes que subgrupos por FK)
            $sub = "SELECT id FROM sesiones_registro WHERE lote_id = ?";
            $db->execute("DELETE FROM spc_senales_detectadas WHERE lote_id = ? OR sesion_id IN ($sub)", [$loteId, $loteId]);
            $db->execute("DELETE FROM reg_subgrupos_spc WHERE sesion_id IN ($sub)", [$loteId]);
            $db->execute("DELETE FROM reg_valores_simples WHERE sesion_id IN ($sub)", [$loteId]);
            $db->execute("DELETE FROM reg_inspeccion_atributos WHERE lote_id = ? OR sesion_id IN ($sub)", [$loteId, $loteId]);
            $db->execute("DELETE FROM reg_liberacion_pt WHERE sesion_id IN ($sub)", [$loteId]);
            $db->execute("DELETE FROM sesiones_registro WHERE lote_id = ?", [$loteId]);

            // 3. Otros vínculos del lote
            $db->execute("DELETE FROM tiempos_produccion WHERE lote_id = ?", [$loteId]);
            $db->execute("DELETE FROM trazabilidad_eventos WHERE codigo_lote = ?", [$codigoLote]);
            $db->execute("UPDATE mantenimientos SET lote_afectado_id = NULL WHERE lote_afectado_id = ?", [$loteId]);

            // 4. El lote
            $db->execute("DELETE FROM lotes_produccion WHERE id = ?", [$loteId]);
        });
    }

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

    // Hallazgo de auditoría: generarCodigo() lee un COUNT(*) y luego el
    // controller hace el INSERT por separado — si dos personas abren un
    // lote para la MISMA fecha casi al mismo tiempo, ambas pueden leer el
    // mismo conteo antes de que cualquiera confirme su INSERT, generando
    // el mismo codigo_lote (que tiene UNIQUE KEY en la BD). Esto no se
    // arregla solo con una validación previa (el mismo problema de
    // "verificar y luego usar") — se necesita reintentar el INSERT con
    // el siguiente correlativo si choca por duplicado.
    //
    // Este método reemplaza el patrón "generarCodigo() + create()" suelto:
    // genera el código y crea el lote dentro del mismo intento, reintenta
    // hasta 5 veces con el siguiente correlativo si hay colisión.
    public function crearConCodigoSeguro(string $fecha, array $data): array
    {
        $dia = (int)date('d', strtotime($fecha));
        $mes = (int)date('m', strtotime($fecha));

        for ($intento = 0; $intento < 5; $intento++) {
            $countHoy    = (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM lotes_produccion WHERE fecha_produccion = ?",
                [$fecha]
            );
            $correlativo = $countHoy + 1 + $intento;
            $codigo      = str_pad($dia, 2, '0', STR_PAD_LEFT)
                . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT)
                . ($correlativo > 1 ? '-'.$correlativo : '');

            try {
                $id = $this->create(array_merge($data, ['codigo_lote' => $codigo]));
                return ['id' => $id, 'codigo' => $codigo];
            } catch (\PDOException $e) {
                // 23000 = violación de restricción de integridad (duplicado)
                if ($e->getCode() !== '23000' || $intento === 4) {
                    throw $e;
                }
                // Colisión — se reintenta con el siguiente correlativo
            }
        }

        throw new \RuntimeException('No se pudo generar un código de lote único.');
    }

    // Calcular y actualizar rendimiento del lote
    public function actualizarRendimiento(int $id, array $datos): void
    {
        $lote = $this->find($id);
        if (!$lote) return;

        $teorico = (int)$datos['rendimiento_teorico_total'];
        $real    = (int)$datos['rendimiento_real_total'];
        $pct     = $teorico > 0 ? round(($real / $teorico) * 100, 2) : 0;

        $update = [
            'rendimiento_teorico_total' => $teorico,
            'rendimiento_real_total'    => $real,
            'diferencia_unidades'       => $real - $teorico,
            'porcentaje_rendimiento'    => $pct,
        ];

        foreach (array_keys(self::MERMAS) as $campo) {
            $update[$campo] = $datos[$campo] ?? 0;
        }

        // AGREGADO: observaciones existía en la BD y en el $fillable del
        // modelo, pero ningún controller la leía ni la guardaba nunca —
        // se conserva el valor existente si no se manda uno nuevo, en vez
        // de borrarlo por accidente.
        if (array_key_exists('observaciones', $datos)) {
            $update['observaciones'] = $datos['observaciones'];
        }

        $this->update($id, $update);
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