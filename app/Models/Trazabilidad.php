<?php
namespace App\Models;

use App\Core\Model;

class Trazabilidad extends Model
{
    protected string $table = 'trazabilidad_eventos';
    protected string $pk    = 'id';

    // Árbol completo de trazabilidad por código de lote
    public function porLote(string $codigoLote): array|false
    {
        // 1. Datos del lote
        $lote = $this->db->fetchOne(
            "SELECT l.*,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    p.peso_nominal_g, p.lse_g, p.lie_g,
                    r.nombre AS receta_nombre,
                    r.version AS receta_version,
                    u.nombre AS supervisor_nombre,
                    u.apellidos AS supervisor_apellidos,
                    li.nombre AS linea_nombre
            FROM lotes_produccion l
            JOIN productos p  ON p.id  = l.producto_id
            JOIN recetas r    ON r.id  = l.receta_id
            JOIN usuarios u   ON u.id  = l.supervisor_id
            JOIN lineas_produccion li ON li.id = p.linea_id
            WHERE l.codigo_lote = ?",
            [$codigoLote]
        );

        if (!$lote) return false;

        // 2. MP consumida con trazabilidad hacia proveedor
        $lote['mp_consumida'] = $this->db->fetchAll(
            "SELECT c.*,
                    i.descripcion AS insumo_nombre,
                    i.codigo AS insumo_codigo,
                    i.tipo AS insumo_tipo,
                    s.fecha_vencimiento, s.lote_proveedor,
                    s.cantidad_inicial AS stock_inicial,
                    r.codigo_recepcion,
                    r.fecha_analisis AS fecha_recepcion,
                    r.decision AS decision_recepcion,
                    prov.nombre AS proveedor_nombre,
                    prov.procedencia AS proveedor_procedencia
            FROM consumo_mp_por_lote c
            JOIN insumos i ON i.id = c.insumo_id
            JOIN stock_mp s ON s.id = c.stock_mp_id
            JOIN recepciones_mp r ON r.id = s.recepcion_id
            JOIN proveedores prov ON prov.id = r.proveedor_id
            WHERE c.lote_id = ?
            ORDER BY i.tipo, i.descripcion",
            [$lote['id']]
        );

        // 3. Sesiones de registro de proceso
        $lote['sesiones'] = $this->db->fetchAll(
            "SELECT s.*,
                    u.nombre AS supervisor_nombre,
                    -- Conteos
                    (SELECT COUNT(*) FROM reg_pesos_masa_cruda
                    WHERE sesion_id = s.id) AS total_pesos,
                    (SELECT COUNT(*) FROM reg_pesos_masa_cruda
                    WHERE sesion_id = s.id
                    AND fuera_de_control = 1) AS senales_spc,
                    (SELECT COUNT(*) FROM reg_proceso_horneado
                    WHERE sesion_id = s.id) AS total_horneado,
                    (SELECT COUNT(*) FROM reg_control_envasado
                    WHERE sesion_id = s.id) AS total_envasado
                FROM sesiones_registro s
                JOIN usuarios u ON u.id = s.supervisor_id
                WHERE s.lote_id = ?
                ORDER BY s.fecha ASC",
            [$lote['id']]
        );

        // 4. Liberación PT
        $lote['liberacion'] = $this->db->fetchOne(
            "SELECT lib.*,
                    u1.nombre AS supervisor_cal_nombre,
                    u1.apellidos AS supervisor_cal_apellidos,
                    u2.nombre AS jefe_prod_nombre
                FROM reg_liberacion_pt lib
                JOIN sesiones_registro s ON s.id = lib.sesion_id
                LEFT JOIN usuarios u1 ON u1.id = lib.supervisor_calidad_id
                LEFT JOIN usuarios u2 ON u2.id = lib.jefe_produccion_id
                WHERE s.lote_id = ?
                ORDER BY lib.hora DESC LIMIT 1",
            [$lote['id']]
        );

        // 5. Eventos de trazabilidad
        $lote['eventos'] = $this->db->fetchAll(
            "SELECT t.*, u.nombre AS usuario_nombre
                FROM trazabilidad_eventos t
                LEFT JOIN usuarios u ON u.id = t.usuario_id
                WHERE t.codigo_lote = ?
                ORDER BY t.creado_en ASC",
            [$codigoLote]
        );

        // 6. Señales SPC detectadas
        $lote['senales_spc'] = $this->db->fetchAll(
            "SELECT s.*,
                u.nombre AS resuelta_por_nombre
            FROM spc_senales_detectadas s
            LEFT JOIN usuarios u ON u.id = s.resuelta_por_id
            WHERE s.lote_id = ?
            ORDER BY s.creado_en ASC",
            [$lote['id']]
        );

        return $lote;
    }

    // Trazabilidad inversa — dado un lote de MP, qué lotes de PT lo usaron
    public function porInsumo(string $codigoInsumo): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT
                    l.codigo_lote, l.fecha_produccion, l.estado,
                    p.nombre AS producto_nombre,
                    i.descripcion AS insumo_nombre,
                    s.lote_proveedor, s.fecha_vencimiento,
                    c.cantidad_usada, c.unidad_medida,
                    prov.nombre AS proveedor_nombre
                FROM consumo_mp_por_lote c
                JOIN lotes_produccion l ON l.id = c.lote_id
                JOIN productos p ON p.id = l.producto_id
                JOIN insumos i ON i.id = c.insumo_id
                JOIN stock_mp s ON s.id = c.stock_mp_id
                JOIN recepciones_mp r ON r.id = s.recepcion_id
                JOIN proveedores prov ON prov.id = r.proveedor_id
                WHERE i.codigo = ?
                ORDER BY l.fecha_produccion DESC",
            [$codigoInsumo]
        );
    }

    // Búsqueda general por código de lote o insumo
    public function buscar(string $termino): array
    {
        $termino = '%'.$termino.'%';
        return $this->db->fetchAll(
            "SELECT l.codigo_lote, l.fecha_produccion, l.estado,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo
                FROM lotes_produccion l
                JOIN productos p ON p.id = l.producto_id
                WHERE l.codigo_lote LIKE ?
                OR p.nombre LIKE ?
                OR p.codigo LIKE ?
                ORDER BY l.fecha_produccion DESC
                LIMIT 20",
            [$termino, $termino, $termino]
        );
    }
}