<?php
namespace App\Models;

use App\Core\Model;

class Proveedor extends Model
{
    protected string $table    = 'proveedores';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'nombre','procedencia','telefono','email',
        'registro_sanitario','activo','creado_por'
    ];

    public function todosActivos(): array
    {
        return $this->query(
            "SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre"
        );
    }

    public function paraSelect(): array
    {
        $rows = $this->query(
            "SELECT id,
                    CONCAT(nombre, IFNULL(CONCAT(' — ', procedencia), '')) AS label
            FROM proveedores WHERE activo = 1 ORDER BY nombre"
        );
        return array_column($rows, 'label', 'id');
    }

    public function nombreExiste(string $nombre, ?int $exceptoId = null): bool
    {
        if ($exceptoId) {
            return $this->exists('nombre = ? AND id != ?', [$nombre, $exceptoId]);
        }
        return $this->exists('nombre = ?', [$nombre]);
    }
}