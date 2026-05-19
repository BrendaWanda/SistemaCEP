<?php
namespace App\Services;

use App\Core\Database;

class OeeService
{
    private Database $db;

    // Horas de turno por día (configurable)
    const HORAS_TURNO_DIA = 8;
    const DIAS_LABORABLES  = 5; // por semana

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // OEE completo para un período
    public function calcularOee(string $fechaDesde, string $fechaHasta): array
    {
        $disponibilidad = $this->calcularDisponibilidad($fechaDesde, $fechaHasta);
        $rendimiento    = $this->calcularRendimiento($fechaDesde, $fechaHasta);
        $calidad        = $this->calcularCalidad($fechaDesde, $fechaHasta);

        $oee = ($disponibilidad['pct'] / 100)
             * ($rendimiento['pct'] / 100)
             * ($calidad['pct'] / 100)
             * 100;

        return [
            'oee'           => round($oee, 2),
            'disponibilidad'=> $disponibilidad,
            'rendimiento'   => $rendimiento,
            'calidad'       => $calidad,
            'clasificacion' => $this->clasificarOee($oee),
            'fecha_desde'   => $fechaDesde,
            'fecha_hasta'   => $fechaHasta,
        ];
    }

    // D = (Tiempo planificado - Paros) / Tiempo planificado
    public function calcularDisponibilidad(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        // Días hábiles en el período
        $dias = $this->diasHabiles($fechaDesde, $fechaHasta);
        $tiempoPlanMin = $dias * self::HORAS_TURNO_DIA * 60;

        // Paros por mantenimiento correctivo
        $parosMin = (float)$this->db->fetchScalar(
            "SELECT COALESCE(SUM(tiempo_paro_min), 0)
                FROM mantenimientos
                WHERE paro_produccion = 1
                AND resultado = 'completado'
                AND DATE(fecha_inicio) BETWEEN ? AND ?",
            [$fechaDesde, $fechaHasta]
        );

        $tiempoOpMin = max(0, $tiempoPlanMin - $parosMin);
        $pct = $tiempoPlanMin > 0
            ? round(($tiempoOpMin / $tiempoPlanMin) * 100, 2)
            : 0;

        return [
            'pct'              => $pct,
            'tiempo_plan_min'  => $tiempoPlanMin,
            'paros_min'        => $parosMin,
            'tiempo_op_min'    => $tiempoOpMin,
            'dias_habiles'     => $dias,
        ];
    }

