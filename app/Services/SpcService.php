<?php
namespace App\Services;

class SpcService
{
    // Constantes SPC para subgrupos n=2 a n=10
    const TABLAS = [
        2  => ['d2'=>1.128,'D3'=>0,    'D4'=>3.267,'A2'=>1.880],
        3  => ['d2'=>1.693,'D3'=>0,    'D4'=>2.574,'A2'=>1.023],
        4  => ['d2'=>2.059,'D3'=>0,    'D4'=>2.282,'A2'=>0.729],
        5  => ['d2'=>2.326,'D3'=>0,    'D4'=>2.114,'A2'=>0.577],
        6  => ['d2'=>2.534,'D3'=>0,    'D4'=>2.004,'A2'=>0.483],
        7  => ['d2'=>2.704,'D3'=>0.076,'D4'=>1.924,'A2'=>0.419],
        8  => ['d2'=>2.847,'D3'=>0.136,'D4'=>1.864,'A2'=>0.373],
        9  => ['d2'=>2.970,'D3'=>0.184,'D4'=>1.816,'A2'=>0.337],
        10 => ['d2'=>3.078,'D3'=>0.223,'D4'=>1.777,'A2'=>0.308],
    ];

    // Calcular límites X̄-R desde datos históricos
    public function calcularLimitesXbarR(array $subgrupos, int $n = 10): array
    {
        if (empty($subgrupos)) {
            return $this->limitesVacios();
        }

        $constantes = self::TABLAS[$n] ?? self::TABLAS[10];

        // Calcular X̄̄ (gran media) y R̄ (media de rangos)
        $xbars  = array_column($subgrupos, 'promedio_xbar');
        $rangos = array_column($subgrupos, 'rango_r');

        $xbars  = array_filter($xbars,  fn($v) => $v !== null);
        $rangos = array_filter($rangos, fn($v) => $v !== null);

        if (empty($xbars)) return $this->limitesVacios();

        $xbarbar = array_sum($xbars)  / count($xbars);
        $rbar    = array_sum($rangos) / count($rangos);

        // Límites gráfico X̄
        $uclXbar = round($xbarbar + $constantes['A2'] * $rbar, 4);
        $lclXbar = round(max(0, $xbarbar - $constantes['A2'] * $rbar), 4);

        // Límites gráfico R
        $uclR = round($constantes['D4'] * $rbar, 4);
        $lclR = round($constantes['D3'] * $rbar, 4);

        // Estimación de sigma
        $sigmaEst = $rbar / $constantes['d2'];

        return [
            'cl_xbar'  => round($xbarbar, 4),
            'ucl_xbar' => $uclXbar,
            'lcl_xbar' => $lclXbar,
            'cl_r'     => round($rbar, 4),
            'ucl_r'    => $uclR,
            'lcl_r'    => $lclR,
            'sigma_est'=> round($sigmaEst, 6),
            'n'        => $n,
            'k'        => count($xbars),
            'xbarbar'  => round($xbarbar, 4),
            'rbar'     => round($rbar, 4),
        ];
    }

    // Calcular índices de capacidad Cp y Cpk
    public function calcularCapacidad(
        array  $subgrupos,
        float  $lse,
        float  $lie,
        int    $n = 10
    ): array {
        $limites = $this->calcularLimitesXbarR($subgrupos, $n);
        if (!$limites['sigma_est']) {
            return ['cp'=>null,'cpk'=>null,'pp'=>null,'ppk'=>null];
        }

        $sigma = $limites['sigma_est'];
        $media = $limites['cl_xbar'];

        // Cp y Cpk (capacidad potencial — con sigma estimado de R̄)
        $cp  = ($lse - $lie) / (6 * $sigma);
        $cpu = ($lse - $media) / (3 * $sigma);
        $cpl = ($media - $lie) / (3 * $sigma);
        $cpk = min($cpu, $cpl);

        // Pp y Ppk (desempeño real — con sigma muestral)
        $xbars = array_filter(array_column($subgrupos, 'promedio_xbar'),
                              fn($v) => $v !== null);
        $sigmaMuestral = $this->desviacionMuestral(array_values($xbars));

        $pp  = $sigmaMuestral > 0 ? ($lse - $lie) / (6 * $sigmaMuestral) : null;
        $ppk = null;
        if ($sigmaMuestral > 0) {
            $ppu = ($lse - $media) / (3 * $sigmaMuestral);
            $ppl = ($media - $lie) / (3 * $sigmaMuestral);
            $ppk = min($ppu, $ppl);
        }

        return [
            'cp'          => round($cp, 4),
            'cpk'         => round($cpk, 4),
            'cpu'         => round($cpu, 4),
            'cpl'         => round($cpl, 4),
            'pp'          => $pp ? round($pp, 4) : null,
            'ppk'         => $ppk ? round($ppk, 4) : null,
            'sigma_est'   => round($sigma, 6),
            'media_xbar'  => round($media, 4),
            'lse'         => $lse,
            'lie'         => $lie,
            'interpretacion_cp'  => $this->interpretarCp($cp),
            'interpretacion_cpk' => $this->interpretarCp($cpk),
        ];
    }

