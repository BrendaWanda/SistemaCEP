<?php
namespace App\Models;

use App\Core\Model;

class Receta extends Model
{
    protected string $table    = 'recetas';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'producto_id','version','nombre','descripcion',
        'vigente','aprobada_por','aprobada_en','creado_por'
    ];

    public function todasConProducto(): array
    {
        return $this->query(
            "SELECT r.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo,
                    l.nombre AS linea_nombre,
                    COUNT(ri.id) AS total_ingredientes,
                    u.nombre AS aprobada_por_nombre
            FROM recetas r
            JOIN productos p ON p.id = r.producto_id
            JOIN lineas_produccion l ON l.id = p.linea_id
            LEFT JOIN receta_insumos ri ON ri.receta_id = r.id
            LEFT JOIN usuarios u ON u.id = r.aprobada_por
            GROUP BY r.id
            ORDER BY l.nombre, p.nombre, r.version DESC"
        );
    }

    public function porProducto(int $productoId): array
    {
        return $this->query(
            "SELECT r.*, COUNT(ri.id) AS total_ingredientes
            FROM recetas r
            LEFT JOIN receta_insumos ri ON ri.receta_id = r.id
            WHERE r.producto_id = ?
            GROUP BY r.id
            ORDER BY r.version DESC",
            [$productoId]
        );
    }

    public function conIngredientes(int $id): array|false
    {
        $receta = $this->queryOne(
            "SELECT r.*, p.nombre AS producto_nombre, p.peso_nominal_g,
                    p.unidades_por_receta
            FROM recetas r
            JOIN productos p ON p.id = r.producto_id
            WHERE r.id = ?",
            [$id]
        );
        if (!$receta) return false;

        $receta['ingredientes'] = $this->query(
            "SELECT ri.*, i.descripcion AS insumo_nombre,
                    i.codigo AS insumo_codigo, i.unidad_medida AS unidad_ref,
                    i.tipo AS insumo_tipo
            FROM receta_insumos ri
            JOIN insumos i ON i.id = ri.insumo_id
            WHERE ri.receta_id = ?
            ORDER BY i.tipo, i.descripcion",
            [$id]
        );
        return $receta;
    }

    public function siguienteVersion(int $productoId): int
    {
        $max = $this->db->fetchScalar(
            "SELECT MAX(version) FROM recetas WHERE producto_id = ?",
            [$productoId]
        );
        return ($max ?? 0) + 1;
    }

    public function paraSelect(int $productoId): array
    {
        $rows = $this->query(
            "SELECT id, CONCAT('v', version, ' — ', nombre) AS label
            FROM recetas WHERE producto_id = ? AND vigente = 1 ORDER BY version DESC",
            [$productoId]
        );
        return array_column($rows, 'label', 'id');
    }

    public function desactivarAnteriores(int $productoId, int $exceptoId): void
    {
        $this->execute(
            "UPDATE recetas SET vigente = 0 WHERE producto_id = ? AND id != ?",
            [$productoId, $exceptoId]
        );
    }
}