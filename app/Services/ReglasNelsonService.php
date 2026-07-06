<?php
// =============================================================================
//  SIACEP — Servicio: Reglas de Nelson / Western Electric
//  Archivo: app/Services/ReglasNelsonService.php
//
//  Única fuente de verdad para las 8 reglas de Nelson. Antes existían DOS
//  implementaciones independientes:
//    - RegistroSubgrupoSpc::verificarReglas() (M2) — las 8 reglas completas.
//    - SpcController::reglasNelson()          (M6) — solo 4 reglas, y lo que
//      ahí se llamaba "Regla 4" era en realidad la Regla 5 en la numeración
//      estándar de Nelson. Eso generaba números de regla inconsistentes
//      entre lo que un supervisor ve en M2 y lo que ve en un reporte de M6.
//
//  Este servicio es la única implementación; M2 y M6 llaman a la misma
//  función y por lo tanto siempre citan el mismo número de regla para el
//  mismo fenómeno estadístico.
// =============================================================================

namespace App\Services;

class ReglasNelsonService
{
    // Descripción de cada regla — única fuente de texto, usada tanto para
    // armar la 'descripcion' combinada como por cualquier módulo que
    // necesite listar violaciones individuales (M6 las usa así).
    public const DESCRIPCIONES = [
        1 => 'Punto fuera de límites de control (±3σ)',
        2 => '9 puntos seguidos del mismo lado de la línea central',
        3 => '6 puntos seguidos en tendencia continua (subiendo o bajando)',
        4 => '14 puntos seguidos alternando arriba/abajo',
        5 => '2 de 3 puntos más allá de 2σ, mismo lado',
        6 => '4 de 5 puntos más allá de 1σ, mismo lado',
        7 => '15 puntos seguidos dentro de 1σ (estratificación)',
        8 => '8 puntos seguidos fuera de 1σ, sin ninguno cerca del centro (mezcla)',
    ];

    // -------------------------------------------------------------------
    // Evalúa las 8 reglas de Nelson sobre una secuencia cronológica de
    // estadísticos (x̄ de subgrupos, o proporciones p de una carta p —
    // el algoritmo es idéntico, solo cambia qué representa cada punto).
    //
    //   $secuencia = valores en orden cronológico, el último es el punto
    //                que se está evaluando "ahora".
    //   $cl        = línea central (x̄̄ o p̄).
    //   $sigma     = desviación estándar del estadístico graficado
    //                (σ_x̄ = σ/√n para X̄-R/X̄-S; σ_p = √(p̄(1-p̄)/n) para carta p).
    //   $ucl/$lcl  = límites de control ya calculados (± 3σ). Si son null,
    //                se derivan de $cl ± 3*$sigma.
    //
    // Devuelve: ['fuera_control' => bool, 'reglas' => [n, ...], 'descripcion' => string|null]
    // -------------------------------------------------------------------
    public static function evaluar(
        array $secuencia,
        float $cl,
        float $sigma,
        ?float $ucl = null,
        ?float $lcl = null
    ): array {
        $ucl ??= $cl + 3 * $sigma;
        $lcl ??= $cl - 3 * $sigma;

        $reglas = [];
        $ultimo = end($secuencia);

        // Regla 1: punto fuera de ±3σ — no necesita historial.
        if ($ultimo > $ucl) $reglas[1] = self::DESCRIPCIONES[1];
        if ($ultimo < $lcl) $reglas[1] = self::DESCRIPCIONES[1];

        if ($sigma <= 0) {
            return self::armarResultado($reglas);
        }

        if (self::rachaMismoLado($secuencia, $cl, 9))               $reglas[2] = self::DESCRIPCIONES[2];
        if (self::tendencia($secuencia, 6))                         $reglas[3] = self::DESCRIPCIONES[3];
        if (self::alternancia($secuencia, 14))                      $reglas[4] = self::DESCRIPCIONES[4];
        if (self::zona($secuencia, $cl, $sigma, 2, 3, 2))           $reglas[5] = self::DESCRIPCIONES[5];
        if (self::zona($secuencia, $cl, $sigma, 1, 5, 4))           $reglas[6] = self::DESCRIPCIONES[6];
        if (self::estratificacion($secuencia, $cl, $sigma, 15))     $reglas[7] = self::DESCRIPCIONES[7];
        if (self::mezcla($secuencia, $cl, $sigma, 8))               $reglas[8] = self::DESCRIPCIONES[8];

        return self::armarResultado($reglas);
    }

