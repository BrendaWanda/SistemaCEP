<?php
// =============================================================================
//  SIACEP — Modelo: Línea de Producción
//  Archivo: app/Models/LineaProduccion.php
// =============================================================================

namespace App\Models;

use App\Core\Model;

class LineaProduccion extends Model
{
    protected string $table    = 'lineas_produccion';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'codigo', 'nombre', 'descripcion', 'activa', 'creado_por'
    ];

    // Todas las líneas activas con conteo de productos
    public function todasConConteo(): array
    {
        return $this->query(
            "SELECT l.*,
                    COUNT(p.id) AS total_productos,
                    SUM(p.activo) AS productos_activos
                FROM lineas_produccion l
                LEFT JOIN productos p ON p.linea_id = l.id
                GROUP BY l.id
                ORDER BY l.nombre"
        );
    }

    // Verificar que el código no esté duplicado
    public function codigoExiste(string $codigo, ?int $exceptoId = null): bool
    {
        if ($exceptoId) {
            return $this->exists('codigo = ? AND id != ?', [$codigo, $exceptoId]);
        }
        return $this->exists('codigo = ?', [$codigo]);
    }

    // Lista para select en formularios
    public function paraSelect(): array
    {
        return $this->toSelectList('id', 'nombre', 'activa = 1');
    }
}