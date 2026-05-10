<?php
// =============================================================================
//  SIACEP — Modelo: Producto
//  Archivo: app/Models/Producto.php
// =============================================================================

namespace App\Models;

use App\Core\Model;

class Producto extends Model
{
    protected string $table    = 'productos';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'linea_id','codigo','nombre','descripcion',
        'lote_contrato','item_dbc',
        'peso_nominal_g','tolerancia_pct','lse_g','lie_g',
        'unidades_por_receta','unidades_por_bolsa','bolsas_por_caja',
        'unidades_por_caja','peso_caja_kg','vida_util_dias',
        'temperatura_conserv_min','temperatura_conserv_max','temperatura_entrega_max',
        'ref_color','ref_olor','ref_sabor','ref_textura','ref_apariencia',
        'ref_humedad_max_pct','ref_ph_min','ref_ph_max',
        'activo','creado_por'
    ];

    // Todos los productos con su línea
    public function todosConLinea(): array
{
    return $this->query(
        "SELECT p.*, l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                COUNT(pp.id) AS total_parametros,
                SUM(pp.es_variable_spc) AS parametros_spc
            FROM productos p
            JOIN lineas_produccion l ON l.id = p.linea_id
            LEFT JOIN parametros_proceso pp ON pp.producto_id = p.id AND pp.activo = 1
            GROUP BY p.id
            ORDER BY l.nombre, p.nombre"
    );
}

    // Productos de una línea específica
    public function porLinea(int $lineaId): array
    {
        return $this->query(
            "SELECT p.*, l.nombre AS linea_nombre
            FROM productos p
            JOIN lineas_produccion l ON l.id = p.linea_id
            WHERE p.linea_id = ? AND p.activo = 1
            ORDER BY p.nombre",
            [$lineaId]
        );
    }

    // Producto completo con línea y conteo de parámetros
    public function conDetalle(int $id): array|false
    {
        return $this->queryOne(
            "SELECT p.*, l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                    COUNT(pp.id) AS total_parametros,
                    SUM(pp.es_variable_spc) AS parametros_spc
            FROM productos p
            JOIN lineas_produccion l ON l.id = p.linea_id
            LEFT JOIN parametros_proceso pp ON pp.producto_id = p.id AND pp.activo = 1
            WHERE p.id = ?
            GROUP BY p.id",
            [$id]
        );
    }

    // Calcular LSE y LIE automáticamente desde peso nominal y tolerancia
    public function calcularLimites(float $pesoNominal, float $toleranciaPct): array
    {
        return [
            'lse_g' => round($pesoNominal * (1 + $toleranciaPct / 100), 3),
            'lie_g' => round($pesoNominal * (1 - $toleranciaPct / 100), 3),
        ];
    }

    public function codigoExiste(string $codigo, ?int $exceptoId = null): bool
    {
        if ($exceptoId) {
            return $this->exists('codigo = ? AND id != ?', [$codigo, $exceptoId]);
        }
        return $this->exists('codigo = ?', [$codigo]);
    }

    public function paraSelect(int $lineaId): array
    {
        $rows = $this->query(
            "SELECT id, nombre, codigo, peso_nominal_g
                FROM productos WHERE linea_id = ? AND activo = 1 ORDER BY nombre",
            [$lineaId]
        );
        $result = [];
        foreach ($rows as $r) {
            $result[$r['id']] = $r['nombre'] . ' (' . $r['codigo'] . ')';
        }
        return $result;
    }
}