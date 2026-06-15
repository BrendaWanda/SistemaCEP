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
                    (SELECT COUNT(*) FROM reg_liberacion_pt
                    WHERE sesion_id = s.id) AS tiene_liberacion,
                    -- Señales SPC detectadas (cualquier parámetro numérico SPC)
                    (SELECT COUNT(*) FROM reg_subgrupos_spc
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
    // Detalle de una sesión (cabecera + liberación PT).
    // Los registros dinámicos (valores, subgrupos SPC, inspección de
    // atributos) los provee SesionRegistroController::datosDinamicos().
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
}