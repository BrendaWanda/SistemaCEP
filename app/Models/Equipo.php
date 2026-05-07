<?php
// =============================================================================
//  SIACEP — Modelo: Equipo / Maquinaria
//  Archivo: app/Models/Equipo.php
// =============================================================================

namespace App\Models;

use App\Core\Model;

class Equipo extends Model
{
    protected string $table    = 'equipos';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'linea_id','codigo','nombre','tipo','marca','modelo','serie',
        'requiere_calibracion','frecuencia_calibr_dias','frecuencia_mant_dias',
        'fecha_ultima_calibr','fecha_prox_calibr',
        'fecha_ultimo_mant','fecha_prox_mant',
        'activo','observaciones','creado_por'
    ];

    public const TIPOS = [
        'horno'       => 'Horno',
        'divisora'    => 'Divisora de masa',
        'amasadora'   => 'Amasadora',
        'envasadora'  => 'Envasadora',
        'balanza'     => 'Balanza',
        'termometro'  => 'Termómetro',
        'higrómetro'  => 'Higrómetro',
        'otro'        => 'Otro',
    ];

    // Todos los equipos con su línea y estado de calibración
    public function todosConEstado(): array
    {
        return $this->query(
            "SELECT e.*, l.nombre AS linea_nombre,
                    CASE
                        WHEN e.requiere_calibracion = 0 THEN 'no_aplica'
                        WHEN e.fecha_prox_calibr IS NULL THEN 'sin_registro'
                        WHEN e.fecha_prox_calibr < CURDATE() THEN 'vencida'
                        WHEN e.fecha_prox_calibr <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proxima'
                        ELSE 'vigente'
                    END AS estado_calibracion,
                    DATEDIFF(e.fecha_prox_calibr, CURDATE()) AS dias_para_calibr,
                    CASE
                        WHEN e.fecha_prox_mant IS NULL THEN 'sin_registro'
                        WHEN e.fecha_prox_mant < CURDATE() THEN 'vencido'
                        WHEN e.fecha_prox_mant <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 'proximo'
                        ELSE 'vigente'
                    END AS estado_mantenimiento
             FROM equipos e
             JOIN lineas_produccion l ON l.id = e.linea_id
             WHERE e.activo = 1
             ORDER BY l.nombre, e.tipo, e.nombre"
        );
    }

    // Equipos que necesitan calibración urgente (vencida o próxima en 30 días)
    public function alertasCalibración(): array
    {
        return $this->query(
            "SELECT e.*, l.nombre AS linea_nombre,
                    DATEDIFF(e.fecha_prox_calibr, CURDATE()) AS dias_restantes
             FROM equipos e
             JOIN lineas_produccion l ON l.id = e.linea_id
             WHERE e.requiere_calibracion = 1
               AND e.activo = 1
               AND (e.fecha_prox_calibr IS NULL
                    OR e.fecha_prox_calibr <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
             ORDER BY e.fecha_prox_calibr ASC"
        );
    }

    public function paraSelect(int $lineaId): array
    {
        $rows = $this->query(
            "SELECT id, CONCAT(codigo, ' — ', nombre) AS label
             FROM equipos WHERE linea_id = ? AND activo = 1 ORDER BY nombre",
            [$lineaId]
        );
        return array_column($rows, 'label', 'id');
    }

    // Recalcular próximas fechas al registrar mantenimiento o calibración
    public function actualizarFechaProxMant(int $id, string $fechaEjecutado): void
    {
        $equipo = $this->find($id);
        if (!$equipo || !$equipo['frecuencia_mant_dias']) return;

        $proxFecha = date('Y-m-d', strtotime($fechaEjecutado
            . ' +' . $equipo['frecuencia_mant_dias'] . ' days'));

        $this->execute(
            "UPDATE equipos SET fecha_ultimo_mant = ?, fecha_prox_mant = ? WHERE id = ?",
            [$fechaEjecutado, $proxFecha, $id]
        );
    }

    public function actualizarFechaProxCalibr(int $id, string $fechaCalibr): void
    {
        $equipo = $this->find($id);
        if (!$equipo || !$equipo['frecuencia_calibr_dias']) return;

        $proxFecha = date('Y-m-d', strtotime($fechaCalibr
            . ' +' . $equipo['frecuencia_calibr_dias'] . ' days'));

        $this->execute(
            "UPDATE equipos SET fecha_ultima_calibr = ?, fecha_prox_calibr = ? WHERE id = ?",
            [$fechaCalibr, $proxFecha, $id]
        );
    }
}