    // Generar datos para gráfico X̄-R
    public function datosGraficoXbarR(array $subgrupos, array $limites): array
    {
        $puntos = [];
        foreach ($subgrupos as $i => $sg) {
            $puntos[] = [
                'index'           => $i + 1,
                'hora'            => $sg['hora'],
                'xbar'            => (float)$sg['promedio_xbar'],
                'r'               => (float)$sg['rango_r'],
                'fuera_control'   => (bool)$sg['fuera_de_control'],
                'regla_violada'   => $sg['regla_violada'],
            ];
        }

        return [
            'puntos'   => $puntos,
            'limites'  => $limites,
            'n_puntos' => count($puntos),
            'senales'  => count(array_filter($puntos, fn($p) => $p['fuera_control'])),
        ];
    }

    // Datos para histograma de frecuencias
    public function datosHistograma(array $valores, int $clases = 8): array
    {
        if (empty($valores)) return [];

        $min  = min($valores);
        $max  = max($valores);
        $rango = $max - $min;
        if ($rango == 0) return [];

        $amplitud  = $rango / $clases;
        $frecuencias = array_fill(0, $clases, 0);
        $etiquetas   = [];

        for ($i = 0; $i < $clases; $i++) {
            $limInf = $min + ($i * $amplitud);
            $limSup = $min + (($i + 1) * $amplitud);
            $etiquetas[] = number_format($limInf, 2).'-'.number_format($limSup, 2);

            foreach ($valores as $v) {
                if ($i === $clases - 1) {
                    if ($v >= $limInf && $v <= $limSup) $frecuencias[$i]++;
                } else {
                    if ($v >= $limInf && $v < $limSup) $frecuencias[$i]++;
                }
            }
        }

        return [
            'etiquetas'   => $etiquetas,
            'frecuencias' => $frecuencias,
            'min'         => $min,
            'max'         => $max,
            'media'       => array_sum($valores) / count($valores),
            'desv_std'    => $this->desviacionMuestral($valores),
        ];
    }

    // Estadística descriptiva
    public function estadisticaDescriptiva(array $valores): array
    {
        if (empty($valores)) return [];

        sort($valores);
        $n    = count($valores);
        $suma = array_sum($valores);
        $media = $suma / $n;

        $varianza = 0;
        foreach ($valores as $v) {
            $varianza += pow($v - $media, 2);
        }
        $desv = $n > 1 ? sqrt($varianza / ($n - 1)) : 0;

        // Mediana
        $mediana = $n % 2 === 0
            ? ($valores[$n/2 - 1] + $valores[$n/2]) / 2
            : $valores[(int)floor($n/2)];

        // Cuartiles
        $q1 = $valores[(int)floor($n * 0.25)];
        $q3 = $valores[(int)floor($n * 0.75)];

        return [
            'n'          => $n,
            'media'      => round($media, 4),
            'mediana'    => round($mediana, 4),
            'desv_std'   => round($desv, 4),
            'varianza'   => round($varianza / max(1, $n-1), 4),
            'min'        => min($valores),
            'max'        => max($valores),
            'rango'      => max($valores) - min($valores),
            'q1'         => $q1,
            'q3'         => $q3,
            'iqr'        => $q3 - $q1,
            'cv_pct'     => $media != 0 ? round(($desv/$media)*100, 2) : null,
        ];
    }

    // Helpers privados
    private function desviacionMuestral(array $valores): float
    {
        $n = count($valores);
        if ($n < 2) return 0;
        $media = array_sum($valores) / $n;
        $suma  = 0;
        foreach ($valores as $v) $suma += pow($v - $media, 2);
        return sqrt($suma / ($n - 1));
    }

    private function interpretarCp(float $cp): string
    {
        if ($cp >= 1.67) return 'Excelente — proceso muy capaz';
        if ($cp >= 1.33) return 'Adecuado — proceso capaz';
        if ($cp >= 1.00) return 'Marginal — necesita mejora';
        return 'Inadecuado — proceso no capaz';
    }

    private function limitesVacios(): array
    {
        return [
            'cl_xbar'=>null,'ucl_xbar'=>null,'lcl_xbar'=>null,
            'cl_r'=>null,'ucl_r'=>null,'lcl_r'=>null,
            'sigma_est'=>null,'n'=>10,'k'=>0,
            'xbarbar'=>null,'rbar'=>null,
        ];
    }
}