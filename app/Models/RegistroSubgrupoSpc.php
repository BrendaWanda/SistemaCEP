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
    // Calcula x̄, R y S de las n lecturas, verifica la Regla 1 de Western
    // Electric (punto fuera de los límites 3-sigma) contra $limites,
    // guarda el subgrupo y devuelve su id.
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
        $valores = array_map('floatval', $lecturas);
        $n       = count($valores);

        $xbar = array_sum($valores) / $n;
        $r    = max($valores) - min($valores);

        // Desviación estándar de la muestra (n-1), para la carta S.
        // Con n=1 (parámetros individuales, carta X-MR) no aplica.
        $s = null;
        if ($n > 1) {
            $sumaCuadrados = array_sum(array_map(
                fn($v) => ($v - $xbar) ** 2, $valores
            ));
            $s = sqrt($sumaCuadrados / ($n - 1));
        }

        [$fueraControl, $reglaViolada] = $this->verificarReglas($xbar, $limites);

        $id = $this->create([
            'sesion_id'         => $sesionId,
            'parametro_id'      => $parametroId,
            'hora'              => $hora,
            'valores'           => json_encode($valores),
            'n'                 => $n,
            'promedio_xbar'     => $xbar,
            'rango_r'           => $r,
            'desv_estandar_s'   => $s,
            'fuera_de_control'  => $fueraControl ? 1 : 0,
            'regla_violada'     => $reglaViolada,
            'registrado_por_id' => $usuarioId,
        ]);

        return (int)$id;
    }

    // -------------------------------------------------------------------
    // Reglas de Western Electric sobre x̄.
    //   Regla 1: un punto fuera de los límites de control (3-sigma).
    //
    //   Las reglas 2-4 (rachas, tendencias) requieren el historial de
    //   subgrupos anteriores de esta sesión+parámetro — se pueden agregar
    //   aquí más adelante consultando reg_subgrupos_spc.
    // -------------------------------------------------------------------
    private function verificarReglas(float $xbar, array $limites): array
    {
        $ucl = isset($limites['ucl_xbar']) ? (float)$limites['ucl_xbar'] : null;
        $lcl = isset($limites['lcl_xbar']) ? (float)$limites['lcl_xbar'] : null;

        if ($ucl !== null && $xbar > $ucl) {
            return [true, 'Regla 1 - Punto fuera de UCL'];
        }
        if ($lcl !== null && $xbar < $lcl) {
            return [true, 'Regla 1 - Punto fuera de LCL'];
        }
        return [false, null];
    }

    // -------------------------------------------------------------------
    // Datos para el gráfico X̄ en tiempo real de un parámetro dentro de
    // una sesión: un punto por subgrupo registrado, en orden, más la
    // amplitud móvil (útil para cartas X-MR cuando n=1).
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

        $subgrupos = [];
        $totalFueraControl = 0;
        $anterior = null;

        foreach ($filas as $i => $fila) {
            $xbar = (float)$fila['promedio_xbar'];

            $subgrupos[] = [
                'numero'           => $i + 1,
                'id'               => (int)$fila['id'],
                'hora'             => $fila['hora'],
                'n'                => (int)$fila['n'],
                'xbar'             => $xbar,
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
            'subgrupos'           => $subgrupos,
            'total_subgrupos'     => count($subgrupos),
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