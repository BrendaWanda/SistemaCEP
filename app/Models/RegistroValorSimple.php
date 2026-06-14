<?php
// =============================================================================
//  SIACEP — Modelo: Valores simples de proceso (no-SPC)
//  Archivo: app/Models/RegistroValorSimple.php
//
//  Reemplaza las tablas fijas reg_proceso_amasado, reg_proceso_horneado y
//  reg_control_envasado: un registro por (sesión, parámetro, hora) para
//  cualquier parámetro con es_variable_spc = 0, de cualquier tipo_dato
//  (numerico, texto, si_no, seleccion). El valor se guarda como texto y
//  se interpreta según parametros_proceso.tipo_dato al mostrarlo.
// =============================================================================

namespace App\Models;

use App\Core\Model;

class RegistroValorSimple extends Model
{
    protected string $table    = 'reg_valores_simples';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'sesion_id','parametro_id','hora','valor','registrado_por_id'
    ];

    // Todos los valores de una sesión, con datos del parámetro (nombre,
    // unidad, tipo, etapa) para poder renderizarlos sin consultas extra.
    public function porSesion(int $sesionId): array
    {
        return $this->query(
            "SELECT rv.*,
                    pp.nombre AS parametro_nombre,
                    pp.unidad,
                    pp.tipo_dato,
                    pp.etapa,
                    pp.opciones_json
             FROM reg_valores_simples rv
             JOIN parametros_proceso pp ON pp.id = rv.parametro_id
             WHERE rv.sesion_id = ?
             ORDER BY rv.hora ASC, rv.id ASC",
            [$sesionId]
        );
    }

    // Igual que porSesion(), pero agrupado por etapa — útil para renderizar
    // cada sección de sesion.php con sus propios registros históricos.
    public function porSesionAgrupadoPorEtapa(int $sesionId): array
    {
        $grouped = [];
        foreach ($this->porSesion($sesionId) as $row) {
            $grouped[$row['etapa']][] = $row;
        }
        return $grouped;
    }

    // Solo los valores de una etapa específica dentro de una sesión.
    public function porSesionYEtapa(int $sesionId, string $etapa): array
    {
        return $this->query(
            "SELECT rv.*,
                    pp.nombre AS parametro_nombre,
                    pp.unidad,
                    pp.tipo_dato,
                    pp.opciones_json
             FROM reg_valores_simples rv
             JOIN parametros_proceso pp ON pp.id = rv.parametro_id
             WHERE rv.sesion_id = ? AND pp.etapa = ?
             ORDER BY rv.hora ASC, rv.id ASC",
            [$sesionId, $etapa]
        );
    }
}