<?php
namespace App\Models;

use App\Core\Model;

class Mantenimiento extends Model
{
    protected string $table    = 'mantenimientos';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'equipo_id','tipo','fecha_programada','fecha_inicio','fecha_fin',
        'duracion_min','descripcion_trabajo','falla_detectada',
        'causa_raiz','accion_correctiva','paro_produccion',
        'tiempo_paro_min','lote_afectado_id','resultado',
        'componentes_cambiados','costo_estimado',
        'ejecutado_por_id','supervisado_por_id','observaciones'
    ];

    public const TIPOS = [
        'preventivo'  => ['label'=>'Preventivo',  'badge'=>'badge-info'],
        'correctivo'  => ['label'=>'Correctivo',  'badge'=>'badge-danger'],
        'calibracion' => ['label'=>'Calibración', 'badge'=>'badge-purple'],
    ];

    public const RESULTADOS = [
        'completado'           => ['label'=>'Completado',           'badge'=>'badge-success'],
        'pendiente'            => ['label'=>'Pendiente',            'badge'=>'badge-muted'],
        'en_proceso'           => ['label'=>'En proceso',           'badge'=>'badge-warning'],
        'requiere_seguimiento' => ['label'=>'Requiere seguimiento',  'badge'=>'badge-danger'],
    ];

    public function todosConEquipo(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['equipo_id'])) {
            $where[]  = 'm.equipo_id = ?';
            $params[] = $filtros['equipo_id'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]  = 'm.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 'm.resultado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'DATE(m.fecha_inicio) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'DATE(m.fecha_inicio) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $whereStr = implode(' AND ', $where);

        return $this->query(
            "SELECT m.*,
                    e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre,
                    e.tipo   AS equipo_tipo,
                    l.nombre AS linea_nombre,
                    u1.nombre AS ejecutado_nombre,
                    u2.nombre AS supervisado_nombre
                FROM mantenimientos m
                JOIN equipos e ON e.id = m.equipo_id
                JOIN lineas_produccion l ON l.id = e.linea_id
                LEFT JOIN usuarios u1 ON u1.id = m.ejecutado_por_id
                LEFT JOIN usuarios u2 ON u2.id = m.supervisado_por_id
                WHERE {$whereStr}
                ORDER BY m.fecha_inicio DESC, m.creado_en DESC",
            $params
        );
    }

    public function conDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT m.*,
                    e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre,
                    e.tipo   AS equipo_tipo,
                    e.marca, e.modelo, e.serie,
                    l.nombre AS linea_nombre,
                    u1.nombre AS ejecutado_nombre,
                    u1.apellidos AS ejecutado_apellidos,
                    u2.nombre AS supervisado_nombre,
                    u2.apellidos AS supervisado_apellidos
                FROM mantenimientos m
                JOIN equipos e ON e.id = m.equipo_id
                JOIN lineas_produccion l ON l.id = e.linea_id
                LEFT JOIN usuarios u1 ON u1.id = m.ejecutado_por_id
                LEFT JOIN usuarios u2 ON u2.id = m.supervisado_por_id
                WHERE m.id = ?",
            [$id]
        );
    }

    public function alertas(int $diasAnticipacion = 15): array
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.codigo, e.nombre AS equipo_nombre,
                    e.tipo AS equipo_tipo, l.nombre AS linea_nombre,
                    e.fecha_prox_mant,
                    DATEDIFF(e.fecha_prox_mant, CURDATE()) AS dias_restantes
                FROM equipos e
                JOIN lineas_produccion l ON l.id = e.linea_id
                WHERE e.activo = 1
                AND e.fecha_prox_mant IS NOT NULL
                AND e.fecha_prox_mant <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY e.fecha_prox_mant ASC",
            [$diasAnticipacion]
        );
    }

    public function estadisticasMes(): array
    {
        $mes = date('Y-m');
        return [
            'total'       => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM mantenimientos
                    WHERE DATE_FORMAT(fecha_inicio,'%Y-%m') = ?",
                [$mes]
            ),
            'completados' => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM mantenimientos
                    WHERE resultado = 'completado'
                    AND DATE_FORMAT(fecha_inicio,'%Y-%m') = ?",
                [$mes]
            ),
            'horas_paro'  => (float)($this->db->fetchScalar(
                "SELECT COALESCE(SUM(tiempo_paro_min),0)
                    FROM mantenimientos
                    WHERE tipo = 'correctivo'
                    AND paro_produccion = 1
                    AND DATE_FORMAT(fecha_inicio,'%Y-%m') = ?",
                [$mes]
            ) / 60),
            'alertas'     => (int)$this->db->fetchScalar(
                "SELECT COUNT(*) FROM equipos
                    WHERE activo = 1
                    AND fecha_prox_mant IS NOT NULL
                    AND fecha_prox_mant <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)"
            ),
        ];
    }
}