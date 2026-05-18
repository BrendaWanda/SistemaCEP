<?php
namespace App\Models;

use App\Core\Model;

class SesionRegistro extends Model
{
    protected string $table    = 'sesiones_registro';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'lote_id','fecha','turno','supervisor_id',
        'nivel','hora_inicio_registro','hora_fin_registro','estado'
    ];

    public const ESTADOS = [
        'en_proceso' => ['label' => 'En proceso', 'badge' => 'badge-info'],
        'completo'   => ['label' => 'Completo',   'badge' => 'badge-success'],
        'revisado'   => ['label' => 'Revisado',   'badge' => 'badge-purple'],
    ];

    // Todas las sesiones con detalle del lote y producto
    public function todasConDetalle(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['lote_id'])) {
            $where[]  = 's.lote_id = ?';
            $params[] = $filtros['lote_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 's.fecha >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 's.fecha <= ?';
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 's.estado = ?';
            $params[] = $filtros['estado'];
        }

        $whereStr = implode(' AND ', $where);

        return $this->query(
            "SELECT s.*,
                    l.codigo_lote, l.turno AS lote_turno,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    u.nombre AS supervisor_nombre,
                    u.apellidos AS supervisor_apellidos,
                    -- Conteos de sub-registros
                    (SELECT COUNT(*) FROM reg_pesos_masa_cruda
                    WHERE sesion_id = s.id) AS total_pesos,
                    (SELECT COUNT(*) FROM reg_proceso_horneado
                    WHERE sesion_id = s.id) AS total_horneado,
                    (SELECT COUNT(*) FROM reg_control_envasado
                    WHERE sesion_id = s.id) AS total_envasado,
                    (SELECT COUNT(*) FROM reg_liberacion_pt
                    WHERE sesion_id = s.id) AS tiene_liberacion,
                    -- Señales SPC detectadas
                    (SELECT COUNT(*) FROM reg_pesos_masa_cruda
                    WHERE sesion_id = s.id
                    AND fuera_de_control = 1) AS senales_spc
            FROM sesiones_registro s
            JOIN lotes_produccion l ON l.id = s.lote_id
            JOIN productos p ON p.id = l.producto_id
            JOIN usuarios u ON u.id = s.supervisor_id
            WHERE {$whereStr}
            ORDER BY s.fecha DESC, s.creado_en DESC",
            $params
        );
    }

    // Detalle completo de una sesión con todos los sub-registros
    public function conSubregistros(int $id): array|false
    {
        $sesion = $this->db->fetchOne(
            "SELECT s.*,
                    l.codigo_lote, l.nivel,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    p.peso_nominal_g, p.lse_g, p.lie_g,
                    p.tolerancia_pct,
                    u.nombre AS supervisor_nombre,
                    u.apellidos AS supervisor_apellidos
            FROM sesiones_registro s
            JOIN lotes_produccion l ON l.id = s.lote_id
            JOIN productos p ON p.id = l.producto_id
            JOIN usuarios u ON u.id = s.supervisor_id
            WHERE s.id = ?",
            [$id]
        );

        if (!$sesion) return false;

        // Cargar todos los sub-registros
        $sesion['amasados'] = $this->db->fetchAll(
            "SELECT a.*, u.nombre AS registrado_nombre
            FROM reg_proceso_amasado a
            JOIN usuarios u ON u.id = a.registrado_por_id
            WHERE a.sesion_id = ? ORDER BY a.hora",
            [$id]
        );

        $sesion['pesos'] = $this->db->fetchAll(
            "SELECT p.*, u.nombre AS registrado_nombre,
                    op.nombre AS operario_nombre
            FROM reg_pesos_masa_cruda p
            JOIN usuarios u ON u.id = p.registrado_por_id
            LEFT JOIN usuarios op ON op.id = p.operario_id
            WHERE p.sesion_id = ? ORDER BY p.hora",
            [$id]
        );

        $sesion['horneados'] = $this->db->fetchAll(
            "SELECT h.*, u.nombre AS registrado_nombre
            FROM reg_proceso_horneado h
            JOIN usuarios u ON u.id = h.registrado_por_id
            WHERE h.sesion_id = ? ORDER BY h.hora",
            [$id]
        );

        $sesion['envasados'] = $this->db->fetchAll(
            "SELECT e.*, u.nombre AS registrado_nombre
            FROM reg_control_envasado e
            JOIN usuarios u ON u.id = e.registrado_por_id
            WHERE e.sesion_id = ? ORDER BY e.hora",
            [$id]
        );

        $sesion['analisis_pt'] = $this->db->fetchAll(
            "SELECT a.*, u.nombre AS registrado_nombre
            FROM reg_analisis_pt a
            JOIN usuarios u ON u.id = a.registrado_por_id
            WHERE a.sesion_id = ? ORDER BY a.hora",
            [$id]
        );

        $sesion['liberacion'] = $this->db->fetchOne(
            "SELECT lib.*,
                    u1.nombre AS supervisor_cal_nombre,
                    u1.apellidos AS supervisor_cal_apellidos,
                    u2.nombre AS nutricion_nombre,
                    u3.nombre AS jefe_prod_nombre
            FROM reg_liberacion_pt lib
            JOIN usuarios u1 ON u1.id = lib.supervisor_calidad_id
            LEFT JOIN usuarios u2 ON u2.id = lib.encargado_nutricion_id
            LEFT JOIN usuarios u3 ON u3.id = lib.jefe_produccion_id
            WHERE lib.sesion_id = ?",
            [$id]
        );

        return $sesion;
    }

    // Sesión activa para un lote (si existe)
    public function porLote(int $loteId): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM sesiones_registro
                WHERE lote_id = ? ORDER BY creado_en DESC LIMIT 1",
            [$loteId]
        );
    }
}