<?php
// =============================================================================
//  SIACEP — Modelo: Subgrupos SPC numéricos (X̄-R/S)
//  Archivo: app/Models/RegistroSubgrupoSpc.php
//
//  Reemplaza reg_pesos_masa_cruda / reg_control_envasado: un registro por
//  subgrupo (n lecturas) de cualquier parámetro numérico con
//  es_variable_spc = 1, sin importar la etapa. Las n lecturas se guardan
//  como JSON en `valores`, junto con x̄, R, S y el resultado de la
//  verificación de reglas de control calculados al guardar.
// =============================================================================

namespace App\Models;

use App\Core\Model;

class RegistroSubgrupoSpc extends Model
{
    protected string $table    = 'reg_subgrupos_spc';
    protected string $pk       = 'id';
    protected array  $fillable = [
        'sesion_id','parametro_id','hora','valores','n',
        'promedio_xbar','rango_r','desv_estandar_s',
        'fuera_de_control','regla_violada','registrado_por_id',
    ];

    // -------------------------------------------------------------------
    // Calcula x̄, R y S de las n lecturas, verifica las 8 reglas de Nelson
    // contra el HISTORIAL COMPLETO de ese parámetro (todas las sesiones,
    // no solo la actual), guarda el subgrupo y devuelve su id.
    //
    //   $limites = ['ucl_xbar' => ..., 'lcl_xbar' => ..., 'cl_xbar' => ...]
    // -------------------------------------------------------------------
    public function registrarSubgrupo(
        int $sesionId,
        int $parametroId,
        string $hora,
        array $lecturas,
        array $limites,
        int $usuarioId
    ): int {
        $stats  = $this->calcularStats($lecturas);
        $nelson = $this->evaluarReglasNelson($parametroId, $stats['xbar'], $limites, null);

        $id = $this->create([
            'sesion_id'         => $sesionId,
            'parametro_id'      => $parametroId,
            'hora'              => $hora,
            'valores'           => json_encode($stats['valores']),
            'n'                 => $stats['n'],
            'promedio_xbar'     => $stats['xbar'],
            'rango_r'           => $stats['r'],
            'desv_estandar_s'   => $stats['s'],
            'fuera_de_control'  => $nelson['fuera_control'] ? 1 : 0,
            'regla_violada'     => $nelson['descripcion'],
            'registrado_por_id' => $usuarioId,
        ]);

        return (int)$id;
    }

    // -------------------------------------------------------------------
    // Edita un subgrupo YA GUARDADO: recalcula x̄/R/S y vuelve a evaluar
    // las 8 reglas de Nelson con la lectura corregida, EXCLUYENDO el
    // valor viejo de este mismo subgrupo del historial (para no comparar
    // el punto corregido contra sí mismo).
    // -------------------------------------------------------------------
    public function actualizarSubgrupo(
        int $id,
        string $hora,
        array $lecturas,
        array $limites
    ): array {
        $actual = $this->find($id);
        if (!$actual) {
            throw new \RuntimeException("Subgrupo {$id} no encontrado.");
        }
        $parametroId = (int)$actual['parametro_id'];

        $stats  = $this->calcularStats($lecturas);
        $nelson = $this->evaluarReglasNelson($parametroId, $stats['xbar'], $limites, $id);

        $this->update($id, [
            'hora'             => $hora,
            'valores'          => json_encode($stats['valores']),
            'n'                => $stats['n'],
            'promedio_xbar'    => $stats['xbar'],
            'rango_r'          => $stats['r'],
            'desv_estandar_s'  => $stats['s'],
            'fuera_de_control' => $nelson['fuera_control'] ? 1 : 0,
            'regla_violada'    => $nelson['descripcion'],
        ]);

        return [
            'valores'       => $stats['valores'],
            'n'             => $stats['n'],
            'xbar'          => $stats['xbar'],
            'r'             => $stats['r'],
            's'             => $stats['s'],
            'fuera_control' => $nelson['fuera_control'],
            'regla'         => $nelson['descripcion'],
        ];
    }

