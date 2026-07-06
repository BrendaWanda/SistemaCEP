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
use App\Services\ReglasNelsonService;

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
    //  REGLAS DE NELSON / WESTERN ELECTRIC
    // =====================================================================
    //
    //  La lógica de las 8 reglas vive en Services/ReglasNelsonService.php
    //  (compartida con M6, ver SpcController::analizar()) — aquí solo se
    //  arma la secuencia histórica y se le pasa al servicio.
    //
    //  Se evalúan contra la secuencia cronológica de x̄ de TODAS las
    //  sesiones para ese parámetro (carta de control continua real, no
    //  solo dentro de una sesión — una sesión nueva casi nunca acumula
    //  los 9/14/15 puntos que varias reglas necesitan).
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

        if ($cl === null) {
            // Sin CL solo se puede evaluar la Regla 1 contra ucl/lcl directos.
            $reglas = [];
            if ($ucl !== null && $xbarNuevo > $ucl) $reglas[1] = 'Punto fuera de UCL (>3σ)';
            if ($lcl !== null && $xbarNuevo < $lcl) $reglas[1] = 'Punto fuera de LCL (<3σ)';
            if (empty($reglas)) return ['fuera_control' => false, 'reglas' => [], 'descripcion' => null];
            return ['fuera_control' => true, 'reglas' => [1], 'descripcion' => 'Regla 1 (' . reset($reglas) . ')'];
        }

        $sigma = $this->calcularSigma($cl, $ucl, $lcl);
        if ($sigma === null || $sigma <= 0) {
            return ['fuera_control' => false, 'reglas' => [], 'descripcion' => null];
        }

        $secuencia = $this->obtenerSecuenciaConSustitucion($parametroId, $excludeId, $xbarNuevo, 30);

        return ReglasNelsonService::evaluar($secuencia, $cl, $sigma, $ucl, $lcl);
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