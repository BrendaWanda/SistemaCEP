<?php
namespace App\Models;

use App\Core\Model;

class RegistroPesos extends Model
{
    protected string $table    = 'reg_pesos_masa_cruda';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'sesion_id','hora','peso_01','peso_02','peso_03','peso_04','peso_05',
        'peso_06','peso_07','peso_08','peso_09','peso_10',
        'promedio_xbar','rango_r','desv_estandar_s',
        'fuera_de_control','regla_violada','alerta_generada',
        'etapa','operario_id','observaciones','registrado_por_id'
    ];

    // Calcular estadísticos del subgrupo
    public function calcularEstadisticos(array $pesos): array
    {
        $pesos = array_filter($pesos, fn($p) => $p !== null && $p !== '');
        $pesos = array_map('floatval', $pesos);

        if (empty($pesos)) {
            return [
                'promedio_xbar'   => null,
                'rango_r'         => null,
                'desv_estandar_s' => null,
            ];
        }

        $n      = count($pesos);
        $suma   = array_sum($pesos);
        $xbar   = $suma / $n;
        $rango  = max($pesos) - min($pesos);

        // Desviación estándar muestral
        $varianza = 0;
        foreach ($pesos as $p) {
            $varianza += pow($p - $xbar, 2);
        }
        $s = $n > 1 ? sqrt($varianza / ($n - 1)) : 0;

        return [
            'promedio_xbar'   => round($xbar, 4),
            'rango_r'         => round($rango, 4),
            'desv_estandar_s' => round($s, 4),
        ];
    }

    // Verificar reglas de Western Electric contra límites calculados
    public function verificarReglas(
        float $xbar,
        float $ucl,
        float $lcl,
        float $cl,
        array $historialXbar = []
    ): array {
        $reglaViolada  = null;
        $fueraControl  = false;

        // Regla 1: Punto fuera de los límites de control
        if ($xbar > $ucl || $xbar < $lcl) {
            $fueraControl = true;
            $reglaViolada = 'Regla 1 — Punto fuera de UCL/LCL';
        }

        // Regla 2: 8 puntos consecutivos del mismo lado de la línea central
        if (!$fueraControl && count($historialXbar) >= 7) {
            $ultimos8 = array_slice($historialXbar, -7);
            $ultimos8[] = $xbar;
            $porEncima  = count(array_filter($ultimos8, fn($v) => $v > $cl));
            $porDebajo  = count(array_filter($ultimos8, fn($v) => $v < $cl));
            if ($porEncima === 8 || $porDebajo === 8) {
                $fueraControl = true;
                $reglaViolada = 'Regla 2 — 8 puntos del mismo lado de CL';
            }
        }

        // Regla 3: 6 puntos consecutivos en tendencia
        if (!$fueraControl && count($historialXbar) >= 5) {
            $ultimos6 = array_slice($historialXbar, -5);
            $ultimos6[] = $xbar;
            $tendAscend = true;
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

    // Historial de X̄ de una sesión para verificar reglas
    public function historialXbar(int $sesionId): array
    {
        $rows = $this->query(
            "SELECT promedio_xbar FROM reg_pesos_masa_cruda
            WHERE sesion_id = ? AND promedio_xbar IS NOT NULL
            ORDER BY hora ASC",
            [$sesionId]
        );
        return array_column($rows, 'promedio_xbar');
    }

    // Todos los pesos de una sesión para el gráfico en tiempo real
    public function datosGrafico(int $sesionId, float $ucl,
                                float $lcl, float $cl): array
    {
        $pesos = $this->query(
            "SELECT hora, promedio_xbar, rango_r,
                    fuera_de_control, regla_violada
            FROM reg_pesos_masa_cruda
            WHERE sesion_id = ?
            ORDER BY hora ASC",
            [$sesionId]
        );

        return [
            'puntos'     => $pesos,
            'ucl_xbar'   => $ucl,
            'lcl_xbar'   => $lcl,
            'cl_xbar'    => $cl,
            'n_puntos'   => count($pesos),
            'senales'    => count(array_filter($pesos,
                            fn($p) => $p['fuera_de_control'])),
        ];
    }

    // Registrar subgrupo con cálculos automáticos
    public function registrarSubgrupo(array $data, int $sesionId,
                                    array $limites): int
    {
        $pesos = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = 'peso_'.str_pad($i, 2, '0', STR_PAD_LEFT);
            if (isset($data[$key]) && $data[$key] !== '') {
                $pesos[] = $data[$key];
            }
        }

        $stats   = $this->calcularEstadisticos($pesos);
        $reglas  = ['fuera_de_control'=>0,'regla_violada'=>null,'alerta_generada'=>0];

        if ($stats['promedio_xbar'] !== null && !empty($limites)) {
            $historial = $this->historialXbar($sesionId);
            $reglas    = $this->verificarReglas(
                $stats['promedio_xbar'],
                $limites['ucl_xbar'],
                $limites['lcl_xbar'],
                $limites['cl_xbar'],
                $historial
            );
        }

        return $this->create(array_merge($data, $stats, $reglas));
    }
}