    // R = Unidades reales / Unidades teóricas
    public function calcularRendimiento(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $result = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(rendimiento_real_total), 0)    AS und_real,
                COALESCE(SUM(rendimiento_teorico_total), 0) AS und_teorico
            FROM lotes_produccion
            WHERE fecha_produccion BETWEEN ? AND ?
                AND estado IN ('liberado','cuarentena','cerrado')",
            [$fechaDesde, $fechaHasta]
        );

        $real    = (float)($result['und_real']    ?? 0);
        $teorico = (float)($result['und_teorico'] ?? 0);
        $pct     = $teorico > 0 ? round(($real / $teorico) * 100, 2) : 0;

        return [
            'pct'            => $pct,
            'unidades_reales'=> (int)$real,
            'unidades_teorico'=> (int)$teorico,
            'diferencia'     => (int)($real - $teorico),
        ];
    }

    // C = Unidades liberadas / Unidades totales producidas
    public function calcularCalidad(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $total = (int)$this->db->fetchScalar(
            "SELECT COALESCE(SUM(rendimiento_real_total), 0)
                FROM lotes_produccion
                WHERE fecha_produccion BETWEEN ? AND ?
                AND estado IN ('liberado','cuarentena','cerrado','rechazado')",
            [$fechaDesde, $fechaHasta]
        );

        $liberados = (int)$this->db->fetchScalar(
            "SELECT COALESCE(SUM(rendimiento_real_total), 0)
                FROM lotes_produccion
                WHERE fecha_produccion BETWEEN ? AND ?
                AND estado = 'liberado'",
            [$fechaDesde, $fechaHasta]
        );

        $pct = $total > 0 ? round(($liberados / $total) * 100, 2) : 0;

        return [
            'pct'              => $pct,
            'unidades_buenas'  => $liberados,
            'unidades_total'   => $total,
            'unidades_malas'   => $total - $liberados,
        ];
    }

    // KPIs por producto para el período
    public function kpisPorProducto(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        return $this->db->fetchAll(
            "SELECT
                p.nombre AS producto_nombre,
                p.codigo AS producto_codigo,
                COUNT(l.id) AS total_lotes,
                SUM(l.rendimiento_real_total)   AS und_reales,
                SUM(l.rendimiento_teorico_total) AS und_teoricas,
                AVG(l.porcentaje_rendimiento)    AS rend_promedio,
                SUM(l.merma_producto_kg + l.merma_envase_kg +
                    l.merma_reproceso_kg + l.merma_no_conforme_kg +
                    l.merma_quemado_kg) AS merma_total_kg,
                COUNT(CASE WHEN l.estado='liberado' THEN 1 END) AS liberados,
                COUNT(CASE WHEN l.estado='cuarentena' THEN 1 END) AS cuarentena,
                COUNT(CASE WHEN l.estado='rechazado' THEN 1 END) AS rechazados
                FROM lotes_produccion l
                JOIN productos p ON p.id = l.producto_id
                WHERE l.fecha_produccion BETWEEN ? AND ?
                GROUP BY p.id
                ORDER BY und_reales DESC",
            [$fechaDesde, $fechaHasta]
        );
    }

    // Tendencia diaria de producción
    public function tendenciaDiaria(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        return $this->db->fetchAll(
            "SELECT
                fecha_produccion AS fecha,
                COUNT(id) AS lotes,
                SUM(rendimiento_real_total) AS unidades,
                AVG(porcentaje_rendimiento) AS rend_pct
                FROM lotes_produccion
                WHERE fecha_produccion BETWEEN ? AND ?
                GROUP BY fecha_produccion
                ORDER BY fecha_produccion ASC",
            [$fechaDesde, $fechaHasta]
        );
    }

    // Mermas acumuladas por tipo
    public function mermasPorTipo(
        string $fechaDesde,
        string $fechaHasta
    ): array {
        $result = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(merma_producto_kg),    0) AS producto,
                COALESCE(SUM(merma_envase_kg),      0) AS envase,
                COALESCE(SUM(merma_reproceso_kg),   0) AS reproceso,
                COALESCE(SUM(merma_no_conforme_kg), 0) AS no_conforme,
                COALESCE(SUM(merma_quemado_kg),     0) AS quemado
                FROM lotes_produccion
                WHERE fecha_produccion BETWEEN ? AND ?",
            [$fechaDesde, $fechaHasta]
        );

        return [
            'Producto'    => round((float)($result['producto']    ?? 0), 3),
            'Envase'      => round((float)($result['envase']      ?? 0), 3),
            'Reproceso'   => round((float)($result['reproceso']   ?? 0), 3),
            'No conforme' => round((float)($result['no_conforme'] ?? 0), 3),
            'Quemado'     => round((float)($result['quemado']     ?? 0), 3),
        ];
    }

    // Stock MP actual
    public function stockActual(): array
    {
        return $this->db->fetchAll(
            "SELECT i.descripcion, i.codigo, i.unidad_medida,
                    COALESCE(SUM(s.cantidad_disponible), 0) AS stock,
                    MIN(s.fecha_vencimiento) AS proximo_vence
                FROM insumos i
                LEFT JOIN stock_mp s ON s.insumo_id = i.id
                    AND s.estado = 'disponible'
                WHERE i.activo = 1 AND i.tipo = 'materia_prima'
                GROUP BY i.id
                ORDER BY stock ASC
                LIMIT 10"
        );
    }

    // Helper: días hábiles entre dos fechas
    private function diasHabiles(string $desde, string $hasta): int
    {
        $inicio = new \DateTime($desde);
        $fin    = new \DateTime($hasta);
        $dias   = 0;
        $actual = clone $inicio;

        while ($actual <= $fin) {
            $dow = (int)$actual->format('N');
            if ($dow <= 5) $dias++;
            $actual->modify('+1 day');
        }
        return max(1, $dias);
    }

    // Clasificación OEE estándar
    private function clasificarOee(float $oee): array
    {
        if ($oee >= 85) return ['label'=>'Clase Mundial', 'color'=>'#15803d', 'badge'=>'badge-success'];
        if ($oee >= 75) return ['label'=>'Bueno',         'color'=>'#1d4ed8', 'badge'=>'badge-info'];
        if ($oee >= 65) return ['label'=>'Regular',       'color'=>'#d97706', 'badge'=>'badge-warning'];
        return                 ['label'=>'Necesita mejora','color'=>'#dc2626', 'badge'=>'badge-danger'];
    }
}