    // -------------------------------------------------------------------
    // Cálculo puramente descriptivo de x̄, R y S de un subgrupo (n lecturas).
    // No decide nada sobre control estadístico — eso es evaluarReglasNelson().
    // -------------------------------------------------------------------
    private function calcularStats(array $lecturas): array
    {
        $valores = array_map('floatval', $lecturas);
        $n       = count($valores);

        $xbar = array_sum($valores) / $n;
        $r    = max($valores) - min($valores);

        $s = null;
        if ($n > 1) {
            $sumaCuadrados = array_sum(array_map(
                fn($v) => ($v - $xbar) ** 2, $valores
            ));
            $s = sqrt($sumaCuadrados / ($n - 1));
        }

        return ['valores' => $valores, 'n' => $n, 'xbar' => $xbar, 'r' => $r, 's' => $s];
    }

    // =====================================================================
    //  REGLAS DE NELSON / WESTERN ELECTRIC (las 8)
    // =====================================================================
    //
    //  Se evalúan contra la secuencia cronológica de x̄ de TODAS las
    //  sesiones para ese parámetro (carta de control continua real, no
    //  solo dentro de una sesión — una sesión nueva casi nunca acumula
    //  los 9/14/15 puntos que varias reglas necesitan). Esta es la misma
    //  fuente de datos que debe usar M6 para el análisis histórico, así
    //  que ambos módulos quedan coherentes entre sí.
    //
    //  Limitación documentada: al editar un punto que no es el más
    //  reciente, solo se re-evalúa la ventana que termina en ESE punto.
    //  Los puntos posteriores que ya tenían su propio veredicto no se
    //  recalculan en cascada — es una simplificación deliberada (recalcular
    //  toda la carta en cada edición sería costoso y rara vez cambia el
    //  diagnóstico de puntos ya evaluados independientemente).
    // =====================================================================

    private function evaluarReglasNelson(
        int $parametroId,
        float $xbarNuevo,
        array $limites,
        ?int $excludeId
    ): array {
        $cl  = isset($limites['cl_xbar'])  ? (float)$limites['cl_xbar']  : null;
        $ucl = isset($limites['ucl_xbar']) ? (float)$limites['ucl_xbar'] : null;
        $lcl = isset($limites['lcl_xbar']) ? (float)$limites['lcl_xbar'] : null;

        // Regla 1 no necesita sigma ni historial: compara directo contra
        // los límites de control ya calculados (UCL/LCL = CL ± 3σ).
        $reglas = [];
        if ($ucl !== null && $xbarNuevo > $ucl) $reglas[1] = 'Punto fuera de UCL (>3σ)';
        if ($lcl !== null && $xbarNuevo < $lcl) $reglas[1] = 'Punto fuera de LCL (<3σ)';

        // Sin CL no se puede establecer sigma ni evaluar las reglas 2-8
        // (todas dependen de zonas de 1σ/2σ/3σ alrededor de la línea central).
        if ($cl === null) {
            return $this->armarResultadoNelson($reglas);
        }

        // Sigma de x̄ a partir de los límites ya calculados. Se promedia
        // el lado superior e inferior por si los límites no son
        // perfectamente simétricos respecto al CL.
        $sigma = $this->calcularSigma($cl, $ucl, $lcl);
        if ($sigma === null || $sigma <= 0) {
            return $this->armarResultadoNelson($reglas);
        }

        $secuencia = $this->obtenerSecuenciaConSustitucion($parametroId, $excludeId, $xbarNuevo, 30);

        if ($this->reglaRachaMismoLado($secuencia, $cl, 9))                 $reglas[2] = '9 puntos seguidos del mismo lado de la línea central';
        if ($this->reglaTendencia($secuencia, 6))                          $reglas[3] = '6 puntos seguidos en tendencia continua (subiendo o bajando)';
        if ($this->reglaAlternancia($secuencia, 14))                       $reglas[4] = '14 puntos seguidos alternando arriba/abajo';
        if ($this->reglaZona($secuencia, $cl, $sigma, 2, 3, 2))            $reglas[5] = '2 de 3 puntos más allá de 2σ, mismo lado';
        if ($this->reglaZona($secuencia, $cl, $sigma, 1, 5, 4))            $reglas[6] = '4 de 5 puntos más allá de 1σ, mismo lado';
        if ($this->reglaEstratificacion($secuencia, $cl, $sigma, 15))       $reglas[7] = '15 puntos seguidos dentro de 1σ (estratificación)';
        if ($this->reglaMezcla($secuencia, $cl, $sigma, 8))                $reglas[8] = '8 puntos seguidos fuera de 1σ, sin ninguno cerca del centro (mezcla)';

        return $this->armarResultadoNelson($reglas);
    }

