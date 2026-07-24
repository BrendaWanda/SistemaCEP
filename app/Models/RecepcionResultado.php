<?php
// =============================================================================
//  SIACEP — Modelo: Resultados dinámicos de recepción de MP
//  Archivo: app/Models/RecepcionResultado.php
//
//  Espejo de RegistroValorSimple.php — un registro por (recepción,
//  parámetro), reemplazando las 21 columnas fijas fisicoquímicas que
//  antes tenía recepciones_mp (esp_humedad/res_humedad/conf_humedad,
//  etc.) — ahora los parámetros vienen de parametros_insumo, definidos
//  por insumo en M0.
// =============================================================================

namespace App\Models;

use App\Core\Model;

class RecepcionResultado extends Model
{
    protected string $table    = 'recepcion_resultados';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'recepcion_id','parametro_insumo_id','resultado','conformidad'
    ];

    // Todos los resultados de una recepción, con datos del parámetro
    // (nombre, unidad, tipo, mín/máx) para poder mostrarlos sin
    // consultas adicionales — igual que RegistroValorSimple::porSesion().
    public function porRecepcion(int $recepcionId): array
    {
        return $this->query(
            "SELECT rr.*,
                    pi.nombre AS parametro_nombre,
                    pi.unidad,
                    pi.tipo_dato,
                    pi.valor_min,
                    pi.valor_max,
                    pi.opciones_json
            FROM recepcion_resultados rr
            JOIN parametros_insumo pi ON pi.id = rr.parametro_insumo_id
            WHERE rr.recepcion_id = ?
            ORDER BY pi.orden_display, pi.nombre",
            [$recepcionId]
        );
    }

    // Guarda todos los resultados de una recepción de una sola vez —
    // usado al crear la recepción, dentro de la misma transacción que
    // el INSERT de recepciones_mp (ver RecepcionController::guardar()).
    public function guardarLote(int $recepcionId, array $resultados): void
    {
        foreach ($resultados as $r) {
            if ($r['resultado'] === '' && $r['conformidad'] === null) continue;
            $this->db->execute(
                "INSERT INTO recepcion_resultados
                (recepcion_id, parametro_insumo_id, resultado, conformidad)
                VALUES (?,?,?,?)",
                [$recepcionId, $r['parametro_insumo_id'], $r['resultado'] ?: null, $r['conformidad']]
            );
        }
    }
}