    // -------------------------------------------------------------------
    // Evalúa las 8 reglas para CADA punto de la secuencia (no solo el
    // último) — usado por M6, que analiza un lote histórico completo de
    // una sola vez y necesita marcar cuáles subgrupos violan qué regla.
    //
    // Devuelve un array indexado 0..n-1 con el mismo shape de evaluar()
    // para cada posición (evaluando solo con los puntos hasta esa posición).
    // -------------------------------------------------------------------
    public static function evaluarSerieCompleta(
        array $secuencia,
        float $cl,
        float $sigma,
        ?float $ucl = null,
        ?float $lcl = null
    ): array {
        $resultados = [];
        foreach (array_keys($secuencia) as $i) {
            $ventana = array_slice($secuencia, 0, $i + 1);
            $resultados[$i] = self::evaluar($ventana, $cl, $sigma, $ucl, $lcl);
        }
        return $resultados;
    }

    private static function armarResultado(array $reglas): array
    {
        if (empty($reglas)) {
            return ['fuera_control' => false, 'reglas' => [], 'descripcion' => null];
        }
        ksort($reglas);
        $descripcion = 'Regla ' . implode(', ', array_map(
            fn($n, $desc) => "{$n} ({$desc})", array_keys($reglas), $reglas
        ));
        return ['fuera_control' => true, 'reglas' => array_keys($reglas), 'descripcion' => $descripcion];
    }

    // ── Regla 2: n puntos seguidos del mismo lado de la línea central ──
    private static function rachaMismoLado(array $seq, float $cl, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        $arriba = array_filter($tail, fn($v) => $v > $cl);
        $abajo  = array_filter($tail, fn($v) => $v < $cl);
        return count($arriba) === $n || count($abajo) === $n;
    }

    // ── Regla 3: n puntos seguidos en tendencia continua ──
    private static function tendencia(array $seq, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        $subiendo = $bajando = true;
        for ($i = 1; $i < count($tail); $i++) {
            if ($tail[$i] <= $tail[$i - 1]) $subiendo = false;
            if ($tail[$i] >= $tail[$i - 1]) $bajando  = false;
        }
        return $subiendo || $bajando;
    }

    // ── Regla 4: n puntos seguidos alternando dirección (zigzag) ──
    private static function alternancia(array $seq, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        $signos = [];
        for ($i = 1; $i < count($tail); $i++) {
            $diff = $tail[$i] - $tail[$i - 1];
            if ($diff == 0) return false;
            $signos[] = $diff > 0 ? 1 : -1;
        }
        for ($i = 1; $i < count($signos); $i++) {
            if ($signos[$i] === $signos[$i - 1]) return false;
        }
        return true;
    }

    // ── Reglas 5 y 6: k de m puntos más allá de kSigma, mismo lado ──
    private static function zona(array $seq, float $cl, float $sigma, int $kSigma, int $m, int $minimo): bool
    {
        if (count($seq) < $m) return false;
        $tail = array_slice($seq, -$m);
        $limiteSup = $cl + $kSigma * $sigma;
        $limiteInf = $cl - $kSigma * $sigma;

        $arriba = count(array_filter($tail, fn($v) => $v > $limiteSup));
        $abajo  = count(array_filter($tail, fn($v) => $v < $limiteInf));

        return $arriba >= $minimo || $abajo >= $minimo;
    }

    // ── Regla 7: n puntos seguidos dentro de 1σ (estratificación) ──
    private static function estratificacion(array $seq, float $cl, float $sigma, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        foreach ($tail as $v) {
            if (abs($v - $cl) > $sigma) return false;
        }
        return true;
    }

    // ── Regla 8: n puntos seguidos fuera de 1σ, ninguno cerca del centro ──
    private static function mezcla(array $seq, float $cl, float $sigma, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        foreach ($tail as $v) {
            if (abs($v - $cl) <= $sigma) return false;
        }
        return true;
    }
}