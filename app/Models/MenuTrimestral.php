<?php
namespace App\Models;

use App\Core\Model;

class MenuTrimestral extends Model
{
    protected string $table    = 'menu_trimestral';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'anio','trimestre','nombre','fecha_inicio','fecha_fin',
        'nivel','recibido_de','fecha_recepcion','obs','activo','creado_por'
    ];

    public const TRIMESTRES = [
        1 => 'Trimestre 1 (Ene-Mar)',
        2 => 'Trimestre 2 (Abr-Jun)',
        3 => 'Trimestre 3 (Jul-Sep)',
        4 => 'Trimestre 4 (Oct-Dic)',
    ];

    public const NIVELES = [
        'Secundaria y Ed. Especial',
        'Primaria',
        'Inicial',
        'Todos los niveles',
    ];

    // Todos los menús con stats de producción
    public function todosConStats(): array
    {
        return $this->query(
            "SELECT m.*,
                    -- Lotes producidos en el período
                    COUNT(DISTINCT l.id) AS total_lotes,
                    COALESCE(SUM(l.rendimiento_real_total), 0) AS und_producidas,
                    -- Días de producción
                    COUNT(DISTINCT l.fecha_produccion) AS dias_producidos
            FROM menu_trimestral m
            LEFT JOIN lotes_produccion l
                ON l.fecha_produccion BETWEEN m.fecha_inicio AND m.fecha_fin
                AND l.estado != 'rechazado'
            WHERE m.activo = 1
            GROUP BY m.id
            ORDER BY m.anio DESC, m.trimestre DESC"
        );
    }

    // Un menú con detalle de lotes
    public function conDetalle(int $id): array|false
    {
        $menu = $this->find($id);
        if (!$menu) return false;

        // Lotes producidos en el período del menú
        $menu['lotes'] = $this->db->fetchAll(
            "SELECT l.*, p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            WHERE l.fecha_produccion BETWEEN ? AND ?
            AND l.estado != 'rechazado'
            ORDER BY l.fecha_produccion ASC",
            [$menu['fecha_inicio'], $menu['fecha_fin']]
        );

        // Resumen por producto
        $menu['por_producto'] = $this->db->fetchAll(
            "SELECT p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    COUNT(l.id) AS total_lotes,
                    SUM(l.rendimiento_real_total) AS und_producidas,
                    AVG(l.porcentaje_rendimiento) AS rend_promedio
            FROM lotes_produccion l
            JOIN productos p ON p.id = l.producto_id
            WHERE l.fecha_produccion BETWEEN ? AND ?
                AND l.estado != 'rechazado'
            GROUP BY p.id
            ORDER BY und_producidas DESC",
            [$menu['fecha_inicio'], $menu['fecha_fin']]
        );

        return $menu;
    }

    // Menú activo actual
    public function actual(): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM menu_trimestral
            WHERE activo = 1
            AND fecha_inicio <= CURDATE()
            AND fecha_fin >= CURDATE()
            LIMIT 1"
        );
    }
}