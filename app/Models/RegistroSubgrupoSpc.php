<?php
// =============================================================================
//  SIACEP — Modelo: Subgrupos SPC (X̄-R / X-MR) — genérico por parámetro
//  Archivo: app/Models/RegistroSubgrupoSpc.php
//
//  Generaliza RegistroPesos (que estaba fijo a "pesos masa cruda", n=10):
//  ahora cualquier parámetro numérico con es_variable_spc=1 (de cualquier
//  etapa, con cualquier tamanio_subgrupo n) usa esta misma tabla,
//  identificado por parametro_id.
// =============================================================================

namespace App\Models;

use App\Core\Model;

class RegistroSubgrupoSpc extends Model
{
    protected string $table    = 'reg_subgrupos_spc';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'sesion_id','parametro_id','hora','valores',
        'promedio_xbar','rango_r','desv_estandar_s',
        'fuera_de_control','regla_violada','alerta_generada',
        'registrado_por_id'
    ];

    // ── Estadísticos del subgrupo (n lecturas, n variable) ───────────────────
    public function calcularEstadisticos(array $valores): array
    {
        $valores = array_values(array_filter(
            $valores, fn($v) => $v !== null && $v !== ''
        ));
        $valores = array_map('floatval', $valores);

        if (empty($valores)) {
            return [
                'promedio_xbar'   => null,
                'rango_r'         => null,
                'desv_estandar_s' => null,
            ];
        }

        $n    = count($valores);
        $xbar = array_sum($valores) / $n;
        $rango = $n > 1 ? max($valores) - min($valores) : 0;

        $varianza = 0;
        foreach ($valores as $v) {
            $varianza += pow($v - $xbar, 2);
        }
        $s = $n > 1 ? sqrt($varianza / ($n - 1)) : 0;

        return [
            'promedio_xbar'   => round($xbar, 4),
            'rango_r'         => round($rango, 4),
            'desv_estandar_s' => round($s, 4),
        ];
    }

    // ── Reglas Western Electric (igual lógica que RegistroPesos) ─────────────
    public function verificarReglas(
        float $xbar,
        float $ucl,
        float $lcl,
        float $cl,
        array $historialXbar = []
    ): array {
        $reglaViolada = null;
        $fueraControl = false;

        // Regla 1: Punto fuera de los límites de control
        if ($xbar > $ucl || $xbar < $lcl) {
            $fueraControl = true;
            $reglaViolada = 'Regla 1 — Punto fuera de UCL/LCL';
        }

        // Regla 2: 8 puntos consecutivos del mismo lado de la línea central
        if (!$fueraControl && count($historialXbar) >= 7) {
            $ultimos8 = array_slice($historialXbar, -7);
            $ultimos8[] = $xbar;
            $porEncima = count(array_filter($ultimos8, fn($v) => $v > $cl));
            $porDebajo = count(array_filter($ultimos8, fn($v) => $v < $cl));
            if ($porEncima === 8 || $porDebajo === 8) {
                $fueraControl = true;
                $reglaViolada = 'Regla 2 — 8 puntos del mismo lado de CL';
            }
        }

        // Regla 3: 6 puntos consecutivos en tendencia
        if (!$fueraControl && count($historialXbar) >= 5) {
            $ultimos6 = array_slice($historialXbar, -5);
            $ultimos6[] = $xbar;
            $tendAscend  = true;
            $tendDescend = true;
            for ($i = 1; $i < count($ultimos6); $i++) {
                if ($ultimos6[$i] <= $ultimos6[$i-1]) $tendAscend  = false;
                if ($ultimos6[$i] >= $ultimos6[$i-1]) $tendDescend = false;
            }
            if ($tendAscend || $tendDescend) {
                $fueraControl = true;
                $reglaViolada = 'Regla 3 — 6 puntos en tendencia '
                    . ($tendAscend ? 'ascendente' : 'descendente');
            }
        }

        return [
            'fuera_de_control' => $fueraControl ? 1 : 0,
            'regla_violada'    => $reglaViolada,
            'alerta_generada'  => $fueraControl ? 1 : 0,
        ];
    }

    // ── Historial de X̄ de un parámetro dentro de una sesión ─────────────────
    public function historialXbar(int $sesionId, int $parametroId): array
    {
        $rows = $this->query(
            "SELECT promedio_xbar FROM reg_subgrupos_spc
             WHERE sesion_id = ? AND parametro_id = ?
               AND promedio_xbar IS NOT NULL
             ORDER BY hora ASC",
            [$sesionId, $parametroId]
        );
        return array_column($rows, 'promedio_xbar');
    }

    // ── Todos los subgrupos de un parámetro en una sesión (con valores decodificados) ──
    public function porSesionYParametro(int $sesionId, int $parametroId): array
    {
        $rows = $this->query(
            "SELECT * FROM reg_subgrupos_spc
             WHERE sesion_id = ? AND parametro_id = ?
             ORDER BY hora ASC",
            [$sesionId, $parametroId]
        );
        foreach ($rows as &$r) {
            $r['valores'] = json_decode($r['valores'], true);
        }
        return $rows;
    }

    // ── Datos para gráfico en tiempo real ────────────────────────────────────
    public function datosGrafico(int $sesionId, int $parametroId,
                                  float $ucl, float $lcl, float $cl): array
    {
        $puntos = $this->porSesionYParametro($sesionId, $parametroId);

        return [
            'puntos'   => $puntos,
            'ucl_xbar' => $ucl,
            'lcl_xbar' => $lcl,
            'cl_xbar'  => $cl,
            'n_puntos' => count($puntos),
            'senales'  => count(array_filter($puntos,
                            fn($p) => $p['fuera_de_control'])),
        ];
    }

    // ── Registrar subgrupo con cálculos automáticos ──────────────────────────
    public function registrarSubgrupo(
        int $sesionId,
        int $parametroId,
        string $hora,
        array $valores,
        array $limites,
        int $registradoPorId
    ): int {
        $stats  = $this->calcularEstadisticos($valores);
        $reglas = ['fuera_de_control'=>0,'regla_violada'=>null,'alerta_generada'=>0];

        if ($stats['promedio_xbar'] !== null && !empty($limites)) {
            $historial = $this->historialXbar($sesionId, $parametroId);
            $reglas    = $this->verificarReglas(
                $stats['promedio_xbar'],
                (float)$limites['ucl_xbar'],
                (float)$limites['lcl_xbar'],
                (float)$limites['cl_xbar'],
                $historial
            );
        }

        return $this->create(array_merge([
            'sesion_id'         => $sesionId,
            'parametro_id'      => $parametroId,
            'hora'              => $hora,
            'valores'           => json_encode(array_values(array_map('floatval', $valores))),
            'registrado_por_id' => $registradoPorId,
        ], $stats, $reglas));
    }
}