    private function armarResultadoNelson(array $reglas): array
    {
        if (empty($reglas)) {
            return ['fuera_control' => false, 'reglas' => [], 'descripcion' => null];
        }
        ksort($reglas);
        $primera = array_key_first($reglas);
        $descripcion = 'Regla ' . implode(', ', array_map(
            fn($n, $desc) => "{$n} ({$desc})", array_keys($reglas), $reglas
        ));
        return ['fuera_control' => true, 'reglas' => array_keys($reglas), 'descripcion' => $descripcion];
    }

    // Sigma de x̄ estimado a partir de CL y los límites de control (± 3σ).
    private function calcularSigma(float $cl, ?float $ucl, ?float $lcl): ?float
    {
        if ($ucl !== null && $lcl !== null) return (($ucl - $cl) + ($cl - $lcl)) / 2 / 3;
        if ($ucl !== null) return ($ucl - $cl) / 3;
        if ($lcl !== null) return ($cl - $lcl) / 3;
        return null;
    }

    // Secuencia cronológica de x̄ de un parámetro a través de TODAS las
    // sesiones, sustituyendo el valor de $excludeId (si se está editando)
    // por $nuevoValor en su posición cronológica original, o agregando
    // $nuevoValor al final si es un subgrupo nuevo ($excludeId = null).
    // Se limita a los últimos $maxPuntos para no cargar todo el histórico.
    private function obtenerSecuenciaConSustitucion(
        int $parametroId,
        ?int $excludeId,
        float $nuevoValor,
        int $maxPuntos
    ): array {
        $filas = $this->query(
            "SELECT rs.id, rs.promedio_xbar
            FROM reg_subgrupos_spc rs
            JOIN sesiones_registro s ON s.id = rs.sesion_id
            WHERE rs.parametro_id = ?
            ORDER BY s.fecha DESC, rs.hora DESC, rs.id DESC
            LIMIT {$maxPuntos}",
            [$parametroId]
        );
        $filas = array_reverse($filas); // orden cronológico ascendente

        $secuencia = [];
        $sustituido = false;
        foreach ($filas as $f) {
            if ($excludeId !== null && (int)$f['id'] === $excludeId) {
                $secuencia[] = $nuevoValor;
                $sustituido = true;
            } else {
                $secuencia[] = (float)$f['promedio_xbar'];
            }
        }
        if ($excludeId === null || !$sustituido) {
            $secuencia[] = $nuevoValor;
        }
        return $secuencia;
    }

    // ── Regla 2: n puntos seguidos del mismo lado de la línea central ──
    private function reglaRachaMismoLado(array $seq, float $cl, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        $arriba = array_filter($tail, fn($v) => $v > $cl);
        $abajo  = array_filter($tail, fn($v) => $v < $cl);
        return count($arriba) === $n || count($abajo) === $n;
    }

    // ── Regla 3: n puntos seguidos en tendencia continua ──
    private function reglaTendencia(array $seq, int $n): bool
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
    private function reglaAlternancia(array $seq, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        $signos = [];
        for ($i = 1; $i < count($tail); $i++) {
            $diff = $tail[$i] - $tail[$i - 1];
            if ($diff == 0) return false; // sin cambio no cuenta como alternancia
            $signos[] = $diff > 0 ? 1 : -1;
        }
        for ($i = 1; $i < count($signos); $i++) {
            if ($signos[$i] === $signos[$i - 1]) return false;
        }
        return true;
    }

    // ── Reglas 5 y 6: k de m puntos más allá de kSigma, mismo lado ──
    private function reglaZona(array $seq, float $cl, float $sigma, int $kSigma, int $m, int $minimo): bool
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
    private function reglaEstratificacion(array $seq, float $cl, float $sigma, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        foreach ($tail as $v) {
            if (abs($v - $cl) > $sigma) return false;
        }
        return true;
    }

    // ── Regla 8: n puntos seguidos fuera de 1σ, ninguno cerca del centro ──
    private function reglaMezcla(array $seq, float $cl, float $sigma, int $n): bool
    {
        if (count($seq) < $n) return false;
        $tail = array_slice($seq, -$n);
        foreach ($tail as $v) {
            if (abs($v - $cl) <= $sigma) return false;
        }
        return true;
    }

    // -------------------------------------------------------------------
    // Datos para el gráfico X̄ en tiempo real de un parámetro dentro de
    // una sesión: un punto por subgrupo registrado, en orden, más la
    // amplitud móvil (útil para cartas X-MR cuando n=1).
    //
    // Devuelve 'puntos' (no 'subgrupos') y cada punto usa 'promedio_xbar'
    // (no 'xbar') para calzar con el JS de la vista de sesión.
    // -------------------------------------------------------------------
    public function datosGrafico(
        int $sesionId,
        int $parametroId,
        float $uclXbar,
        float $lclXbar,
        float $clXbar
    ): array {
        $filas = $this->query(
            "SELECT id, hora, n, promedio_xbar, rango_r, desv_estandar_s,
                    fuera_de_control, regla_violada
            FROM reg_subgrupos_spc
            WHERE sesion_id = ? AND parametro_id = ?
            ORDER BY hora ASC, id ASC",
            [$sesionId, $parametroId]
        );

        $puntos = [];
        $totalFueraControl = 0;
        $anterior = null;

        foreach ($filas as $i => $fila) {
            $xbar = (float)$fila['promedio_xbar'];

            $puntos[] = [
                'numero'           => $i + 1,
                'id'               => (int)$fila['id'],
                'hora'             => $fila['hora'],
                'n'                => (int)$fila['n'],
                'promedio_xbar'    => $xbar,
                'rango_r'          => (float)$fila['rango_r'],
                'desv_est'         => $fila['desv_estandar_s'] !== null
                                        ? (float)$fila['desv_estandar_s'] : null,
                'amplitud_movil'   => $anterior !== null ? abs($xbar - $anterior) : null,
                'fuera_de_control' => (bool)$fila['fuera_de_control'],
                'regla_violada'    => $fila['regla_violada'],
            ];

            if ($fila['fuera_de_control']) $totalFueraControl++;
            $anterior = $xbar;
        }

        return [
            'puntos'              => $puntos,
            'total_subgrupos'     => count($puntos),
            'total_fuera_control' => $totalFueraControl,
        ];
    }

    // -------------------------------------------------------------------
    // Todos los subgrupos de un parámetro dentro de una sesión, con las
    // n lecturas individuales ya decodificadas — para mostrar en la vista
    // de la sesión (tabla de subgrupos registrados hasta el momento).
    // -------------------------------------------------------------------
    public function porSesionYParametro(int $sesionId, int $parametroId): array
    {
        $filas = $this->query(
            "SELECT * FROM reg_subgrupos_spc
            WHERE sesion_id = ? AND parametro_id = ?
            ORDER BY hora ASC, id ASC",
            [$sesionId, $parametroId]
        );

        foreach ($filas as &$fila) {
            $fila['valores'] = json_decode($fila['valores'], true);
        }

        return $filas;
    }
}