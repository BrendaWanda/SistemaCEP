<?php
// app/Controllers/M6_SPC/SpcController.php
namespace App\Controllers\M6_SPC;
use App\Core\Controller;
use App\Core\Auth;
use App\Services\ReglasNelsonService;

class SpcController extends Controller
{
    // ── Constantes carta X̄-R: d2, A2, D3, D4
    // Ref: Montgomery (2013), Cap.5, Apéndice Tabla VI
    private const CONSTANTES_R = [
        2  => ['d2'=>1.128,'A2'=>1.880,'D3'=>0,    'D4'=>3.267],
        3  => ['d2'=>1.693,'A2'=>1.023,'D3'=>0,    'D4'=>2.575],
        4  => ['d2'=>2.059,'A2'=>0.729,'D3'=>0,    'D4'=>2.282],
        5  => ['d2'=>2.326,'A2'=>0.577,'D3'=>0,    'D4'=>2.115],
        6  => ['d2'=>2.534,'A2'=>0.483,'D3'=>0,    'D4'=>2.004],
        7  => ['d2'=>2.704,'A2'=>0.419,'D3'=>0.076,'D4'=>1.924],
        8  => ['d2'=>2.847,'A2'=>0.373,'D3'=>0.136,'D4'=>1.864],
        9  => ['d2'=>2.970,'A2'=>0.337,'D3'=>0.184,'D4'=>1.816],
        10 => ['d2'=>3.078,'A2'=>0.308,'D3'=>0.223,'D4'=>1.777],
    ];

    // ── Constantes carta X̄-S: c4, A3, B3, B4
    // Ref: Montgomery (2013), Cap.5, Apéndice Tabla VI
    // Aplica cuando n >= 6 (mayor eficiencia que carta R)
    private const CONSTANTES_S = [
        6  => ['c4'=>0.9515,'A3'=>1.287,'B3'=>0.030,'B4'=>1.970],
        7  => ['c4'=>0.9594,'A3'=>1.182,'B3'=>0.118,'B4'=>1.882],
        8  => ['c4'=>0.9650,'A3'=>1.099,'B3'=>0.185,'B4'=>1.815],
        9  => ['c4'=>0.9693,'A3'=>1.032,'B3'=>0.239,'B4'=>1.761],
        10 => ['c4'=>0.9727,'A3'=>0.975,'B3'=>0.284,'B4'=>1.716],
        11 => ['c4'=>0.9754,'A3'=>0.927,'B3'=>0.321,'B4'=>1.679],
        12 => ['c4'=>0.9776,'A3'=>0.886,'B3'=>0.354,'B4'=>1.646],
        15 => ['c4'=>0.9823,'A3'=>0.789,'B3'=>0.428,'B4'=>1.572],
        20 => ['c4'=>0.9869,'A3'=>0.680,'B3'=>0.510,'B4'=>1.490],
        25 => ['c4'=>0.9896,'A3'=>0.606,'B3'=>0.565,'B4'=>1.435],
    ];

    // ── Constantes carta X-MR (n=1, rangos móviles con span=2)
    // Ref: Montgomery (2013), Cap.5, Tabla VI
    private const D2_MR = 1.128;
    private const D4_MR = 3.267;

    // Umbral para cambiar de carta R a carta S
    private const N_UMBRAL_S = 6;

    public function __construct()
    {
        parent::__construct();
        Auth::requireAccess('m6_spc');
    }

    // ═══════════════════════════════════════════════════════════
    // PÁGINA PRINCIPAL
    // ═══════════════════════════════════════════════════════════
    public function index(): void
    {
        $this->render('m6_spc/index', [
            'pageTitle'  => 'Control Estadístico de Proceso',
            'breadcrumb' => [['label' => 'SPC']],
            'productos'  => $this->getProductos(),
            'parametros' => $this->getParametrosSPC(),
            'canWrite'   => Auth::canWrite('m6_spc'),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX: Análisis SPC — detecta tipo de carta automáticamente
    // ═══════════════════════════════════════════════════════════
    public function analizar(): void
    {
        $productoId  = (int)($_GET['producto_id']  ?? 0);
        $parametroId = (int)($_GET['parametro_id'] ?? 0);
        $fechaDesde  = $_GET['fecha_desde'] ?? null;
        $fechaHasta  = $_GET['fecha_hasta'] ?? null;

        $resultado = $this->calcularAnalisis($productoId, $parametroId, $fechaDesde, $fechaHasta);
        if (isset($resultado['error'])) {
            $this->jsonError($resultado['error'], 422);
            return;
        }
        $this->jsonSuccess($resultado, 'Análisis calculado correctamente.');
    }

    // ═══════════════════════════════════════════════════════════
    // Lógica de análisis compartida entre analizar() (solo muestra en
    // pantalla) y guardarLimites() (además persiste en spc_limites_control).
    // Devuelve ['error' => '...'] si no se puede calcular, o el array
    // completo de resultados (mismo shape que antes devolvía analizar()).
    // ═══════════════════════════════════════════════════════════
    private function calcularAnalisis(
        int $productoId, int $parametroId, ?string $fechaDesde, ?string $fechaHasta
    ): array {
        if (!$productoId || !$parametroId) {
            return ['error' => 'Seleccione producto y parámetro.'];
        }
        $specs = $this->getEspecificaciones($parametroId);
        if (!$specs) {
            return ['error' => 'Parámetro no encontrado.'];
        }

        $tipoDato = $specs['tipo_dato'];
        $n        = (int)($specs['tamanio_subgrupo'] ?? 1);

        if ($tipoDato === 'seleccion' || $tipoDato === 'si_no') {
            $datos = $this->getDatosCartaP($productoId, $parametroId, $fechaDesde, $fechaHasta);
            if (count($datos) < 3) {
                return ['error' => 'Se necesitan al menos 3 lotes para la carta p. Actualmente hay ' . count($datos) . '.'];
            }
            return $this->calcularCartaP($datos, $specs);
        }

        if ($n > 1) {
            $subgrupos = $this->getSubgruposXR($productoId, $parametroId, $fechaDesde, $fechaHasta);
            if (count($subgrupos) < 3) {
                return ['error' => 'Se necesitan al menos 3 subgrupos. Actualmente hay ' . count($subgrupos) . '.'];
            }
            return $this->calcularXR($subgrupos, $specs);
        }

        $valores = $this->getValoresXMR($productoId, $parametroId, $fechaDesde, $fechaHasta);
        if (count($valores) < 3) {
            return ['error' => 'Se necesitan al menos 3 observaciones. Actualmente hay ' . count($valores) . '.'];
        }
        return $this->calcularXMR($valores, $specs);
    }

    // ═══════════════════════════════════════════════════════════
    // POST /m6/anular-limites
    //
    // Desactiva el límite vigente de un parámetro SIN reemplazarlo por
    // uno nuevo (a diferencia de guardarLimites(), que siempre supersede
    // con un cálculo nuevo). Caso de uso: Calidad determina que el
    // límite guardado ya no es confiable (ej. se calculó con datos que
    // luego resultaron comprometidos) pero todavía no tiene un análisis
    // nuevo listo. Tras anular, M2 vuelve a usar la especificación de
    // M0 (valor_min/valor_max) como respaldo hasta que se guarde un
    // nuevo análisis — nunca se borra la fila, solo se marca vigente=0,
    // así el historial de "qué límite se usó y cuándo" queda intacto.
    // ═══════════════════════════════════════════════════════════
    public function anularLimites(): void
    {
        Auth::requireWrite('m6_spc');
        $this->verifyCsrf();

        $parametroId = (int)($_POST['parametro_id'] ?? 0);
        if (!$parametroId) {
            $this->jsonError('Parámetro requerido.', 422);
            return;
        }

        $vigente = $this->db->fetchOne(
            "SELECT id FROM spc_limites_control WHERE parametro_id = ? AND vigente = 1",
            [$parametroId]
        );
        if (!$vigente) {
            $this->jsonError('Este parámetro no tiene límites vigentes para anular.', 404);
            return;
        }

        $this->db->execute(
            "UPDATE spc_limites_control SET vigente = 0 WHERE id = ?",
            [$vigente['id']]
        );

        $this->jsonSuccess(
            ['parametro_id' => $parametroId],
            '✅ Límites anulados. M2 volverá a usar la especificación de M0 (valor mín/máx) '
            . 'hasta que se guarde un nuevo análisis.'
        );
    }

    // ═══════════════════════════════════════════════════════════
    // PARETO DE PARÁMETROS CRÍTICOS
    //
    // A diferencia del resto de M6 (que analiza 1 parámetro a la vez),
    // este Pareto opera a nivel de PRODUCTO: agrega los "eventos de no
    // conformidad" de TODOS sus parámetros (atributos + variables) para
    // responder "¿cuál parámetro concentra la mayoría de los problemas?"
    // — la pregunta correcta de un Pareto, no "¿cuál lote fue peor?".
    //
    // Unificación de unidades (decisión metodológica confirmada):
    //   - Atributos (carta p)   → no conformes (reg_inspeccion_atributos)
    //   - Variables (X̄-R/X-MR)  → señales SPC detectadas (reg_subgrupos_spc)
    //
    // CORRECCIÓN METODOLÓGICA (tras revisión): NO se combinan atributos y
    // variables en un solo Pareto. Un Pareto riguroso exige que todas las
    // barras compartan la misma unidad de conteo (Montgomery, 2013, Cap.3);
    // "no conformes individuales" y "subgrupos con señal SPC" no son la
    // misma unidad. Se generan dos Paretos INDEPENDIENTES, cada uno
    // homogéneo en su propia unidad — se elige cuál ver con el filtro
    // "tipo" (atributo | variable).
    //
    // ESTRATIFICACIÓN (4ª herramienta básica de calidad — Kume, Statistical
    // Methods for Quality Improvement, Cap.5): se puede filtrar por etapa
    // "en proceso" (amasado…envasado, para detección temprana) vs.
    // "producto terminado" (última barrera antes de liberar) — mezclarlas
    // oculta que responden preguntas gerenciales distintas.
    // ═══════════════════════════════════════════════════════════

    // GET /m6/pareto — página
    public function pareto(): void
    {
        $this->render('m6_spc/pareto', [
            'pageTitle'  => 'Pareto de Parámetros Críticos',
            'breadcrumb' => [['label' => 'SPC', 'url' => APP_URL.'/m6'], ['label' => 'Pareto']],
            'productos'  => $this->getProductos(),
        ]);
    }

    // GET /m6/pareto/analizar — AJAX
    public function analizarPareto(): void
    {
        $productoId = (int)($_GET['producto_id'] ?? 0);
        $tipo       = $_GET['tipo']    ?? 'atributo'; // 'atributo' | 'variable'
        $estrato    = $_GET['estrato'] ?? 'todos';    // 'todos' | 'proceso' | 'terminado'
        $fechaDesde = $_GET['fecha_desde'] ?? null;
        $fechaHasta = $_GET['fecha_hasta'] ?? null;

        if (!$productoId) {
            $this->jsonError('Seleccione un producto.', 422);
            return;
        }
        if (!in_array($tipo, ['atributo', 'variable'], true)) {
            $this->jsonError('Tipo de Pareto inválido.', 422);
            return;
        }
        if (!in_array($estrato, ['todos', 'proceso', 'terminado'], true)) {
            $this->jsonError('Estrato inválido.', 422);
            return;
        }

        $datos = $this->getDatosPareto($productoId, $tipo, $estrato, $fechaDesde, $fechaHasta);
        if (empty($datos)) {
            $this->jsonError(
                'No hay datos de ' . ($tipo === 'atributo' ? 'atributos' : 'variables')
                . ' para este producto con estos filtros.',
                422
            );
            return;
        }

        $resultado = $this->calcularPareto($datos);
        $resultado['tipo']    = $tipo;
        $resultado['estrato'] = $estrato;
        $this->jsonSuccess($resultado);
    }

    // Eventos de no conformidad de UN SOLO tipo (atributo o variable),
    // filtrados opcionalmente por estrato de etapa.
    private function getDatosPareto(
        int $productoId, string $tipo, string $estrato, ?string $fechaDesde, ?string $fechaHasta
    ): array {
        $cond   = '';
        $params = [$productoId];
        if ($fechaDesde) { $cond .= ' AND s.fecha >= ?'; $params[] = $fechaDesde; }
        if ($fechaHasta) { $cond .= ' AND s.fecha <= ?'; $params[] = $fechaHasta; }

        if ($estrato === 'proceso')    $cond .= " AND pp.etapa != 'producto_terminado'";
        if ($estrato === 'terminado')  $cond .= " AND pp.etapa = 'producto_terminado'";

        if ($tipo === 'atributo') {
            // Atributos (carta p): eventos = no conformes, oportunidades = inspeccionado
            return $this->db->fetchAll(
                "SELECT pp.id AS parametro_id, pp.nombre AS parametro_nombre, pp.etapa,
                        COALESCE(SUM(ia.n_no_conformes), 0)  AS eventos,
                        COALESCE(SUM(ia.n_inspeccionado), 0) AS oportunidades
                FROM parametros_proceso pp
                JOIN reg_inspeccion_atributos ia ON ia.parametro_id = pp.id
                JOIN sesiones_registro s ON s.id = ia.sesion_id
                WHERE pp.producto_id = ? AND pp.es_variable_spc = 1 AND pp.activo = 1
                    AND pp.tipo_dato IN ('seleccion','si_no') {$cond}
                GROUP BY pp.id, pp.nombre, pp.etapa",
                $params
            );
        }

        // Variables (X̄-R/X-MR): eventos = señales SPC, oportunidades = subgrupos evaluados
        return $this->db->fetchAll(
            "SELECT pp.id AS parametro_id, pp.nombre AS parametro_nombre, pp.etapa,
                    COALESCE(SUM(CASE WHEN rs.fuera_de_control = 1 THEN 1 ELSE 0 END), 0) AS eventos,
                    COUNT(rs.id) AS oportunidades
            FROM parametros_proceso pp
            JOIN reg_subgrupos_spc rs ON rs.parametro_id = pp.id
            JOIN sesiones_registro s ON s.id = rs.sesion_id
            WHERE pp.producto_id = ? AND pp.es_variable_spc = 1 AND pp.activo = 1
                AND pp.tipo_dato = 'numerico' {$cond}
            GROUP BY pp.id, pp.nombre, pp.etapa",
            $params
        );
    }

    // Ordena descendente por eventos, calcula % relativo, % acumulado,
    // y marca cuántos parámetros son "vitales" (acumulan hasta el 80%).
    private function calcularPareto(array $datos): array
    {
        foreach ($datos as &$d) {
            $d['eventos']       = (int)$d['eventos'];
            $d['oportunidades'] = (int)$d['oportunidades'];
            $d['tasa_pct']      = $d['oportunidades'] > 0
                ? round($d['eventos'] / $d['oportunidades'] * 100, 2) : 0.0;
        }
        unset($d);

        usort($datos, fn($a, $b) => $b['eventos'] <=> $a['eventos']);

        $total = array_sum(array_column($datos, 'eventos'));
        $acum  = 0;
        foreach ($datos as &$d) {
            $acum += $d['eventos'];
            $d['pct_relativo']  = $total > 0 ? round($d['eventos'] / $total * 100, 2) : 0.0;
            $d['pct_acumulado'] = $total > 0 ? round($acum / $total * 100, 2) : 0.0;
        }
        unset($d);

        $vitales = 0;
        foreach ($datos as $d) {
            $vitales++;
            if ($d['pct_acumulado'] >= 80) break;
        }

        return [
            'datos'              => $datos,
            'total_eventos'      => $total,
            'total_parametros'   => count($datos),
            'parametros_vitales' => $vitales,
            'ref_biblio'         => 'Juran & Godfrey (1999), Cap.5; Montgomery (2013), Cap.3; Kume (1985), Cap.5',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // DIAGRAMA DE DISPERSIÓN — 3ª de las 7 herramientas básicas de
    // calidad que faltaba en M6 (ya están histograma y carta de
    // control). Requiere 2 parámetros NUMÉRICOS del mismo producto —
    // a diferencia del resto de M6, que analiza un parámetro a la vez.
    // ═══════════════════════════════════════════════════════════

    // GET /m6/dispersion — página
    public function dispersion(): void
    {
        $this->render('m6_spc/dispersion', [
            'pageTitle'  => 'Diagrama de Dispersión',
            'breadcrumb' => [['label' => 'SPC', 'url' => APP_URL.'/m6'], ['label' => 'Dispersión']],
            'productos'  => $this->getProductos(),
            'parametros' => $this->getParametrosSPC(), // se filtra a solo numéricos en el JS
        ]);
    }

    // GET /m6/dispersion/analizar — AJAX
    public function analizarDispersion(): void
    {
        $productoId = (int)($_GET['producto_id'] ?? 0);
        $paramXId   = (int)($_GET['parametro_x']  ?? 0);
        $paramYId   = (int)($_GET['parametro_y']  ?? 0);
        $fechaDesde = $_GET['fecha_desde'] ?? null;
        $fechaHasta = $_GET['fecha_hasta'] ?? null;

        if (!$productoId || !$paramXId || !$paramYId) {
            $this->jsonError('Seleccione el producto y ambos parámetros.', 422);
            return;
        }
        if ($paramXId === $paramYId) {
            $this->jsonError('Seleccione dos parámetros distintos para correlacionar.', 422);
            return;
        }

        $specX = $this->getEspecificaciones($paramXId);
        $specY = $this->getEspecificaciones($paramYId);
        if (!$specX || !$specY) {
            $this->jsonError('Uno de los parámetros no fue encontrado.', 404);
            return;
        }
        if ($specX['tipo_dato'] !== 'numerico' || $specY['tipo_dato'] !== 'numerico') {
            $this->jsonError('El diagrama de dispersión solo aplica a parámetros numéricos (X̄-R/X-MR), no a atributos.', 422);
            return;
        }

        $pares = $this->getParesDispersion($paramXId, $paramYId, $fechaDesde, $fechaHasta);
        if (count($pares) < 5) {
            $this->jsonError(
                'Se necesitan al menos 5 lotes con ambos parámetros medidos en la misma sesión. '
                . 'Actualmente hay ' . count($pares) . '.',
                422
            );
            return;
        }

        $this->jsonSuccess($this->calcularDispersion($pares, $specX, $specY));
    }

    // Pares (x,y) por sesión: promedia si hubo más de una lectura del mismo
    // parámetro dentro de la misma sesión, y solo incluye sesiones donde
    // AMBOS parámetros fueron medidos (INNER, vía HAVING).
    private function getParesDispersion(
        int $paramXId, int $paramYId, ?string $fechaDesde, ?string $fechaHasta
    ): array {
        $where  = ['1=1'];
        $params = [$paramXId, $paramYId, $paramXId, $paramYId];

        if ($fechaDesde) { $where[] = 's.fecha >= ?'; $params[] = $fechaDesde; }
        if ($fechaHasta) { $where[] = 's.fecha <= ?'; $params[] = $fechaHasta; }
        $whereStr = implode(' AND ', $where);

        return $this->db->fetchAll(
            "SELECT s.id AS sesion_id, s.fecha, l.codigo_lote,
                    AVG(CASE WHEN rs.parametro_id = ? THEN rs.promedio_xbar END) AS valor_x,
                    AVG(CASE WHEN rs.parametro_id = ? THEN rs.promedio_xbar END) AS valor_y
            FROM reg_subgrupos_spc rs
            JOIN sesiones_registro s ON s.id = rs.sesion_id
            JOIN lotes_produccion l  ON l.id = s.lote_id
            WHERE rs.parametro_id IN (?, ?) AND {$whereStr}
            GROUP BY s.id, s.fecha, l.codigo_lote
            HAVING valor_x IS NOT NULL AND valor_y IS NOT NULL
            ORDER BY s.fecha ASC",
            $params
        );
    }

    // Correlación de Pearson + regresión lineal simple + prueba de
    // significancia (t de Student, H0: ρ=0) contra valores críticos
    // tabulados estándar (α=0.05, dos colas).
    // Ref: Montgomery & Runger, Applied Statistics and Probability for
    // Engineers (2014), Cap.11-12; Montgomery (2013) SQC, Cap.3 (diagrama
    // de dispersión como herramienta básica de calidad).
    private function calcularDispersion(array $pares, array $specX, array $specY): array
    {
        $n  = count($pares);
        $xs = array_map(fn($p) => (float)$p['valor_x'], $pares);
        $ys = array_map(fn($p) => (float)$p['valor_y'], $pares);

        $xbar = array_sum($xs) / $n;
        $ybar = array_sum($ys) / $n;

        $sxy = 0.0; $sxx = 0.0; $syy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $xs[$i] - $xbar;
            $dy = $ys[$i] - $ybar;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }

        $r  = ($sxx > 0 && $syy > 0) ? $sxy / sqrt($sxx * $syy) : 0.0;
        $r2 = $r * $r;
        $b1 = $sxx > 0 ? $sxy / $sxx : 0.0;
        $b0 = $ybar - $b1 * $xbar;

        $gl = $n - 2;
        $tStat = ($gl > 0 && (1 - $r2) > 0) ? $r * sqrt($gl / (1 - $r2)) : 0.0;
        $rCritico = $this->rCriticoTabla($gl);
        $significativa = abs($r) > $rCritico;

        return [
            'n'              => $n,
            'pares'          => $pares,
            'x_label'        => $specX['nombre'] . ($specX['unidad'] ? ' (' . $specX['unidad'] . ')' : ''),
            'y_label'        => $specY['nombre'] . ($specY['unidad'] ? ' (' . $specY['unidad'] . ')' : ''),
            'r'              => round($r, 4),
            'r2'             => round($r2, 4),
            'b0'             => round($b0, 6),
            'b1'             => round($b1, 6),
            't_stat'         => round($tStat, 3),
            'gl'             => $gl,
            'r_critico'      => round($rCritico, 3),
            'significativa'  => $significativa,
            'interpretacion' => $this->interpretarCorrelacion($r, $significativa),
            'ref_biblio'     => 'Montgomery & Runger (2014), Cap.11-12; Montgomery (2013), Cap.3',
        ];
    }

    private function interpretarCorrelacion(float $r, bool $significativa): string
    {
        $abs = abs($r);
        $fuerza = $abs >= 0.7 ? 'fuerte' : ($abs >= 0.3 ? 'moderada' : ($abs > 0 ? 'débil' : 'nula'));
        $direccion = $r > 0 ? 'positiva' : ($r < 0 ? 'negativa' : '');
        $base = trim("Correlación {$direccion} {$fuerza}");
        return $significativa
            ? "{$base} y estadísticamente significativa (α=0.05)."
            : "{$base}, pero NO estadísticamente significativa a un 95% de confianza — "
              . 'no hay evidencia suficiente para afirmar que existe correlación real.';
    }

    // Valores críticos tabulados de r de Pearson (α=0.05, dos colas) —
    // tabla estándar presente en cualquier apéndice de estadística
    // aplicada. Interpola linealmente entre los grados de libertad
    // tabulados; para gl fuera de rango usa el valor más cercano.
    private function rCriticoTabla(int $gl): float
    {
        $tabla = [
            1=>0.997, 2=>0.950, 3=>0.878, 4=>0.811, 5=>0.754, 6=>0.707,
            7=>0.666, 8=>0.632, 9=>0.602, 10=>0.576, 12=>0.532, 15=>0.482,
            20=>0.423, 25=>0.381, 30=>0.349, 40=>0.304, 50=>0.273,
            60=>0.250, 80=>0.217, 100=>0.195, 120=>0.178,
        ];
        if ($gl <= 1) return $tabla[1];
        if ($gl >= 120) return $tabla[120];

        $glsOrdenados = array_keys($tabla);
        foreach ($glsOrdenados as $i => $g) {
            if ($g === $gl) return $tabla[$g];
            if ($g > $gl) {
                $glAnterior = $glsOrdenados[$i - 1];
                $fraccion = ($gl - $glAnterior) / ($g - $glAnterior);
                return $tabla[$glAnterior] + $fraccion * ($tabla[$g] - $tabla[$glAnterior]);
            }
        }
        return $tabla[120];
    }

    // ═══════════════════════════════════════════════════════════
    // GESTIÓN DE SEÑALES SPC
    //
    // M2 detecta y crea señales en spc_senales_detectadas, pero hasta
    // ahora nada permitía investigarlas ni cerrarlas — quedaban creadas
    // para siempre en estado 'nueva'. Este panel conecta las columnas
    // que ya existían en el esquema (asignado_a_id, causa_identificada,
    // accion_tomada, resuelta_por_id, resuelta_en) con un flujo real:
    //   nueva → investigando (al asignar) → resuelta | falsa_alarma (al resolver)
    // ═══════════════════════════════════════════════════════════

    // GET /m6/senales — página del panel
    public function senales(): void
    {
        $modelUsuario = new \App\Models\Usuario();
        $this->render('m6_spc/senales', [
            'pageTitle'   => 'Gestión de Señales SPC',
            'breadcrumb'  => [['label' => 'SPC', 'url' => APP_URL.'/m6'], ['label' => 'Señales']],
            'productos'   => $this->getProductos(),
            'usuarios'    => $modelUsuario->toSelectList('id', 'nombre'),
            'canWrite'    => Auth::canWrite('m6_spc'),
        ]);
    }

    // GET /m6/senales/listar — AJAX, filtrable por estado y producto
    public function listarSenales(): void
    {
        $estado    = $_GET['estado']     ?? 'pendientes'; // 'pendientes' agrupa nueva+investigando
        $productoId = (int)($_GET['producto_id'] ?? 0);

        $where  = ['1=1'];
        $params = [];

        if ($estado === 'pendientes') {
            $where[] = "s.estado IN ('nueva','investigando')";
        } elseif (in_array($estado, ['nueva','investigando','resuelta','falsa_alarma'], true)) {
            $where[]  = 's.estado = ?';
            $params[] = $estado;
        }
        if ($productoId) {
            $where[]  = 's.producto_id = ?';
            $params[] = $productoId;
        }
        $whereStr = implode(' AND ', $where);

        $senales = $this->db->fetchAll(
            "SELECT s.*,
                    lp.codigo_lote,
                    pr.nombre  AS producto_nombre,
                    pp.nombre  AS parametro_nombre,
                    ua.nombre  AS asignado_nombre,
                    ur.nombre  AS resuelta_por_nombre
            FROM spc_senales_detectadas s
            JOIN lotes_produccion lp ON lp.id = s.lote_id
            JOIN productos pr        ON pr.id = s.producto_id
            LEFT JOIN parametros_proceso pp ON pp.id = s.parametro_id
            LEFT JOIN usuarios ua ON ua.id = s.asignado_a_id
            LEFT JOIN usuarios ur ON ur.id = s.resuelta_por_id
            WHERE {$whereStr}
            ORDER BY s.creado_en DESC
            LIMIT 200",
            $params
        );

        $this->jsonSuccess(['senales' => $senales]);
    }

    // POST /m6/senales/:id/asignar — nueva → investigando
    public function asignarSenal(array $params): void
    {
        Auth::requireWrite('m6_spc');
        $this->verifyCsrf();

        $senalId  = (int)$params['id'];
        $usuarioId = (int)($_POST['asignado_a_id'] ?? 0);

        $senal = $this->db->fetchOne("SELECT id, estado FROM spc_senales_detectadas WHERE id = ?", [$senalId]);
        if (!$senal) { $this->jsonError('Señal no encontrada.', 404); return; }
        if (!$usuarioId) { $this->jsonError('Seleccione a quién asignar la señal.', 422); return; }

        $this->db->execute(
            "UPDATE spc_senales_detectadas
            SET asignado_a_id = ?, estado = 'investigando'
            WHERE id = ?",
            [$usuarioId, $senalId]
        );

        $this->jsonSuccess(['id' => $senalId], '✅ Señal asignada y marcada como en investigación.');
    }

    // POST /m6/senales/:id/resolver — investigando/nueva → resuelta | falsa_alarma
    public function resolverSenal(array $params): void
    {
        Auth::requireWrite('m6_spc');
        $this->verifyCsrf();

        $senalId = (int)$params['id'];
        $causa   = trim($_POST['causa_identificada'] ?? '');
        $accion  = trim($_POST['accion_tomada'] ?? '');
        $estadoFinal = $_POST['estado_final'] ?? 'resuelta';

        $senal = $this->db->fetchOne("SELECT id FROM spc_senales_detectadas WHERE id = ?", [$senalId]);
        if (!$senal) { $this->jsonError('Señal no encontrada.', 404); return; }
        if (!in_array($estadoFinal, ['resuelta', 'falsa_alarma'], true)) {
            $this->jsonError('Estado final inválido.', 422);
            return;
        }
        if ($causa === '') {
            $this->jsonError('Indique la causa identificada (o el motivo si es falsa alarma).', 422);
            return;
        }

        $this->db->execute(
            "UPDATE spc_senales_detectadas
            SET causa_identificada = ?, accion_tomada = ?,
                estado = ?, resuelta_por_id = ?, resuelta_en = NOW()
            WHERE id = ?",
            [$causa, $accion ?: null, $estadoFinal, Auth::id(), $senalId]
        );

        $msg = $estadoFinal === 'resuelta'
            ? '✅ Señal marcada como resuelta.'
            : '✅ Señal marcada como falsa alarma.';
        $this->jsonSuccess(['id' => $senalId], $msg);
    }

    // ═══════════════════════════════════════════════════════════
    // GET /m6/limites-vigentes — límites actualmente vigentes de un
    // parámetro (o null si nunca se guardó ninguno). Se usa en la UI
    // para mostrar "esto es lo que M2 está usando ahora mismo" ANTES de
    // sobrescribir con un nuevo cálculo — parte del flujo de usabilidad
    // para no reemplazar límites vigentes a ciegas.
    // ═══════════════════════════════════════════════════════════
    public function limitesVigentes(): void
    {
        $parametroId = (int)($_GET['parametro_id'] ?? 0);
        if (!$parametroId) {
            $this->jsonError('Parámetro requerido.', 422);
            return;
        }

        $vigente = $this->db->fetchOne(
            "SELECT lc.*, u.nombre AS calculado_por_nombre
            FROM spc_limites_control lc
            LEFT JOIN usuarios u ON u.id = lc.calculado_por_id
            WHERE lc.parametro_id = ? AND lc.vigente = 1
            ORDER BY lc.calculado_en DESC LIMIT 1",
            [$parametroId]
        );

        $this->jsonSuccess(['vigente' => $vigente ?: null]);
    }

    // ═══════════════════════════════════════════════════════════
    // POST /m6/guardar-limites
    //
    // Recalcula el análisis desde cero (nunca confía en números que
    // pudo haber mandado el navegador) y lo persiste en
    // spc_limites_control como el límite vigente para ese parámetro,
    // marcando como no vigente cualquier fila anterior del mismo
    // parámetro. Esta es la conexión que faltaba entre M6 (calcula
    // Fase I) y M2 (consume los límites en tiempo real).
    // ═══════════════════════════════════════════════════════════
    public function guardarLimites(): void
    {
        Auth::requireWrite('m6_spc');
        $this->verifyCsrf();

        $productoId  = (int)($_POST['producto_id']  ?? 0);
        $parametroId = (int)($_POST['parametro_id'] ?? 0);
        $fechaDesde  = $_POST['fecha_desde'] ?? null;
        $fechaHasta  = $_POST['fecha_hasta'] ?? null;
        $confirmarInestable = !empty($_POST['confirmar_inestable']);

        $r = $this->calcularAnalisis($productoId, $parametroId, $fechaDesde, $fechaHasta);
        if (isset($r['error'])) {
            $this->jsonError($r['error'], 422);
            return;
        }

        $procesoEstable = empty($r['violaciones']);

        // No se guarda a ciegas un proceso inestable como límite vigente
        // (Montgomery 2013, Cap.6, p.354: Cp/Cpk no son válidos con
        // proceso fuera de control, y usar esos límites como operativos
        // perpetuaría la inestabilidad). Se exige confirmación explícita.
        if (!$procesoEstable && !$confirmarInestable) {
            $this->jsonError(
                'El proceso muestra ' . count($r['violaciones']) . ' señal(es) de descontrol. '
                . 'Los límites calculados sobre un proceso inestable no son representativos '
                . '(Montgomery, 2013, Cap.6, p.354). Confirme si desea guardarlos de todas formas.',
                409,
                ['requiere_confirmacion' => true, 'violaciones' => count($r['violaciones'])]
            );
            return;
        }

        $tipoCarta = $r['tipo_carta']; // 'p' | 'xr'
        $n         = (int)$r['n'];
        $k         = (int)$r['k'];

        // Rango de fechas real de los datos usados (no lo que el usuario
        // tecleó en el filtro, por si lo dejó vacío)
        $fechasReales = array_column($r['subgrupos'], 'fecha');
        $fDesde = $fechaDesde ?: (min($fechasReales) ?: date('Y-m-d'));
        $fHasta = $fechaHasta ?: (max($fechasReales) ?: date('Y-m-d'));
        // 'fecha' en $subgrupos viene como "YYYY-MM-DD (turno)" vía fecha_label
        // en algunos casos; nos aseguramos de tomar solo la parte de fecha.
        $fDesde = substr((string)$fDesde, 0, 10);
        $fHasta = substr((string)$fHasta, 0, 10);

        $data = [
            'producto_id'        => $productoId,
            'parametro_id'       => $parametroId,
            'fecha_desde'        => $fDesde,
            'fecha_hasta'        => $fHasta,
            'n_subgrupos'        => $k,
            'tamanio_subgrupo'   => $n,
            'ucl_xbar'           => $r['limites_xbar']['lcs']   ?? null,
            'cl_xbar'            => $r['limites_xbar']['media'] ?? null,
            'lcl_xbar'           => $r['limites_xbar']['lci']   ?? null,
            'pct_fuera_control'  => $k > 0 ? round(count($r['violaciones']) / $k * 100, 2) : 0,
            'proceso_estable'    => $procesoEstable ? 1 : 0,
            'calculado_por_id'   => Auth::id(),
            'calculado_en'       => date('Y-m-d H:i:s'),
            'vigente'            => 1,
        ];

        if ($tipoCarta === 'p') {
            // Carta p: no aplica R/S ni Cp/Cpk (son índices para variables
            // continuas) — gran_media_xbarbar guarda p̄ por conveniencia
            // de reutilizar la misma columna, con nivel_sigma y ppm sí
            // aplicables (se derivan directo de p̄).
            $data['gran_media_xbarbar'] = $r['pbar'];
            $data['media_rangos_rbar']  = null;
            $data['desv_est_proceso']   = $r['estadisticos']['desv_lp'] ?? null;
            $data['tipo_secundaria']    = 'r';
            $data['ucl_r']              = null;
            $data['cl_r']               = null;
            $data['lcl_r']              = null;
            $data['lse']                = null;
            $data['lie']                = null;
            $data['cp']                 = null;
            $data['cpk']                = null;
            $data['pp']                 = null;
            $data['ppk']                = null;
            $data['ppm_cp']             = $r['capacidad']['ppm_cp'] ?? null;
            $data['ppm_lp']             = $r['capacidad']['ppm_lp'] ?? null;
            $data['nivel_sigma']        = null;
        } else {
            $data['gran_media_xbarbar'] = $r['estadisticos']['media']   ?? null;
            $data['media_rangos_rbar']  = $r['limites_r']['media']      ?? null;
            $data['desv_est_proceso']   = $r['estadisticos']['desv_lp'] ?? null;
            $data['tipo_secundaria']    = $r['usa_carta_s'] ? 's' : 'r';
            $data['ucl_r']              = $r['limites_r']['lcs']   ?? null;
            $data['cl_r']               = $r['limites_r']['media'] ?? null;
            $data['lcl_r']              = $r['limites_r']['lci']   ?? null;
            $data['lse']                = $r['specs']['lse'] ?: null;
            $data['lie']                = $r['specs']['lie'] ?: null;
            $cap = $r['capacidad'];
            $data['cp']          = is_numeric($cap['cp'])  ? $cap['cp']  : null;
            $data['cpk']         = is_numeric($cap['cpk']) ? $cap['cpk'] : null;
            $data['pp']          = is_numeric($cap['pp'])  ? $cap['pp']  : null;
            $data['ppk']         = is_numeric($cap['ppk']) ? $cap['ppk'] : null;
            $data['ppm_cp']      = is_numeric($cap['ppm_cp']) ? $cap['ppm_cp'] : null;
            $data['ppm_lp']      = is_numeric($cap['ppm_lp']) ? $cap['ppm_lp'] : null;
            $data['nivel_sigma'] = is_numeric($cap['sigma_nivel']) ? $cap['sigma_nivel'] : null;
        }

        $this->db->transaction(function($db) use ($data, $parametroId) {
            // Supersede: el límite anterior de este parámetro deja de ser vigente
            $db->execute(
                "UPDATE spc_limites_control SET vigente = 0 WHERE parametro_id = ? AND vigente = 1",
                [$parametroId]
            );

            $cols  = implode(',', array_keys($data));
            $marks = implode(',', array_fill(0, count($data), '?'));
            $db->execute(
                "INSERT INTO spc_limites_control ({$cols}) VALUES ({$marks})",
                array_values($data)
            );
        });

        $this->jsonSuccess(
            ['parametro_id' => $parametroId, 'vigente_desde' => $data['calculado_en']],
            '✅ Límites de control guardados como vigentes. M2 los usará desde ahora para detectar señales en tiempo real.'
        );
    }

    // ═══════════════════════════════════════════════════════════
    // CÁLCULO CARTA p
    // Ref: Montgomery (2013), Cap.7
    // ═══════════════════════════════════════════════════════════
    private function calcularCartaP(array $datos, array $specs): array
    {
        $k  = count($datos);
        $ns = array_map(fn($d) => (int)($d['n_inspeccionado'] ?? $specs['tamanio_subgrupo'] ?? 50), $datos);
        $ni = max(1, (int)round(array_sum($ns) / count($ns)));

        $proporciones = array_map(
            fn($d, $i) => $ns[$i] > 0 ? (float)$d['no_conformes'] / $ns[$i] : 0,
            $datos, array_keys($datos)
        );
        $totalNoConf = array_sum(array_column($datos, 'no_conformes'));
        $pbar        = $totalNoConf / ($k * $ni);

        // Límites — Montgomery (2013), Cap.7, Ec.7.7
        $factor = sqrt($pbar * (1 - $pbar) / $ni);
        $lcs_p  = $pbar + 3 * $factor;
        $lci_p  = max(0, $pbar - 3 * $factor);

        $curvaOC_p = $this->calcularCurvaOC_p($pbar, $ni, $lcs_p, $lci_p);

        // Violaciones Nelson — mismo Service compartido que M2 y la carta
        // X̄-R/X̄-S de este mismo controller (ver reglasNelson() más abajo).
        // Antes esto tenía una implementación propia con solo 3 reglas
        // adaptadas; ahora la carta p tiene las 8 reglas completas, igual
        // que las demás cartas.
        $violacionesPorPunto = ReglasNelsonService::evaluarSerieCompleta(
            $proporciones, $pbar, $factor, $lcs_p, $lci_p
        );
        $violaciones = [];
        foreach ($violacionesPorPunto as $i => $r) {
            foreach ($r['reglas'] as $numRegla) {
                $violaciones[] = [
                    'subgrupo' => $i + 1,
                    'regla'    => $numRegla,
                    'desc'     => ReglasNelsonService::DESCRIPCIONES[$numRegla] ?? "Regla {$numRegla}",
                ];
            }
        }

        $histograma  = $this->calcularHistograma($proporciones, 0, $lcs_p * 1.5);
        $fechas_p    = array_map(fn($d) => $d['fecha_label'], $datos);
        $runChart    = $this->calcularRunChart($proporciones, $fechas_p);

        $sgTabla = array_map(fn($d, $i) => [
            'numero'       => $i + 1,
            'fecha'        => $d['fecha_label'],
            'lote_ref'     => $d['lote_ref'] ?? '—',
            'origen'       => $d['origen']   ?? '—',
            'n_obs'        => $ni,
            'no_conformes' => (int)$d['no_conformes'],
            'conformes'    => $ni - (int)$d['no_conformes'],
            'proporcion'   => round($proporciones[$i], 4),
            'porcentaje'   => round($proporciones[$i] * 100, 2),
            'estado'       => $proporciones[$i] > $lcs_p ? 'fuera' : 'ok',
        ], $datos, array_keys($datos));

        $advertencias = [];
        if ($k < 25) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'FASE1_K_INSUFICIENTE',
                'titulo' => 'Lotes insuficientes para Fase I',
                'msg'    => "k={$k} lotes. Montgomery (2013, Cap.7, p.304) recomienda k≥25.",
                'ref'    => 'Montgomery (2013), Cap.7, p.304'];
        }
        $criterio1 = round($pbar * $ni, 2);
        if ($criterio1 < 5) {
            $advertencias[] = ['tipo' => 'danger', 'codigo' => 'N_INSUFICIENTE_CARTA_P',
                'titulo' => 'Tamaño de muestra insuficiente',
                'msg'    => "p̄·n={$criterio1}<5. Se necesita n≥" . ceil(5 / max($pbar, 0.0001)) . " unidades.",
                'ref'    => 'Montgomery (2013), Cap.7, p.296'];
        }
        if (count($violaciones) > 0) {
            $nv = count($violaciones);
            $advertencias[] = ['tipo' => 'danger', 'codigo' => 'PROCESO_INESTABLE',
                'titulo' => 'Proceso fuera de control — carta p',
                'msg'    => "{$nv} señal(es). Identifique causas asignables antes de Fase II.",
                'ref'    => 'Montgomery (2013), Cap.7, p.306'];
        }
        if (($pbar - 3 * $factor) < 0) {
            $advertencias[] = ['tipo' => 'info', 'codigo' => 'LCI_TRUNCADO',
                'titulo' => 'LCI truncado a 0',
                'msg'    => 'LCI negativo truncado a 0. LCI=máx(0, p̄−3√(p̄(1-p̄)/n)).',
                'ref'    => 'Montgomery (2013), Cap.7, p.296'];
        }

        return [
            'tipo_carta'      => 'p',
            'subtipo_carta'   => 'p',
            'n'               => $ni,
            'k'               => $k,
            'subgrupos'       => $sgTabla,
            'limites_p'       => ['lcs' => round($lcs_p, 4), 'lci' => round($lci_p, 4), 'media' => round($pbar, 4)],
            'limites_xbar'    => ['lcs' => round($lcs_p, 4), 'lci' => round($lci_p, 4), 'media' => round($pbar, 4)],
            'limites_r'       => ['lcs' => 0, 'lci' => 0, 'media' => 0],
            'specs'           => ['lse' => round($lcs_p, 4), 'lie' => round($lci_p, 4), 'nominal' => round($pbar, 4), 'unidad' => 'proporción'],
            'estadisticos'    => [
                'media'   => round($pbar, 4),
                'mediana' => round($this->mediana($proporciones), 4),
                'desv_cp' => round($factor, 4),
                'desv_lp' => round($this->desviacionMuestral($proporciones), 4),
                'cv_pct'  => $pbar > 0 ? round($factor / $pbar * 100, 2) : 0,
                'minimo'  => round(min($proporciones), 4),
                'maximo'  => round(max($proporciones), 4),
                'n_total' => $k * $ni,
                'arl0'    => 370,
            ],
            'capacidad'       => [
                'cp' => '—', 'cpk' => '—', 'cpu' => '—', 'cpl' => '—',
                'pp' => '—', 'ppk' => '—', 'ppu' => '—', 'ppl' => '—',
                'ppm_cp' => round($pbar * 1_000_000), 'ppm_lp' => round($pbar * 1_000_000),
                'sigma_nivel' => '—', 'lse' => '—', 'lie' => '—', 'tolerancia' => '—',
                'ic_cp' => null, 'ic_cpk' => null,
            ],
            'violaciones'     => $violaciones,
            'advertencias'    => $advertencias,
            'histograma'      => $histograma,
            'run_chart'       => $runChart,
            'xbars'           => array_map(fn($v) => round($v, 4), $proporciones),
            'rangos'          => [],
            'todos_valores'   => $proporciones,
            'pbar'            => round($pbar, 4),
            'pbar_pct'        => round($pbar * 100, 2),
            'total_no_conf'   => $totalNoConf,
            'total_insp'      => $k * $ni,
            'fase'            => 'I',
            'k_recomendado'   => 25,
            'curva_oc'        => $curvaOC_p,
            'anova'           => [],
            'indices_adic'    => [],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CÁLCULO X̄-R o X̄-S (dinámico según n)
    // n < 6  → X̄-R (carta de rangos)
    // n >= 6 → X̄-S (carta de desviaciones estándar)
    // Ref: Montgomery (2013), Cap.5, p.227-238
    // ═══════════════════════════════════════════════════════════
    private function calcularXR(array $subgrupos, array $specs): array
    {
        $nCnt = array_count_values(array_map('intval', array_column($subgrupos, 'n')));
        arsort($nCnt);
        $n = max(2, min(25, (int)key($nCnt)));
        $k = count($subgrupos);

        $usarCartaS = $n >= self::N_UMBRAL_S;

        // Valores raw de todos los subgrupos (para estadísticos globales)
        $allVals = [];
        foreach ($subgrupos as $sg) {
            foreach ($sg['valores_raw'] as $v) {
                if ($v !== null && (float)$v > 0) $allVals[] = (float)$v;
            }
        }
        $N         = count($allVals);
        $mediaGlob = $N > 0 ? array_sum($allVals) / $N : 0;
        $sigmaLP   = $N > 1 ? $this->desviacionMuestral($allVals) : 0;
        $xbars     = array_map(fn($sg) => (float)$sg['xbar'], $subgrupos);
        $xdbar     = array_sum($xbars) / $k;

        if ($usarCartaS) {
            // ── CARTA X̄-S ──────────────────────────────────────
            // Ref: Montgomery (2013), Cap.5, Ec.5.41-5.43
            $cs = $this->getConstantesS($n);

            $sValues = [];
            foreach ($subgrupos as $sg) {
                $vals_sg = array_filter($sg['valores_raw'], fn($v) => $v !== null && (float)$v > 0);
                if (count($vals_sg) >= 2) {
                    $sValues[] = $this->desviacionMuestral(array_values($vals_sg));
                }
            }
            $sbar    = count($sValues) > 0 ? array_sum($sValues) / count($sValues) : 0;
            $sigmaCP = $cs['c4'] > 0 ? $sbar / $cs['c4'] : 0;

            $lcs_xbar = $xdbar + $cs['A3'] * $sbar;
            $lci_xbar = $xdbar - $cs['A3'] * $sbar;
            $lcs_s    = $cs['B4'] * $sbar;
            $lci_s    = $cs['B3'] * $sbar;

            $subtipo            = 'xs';
            $valorSec           = $sValues;
            $limitesSecundarios = ['lcs' => round($lcs_s, 4), 'lci' => round($lci_s, 4), 'media' => round($sbar, 4)];
            $labelSec           = 'S (Desv.Est.)';
            $constInfo          = "A₃={$cs['A3']}, B₃={$cs['B3']}, B₄={$cs['B4']}, c₄={$cs['c4']}";

        } else {
            // ── CARTA X̄-R ──────────────────────────────────────
            // Ref: Montgomery (2013), Cap.5, Ec.5.5-5.6
            $cr = self::CONSTANTES_R[$n] ?? self::CONSTANTES_R[5];

            $rangos  = array_map(fn($sg) => (float)$sg['rango_r'], $subgrupos);
            $rdbar   = array_sum($rangos) / $k;
            $sigmaCP = $rdbar / $cr['d2'];

            $lcs_xbar = $xdbar + $cr['A2'] * $rdbar;
            $lci_xbar = $xdbar - $cr['A2'] * $rdbar;
            $lcs_r    = $cr['D4'] * $rdbar;
            $lci_r    = $cr['D3'] * $rdbar;

            $subtipo            = 'xr';
            $valorSec           = $rangos;
            $limitesSecundarios = ['lcs' => round($lcs_r, 4), 'lci' => round($lci_r, 4), 'media' => round($rdbar, 4)];
            $labelSec           = 'R (Rango)';
            $constInfo          = "A₂={$cr['A2']}, D₃={$cr['D3']}, D₄={$cr['D4']}, d₂={$cr['d2']}";
        }

        $capacidad   = $this->calcularCapacidad($xdbar, $sigmaCP, $sigmaLP, $specs);
        $violaciones = $this->reglasNelson($xbars, $xdbar, $sigmaCP / sqrt($n), $lcs_xbar, $lci_xbar);

        $violPorRegla = [];
        foreach ($violaciones as $viol) {
            $violPorRegla[$viol['subgrupo'] - 1][] = $viol['regla'];
        }

        $allValsSort = $allVals; sort($allValsSort);
        $mediana     = $this->mediana($allValsSort);
        $cv          = $mediaGlob != 0 ? ($sigmaLP / abs($mediaGlob)) * 100 : 0;
        $histograma  = !empty($allVals) ? $this->calcularHistograma($allVals, (float)$specs['lie'], (float)$specs['lse']) : [];

        $normalidad = $this->andersonDarling($allVals);
        $qqPlot     = $this->qqPlot($allVals, $mediaGlob, $sigmaLP);
        $curvaCP    = !empty($allVals) ? $this->curvaNomal($mediaGlob, $sigmaCP, min($allVals), max($allVals)) : [];
        $curvaLP    = !empty($allVals) ? $this->curvaNomal($mediaGlob, $sigmaLP, min($allVals), max($allVals)) : [];
        $curvaOC    = $this->calcularCurvaOC($n);
        $anova      = $this->calcularANOVA($subgrupos, $mediaGlob);
        $St         = $anova['St'] ?? $sigmaLP;
        $indicesAdc = $this->calcularIndicesAdicionales($mediaGlob, $sigmaCP, $sigmaLP, $specs, $St);
        $fechas     = array_map(fn($sg) => $sg['fecha_label'], $subgrupos);
        $runChart   = $this->calcularRunChart($xbars, $fechas);

        $icCp  = $this->intervaloConfianzaCp($capacidad['cp'],  $N);
        $icCpk = $this->intervaloConfianzaCpk($capacidad['cpk'], $N);
        $capacidad['ic_cp']  = $icCp;
        $capacidad['ic_cpk'] = $icCpk;
        $capacidad['arl0']   = 370;

        $advertencias = [];
        if ($k < 25) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'FASE1_K_INSUFICIENTE',
                'titulo' => 'Subgrupos insuficientes para Fase I',
                'msg'    => "k={$k}. Montgomery (2013, Cap.5, p.241) recomienda k≥25.",
                'ref'    => 'Montgomery (2013), Cap.5, p.241'];
        }
        if (count($violaciones) > 0) {
            $nViol = count($violaciones);
            $advertencias[] = ['tipo' => 'danger', 'codigo' => 'PROCESO_INESTABLE',
                'titulo' => 'Proceso fuera de control estadístico',
                'msg'    => "{$nViol} señal(es). Cp/Cpk NO son válidos con proceso inestable.",
                'ref'    => 'Montgomery (2013), Cap.6, p.354'];
        }
        if ($normalidad['normal'] === false) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'NO_NORMALIDAD',
                'titulo' => 'Datos no normales — índices con cautela',
                'msg'    => "AD={$normalidad['ad']}, p={$normalidad['p_value']}<0.05. Cp, Cpk y PPM asumen normalidad.",
                'ref'    => 'Montgomery (2013), Cap.6, p.354'];
        }
        if (!$usarCartaS && $n < 7) {
            $advertencias[] = ['tipo' => 'info', 'codigo' => 'R_LCI_CERO',
                'titulo' => "LCI carta R = 0 (n={$n} < 7)",
                'msg'    => "D₃=0 para n={$n}. La carta R no detecta reducciones de variabilidad con n pequeño.",
                'ref'    => 'Montgomery (2013), Cap.5, Tabla VI'];
        }
        if ($usarCartaS) {
            $advertencias[] = ['tipo' => 'info', 'codigo' => 'CARTA_S_ACTIVA',
                'titulo' => "Carta X̄-S activa (n={$n} ≥ 6)",
                'msg'    => "Con n≥6 la carta S es más eficiente que la carta R. Constantes: {$constInfo}.",
                'ref'    => 'Montgomery (2013), Cap.5, p.238'];
        }

        // Tabla de subgrupos — desv_est y CV% vienen de $sg['desv_est']
        // calculado en getSubgruposGenerico()
        $sgTabla = array_map(function ($sg, $i) use ($valorSec, $violPorRegla) {
            $desvEst = (float)($sg['desv_est'] ?? 0);
            $xbar    = (float)$sg['xbar'];
            $cv      = ($xbar != 0 && $desvEst > 0) ? round($desvEst / abs($xbar) * 100, 2) : 0;
            return [
                'numero'          => $i + 1,
                'fecha'           => $sg['fecha_label'],
                'lote_ref'        => $sg['lote_ref'] ?? '—',
                'origen'          => $sg['origen']   ?? '—',
                'n_obs'           => (int)$sg['n'],
                'xbar'            => round($xbar, 4),
                'rango'           => round((float)($valorSec[$i] ?? 0), 4),
                'desv_est'        => $desvEst > 0 ? round($desvEst, 4) : '—',
                'minimo'          => round((float)$sg['minimo'], 4),
                'maximo'          => round((float)$sg['maximo'], 4),
                'cv'              => $cv,
                'reglas_violadas' => $violPorRegla[$i] ?? [],
            ];
        }, $subgrupos, array_keys($subgrupos));

        return [
            'tipo_carta'        => 'xr',
            'subtipo_carta'     => $subtipo,
            'usa_carta_s'       => $usarCartaS,
            'n'                 => $n,
            'k'                 => $k,
            'label_secundaria'  => $labelSec,
            'const_info'        => $constInfo,
            'subgrupos'         => $sgTabla,
            'limites_xbar'      => ['lcs' => round($lcs_xbar, 4), 'lci' => round($lci_xbar, 4), 'media' => round($xdbar, 4)],
            'limites_r'         => $limitesSecundarios,
            'limites_s'         => $usarCartaS ? $limitesSecundarios : null,
            'specs'             => $this->buildSpecs($specs),
            'estadisticos'      => $this->buildEstadisticos($mediaGlob, $mediana, $sigmaCP, $sigmaLP, $cv, $allVals, $N),
            'capacidad'         => $capacidad,
            'anova'             => $anova,
            'indices_adic'      => $indicesAdc,
            'normalidad'        => $normalidad,
            'qq_plot'           => $qqPlot,
            'curva_cp'          => $curvaCP,
            'curva_lp'          => $curvaLP,
            'curva_oc'          => $curvaOC,
            'run_chart'         => $runChart,
            'violaciones'       => $violaciones,
            'viol_por_subgrupo' => $violPorRegla,
            'advertencias'      => $advertencias,
            'histograma'        => $histograma,
            'xbars'             => array_map(fn($v) => round($v, 4), $xbars),
            'rangos'            => array_map(fn($v) => round((float)$v, 4), $valorSec),
            'todos_valores'     => $allVals,
            'fase'              => 'I',
            'k_recomendado'     => 25,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CÁLCULO X-MR
    // Ref: Montgomery (2013), Cap.5
    // ═══════════════════════════════════════════════════════════
    private function calcularXMR(array $valores, array $specs): array
    {
        $vals = array_map('floatval', array_column($valores, 'valor'));
        $k    = count($vals);
        $xbar = array_sum($vals) / $k;

        $mr = [];
        for ($i = 1; $i < $k; $i++) $mr[] = abs($vals[$i] - $vals[$i - 1]);
        $mrbar   = count($mr) > 0 ? array_sum($mr) / count($mr) : 0;
        $sigmaCP = $mrbar / self::D2_MR;
        $sigmaLP = $this->desviacionMuestral($vals);

        $ucl_x  = $xbar + 3 * $sigmaCP;
        $lcl_x  = $xbar - 3 * $sigmaCP;
        $ucl_mr = self::D4_MR * $mrbar;

        $capacidad   = $this->calcularCapacidad($xbar, $sigmaCP, $sigmaLP, $specs);
        $violaciones = $this->reglasNelson($vals, $xbar, $sigmaCP, $ucl_x, $lcl_x);

        $violPorRegla = [];
        foreach ($violaciones as $viol) {
            $violPorRegla[$viol['subgrupo'] - 1][] = $viol['regla'];
        }

        $valsSort   = $vals; sort($valsSort);
        $mediana    = $this->mediana($valsSort);
        $cv         = $xbar != 0 ? ($sigmaLP / abs($xbar)) * 100 : 0;
        $histograma = $this->calcularHistograma($valsSort, (float)$specs['lie'], (float)$specs['lse']);

        $normalidad = $this->andersonDarling($vals);
        $qqPlot     = $this->qqPlot($vals, $xbar, $sigmaLP);
        $curvaCP    = $this->curvaNomal($xbar, $sigmaCP, min($vals), max($vals));
        $curvaLP    = $this->curvaNomal($xbar, $sigmaLP, min($vals), max($vals));
        $curvaOC    = $this->calcularCurvaOC(1);
        $indicesAdc = $this->calcularIndicesAdicionales($xbar, $sigmaCP, $sigmaLP, $specs, $sigmaLP);
        $fechas     = array_map(fn($row) => $row['fecha_label'], $valores);
        $runChart   = $this->calcularRunChart($vals, $fechas);

        $icCp  = $this->intervaloConfianzaCp($capacidad['cp'],  $k);
        $icCpk = $this->intervaloConfianzaCpk($capacidad['cpk'], $k);
        $capacidad['ic_cp']  = $icCp;
        $capacidad['ic_cpk'] = $icCpk;
        $capacidad['arl0']   = 370;

        $advertencias = [];
        if ($k < 20) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'FASE1_K_INSUFICIENTE',
                'titulo' => 'Observaciones insuficientes para Fase I',
                'msg'    => "k={$k}. Montgomery (2013, Cap.5, p.253) recomienda k≥20.",
                'ref'    => 'Montgomery (2013), Cap.5, p.253'];
        }
        if ($k < 10) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'XMR_SIGMA_INESTABLE',
                'titulo' => 'Estimación σ̂ inestable (k < 10)',
                'msg'    => "σ̂=MR̄/d₂ es inestable con k={$k}. Se recomiendan al menos 10 observaciones.",
                'ref'    => 'Montgomery (2013), Cap.5, p.253'];
        }
        if (count($violaciones) > 0) {
            $nViol = count($violaciones);
            $advertencias[] = ['tipo' => 'danger', 'codigo' => 'PROCESO_INESTABLE',
                'titulo' => 'Proceso fuera de control',
                'msg'    => "{$nViol} señal(es). Cp/Cpk NO válidos con proceso inestable.",
                'ref'    => 'Montgomery (2013), Cap.6, p.354'];
        }
        if ($normalidad['normal'] === false) {
            $advertencias[] = ['tipo' => 'warning', 'codigo' => 'NO_NORMALIDAD',
                'titulo' => 'Datos no normales — índices con cautela',
                'msg'    => "AD={$normalidad['ad']}, p={$normalidad['p_value']}<0.05.",
                'ref'    => 'Montgomery (2013), Cap.6, p.354'];
        }

        // Para X-MR con n=1 no tiene sentido S ni CV por subgrupo
        $sgTabla = array_map(fn($row, $i) => [
            'numero'          => $i + 1,
            'fecha'           => $row['fecha_label'],
            'lote_ref'        => $row['lote_ref'] ?? '—',
            'origen'          => $row['origen']   ?? '—',
            'n_obs'           => 1,
            'xbar'            => round((float)$row['valor'], 4),
            'rango'           => isset($mr[$i - 1]) ? round($mr[$i - 1], 4) : '—',
            'desv_est'        => '—',
            'minimo'          => round((float)$row['valor'], 4),
            'maximo'          => round((float)$row['valor'], 4),
            'cv'              => '—',
            'reglas_violadas' => $violPorRegla[$i] ?? [],
        ], $valores, array_keys($valores));

        return [
            'tipo_carta'        => 'xmr',
            'subtipo_carta'     => 'xmr',
            'usa_carta_s'       => false,
            'n'                 => 1,
            'k'                 => $k,
            'label_secundaria'  => 'MR (Rango Móvil)',
            'subgrupos'         => $sgTabla,
            'limites_x'         => ['lcs' => round($ucl_x, 4), 'lci' => round($lcl_x, 4), 'media' => round($xbar, 4)],
            'limites_xbar'      => ['lcs' => round($ucl_x, 4), 'lci' => round($lcl_x, 4), 'media' => round($xbar, 4)],
            'limites_mr'        => ['lcs' => round($ucl_mr, 4), 'lci' => 0, 'media' => round($mrbar, 4)],
            'limites_r'         => ['lcs' => round($ucl_mr, 4), 'lci' => 0, 'media' => round($mrbar, 4)],
            'specs'             => $this->buildSpecs($specs),
            'estadisticos'      => $this->buildEstadisticos($xbar, $mediana, $sigmaCP, $sigmaLP, $cv, $vals, $k),
            'capacidad'         => $capacidad,
            'anova'             => [],
            'indices_adic'      => $indicesAdc,
            'normalidad'        => $normalidad,
            'qq_plot'           => $qqPlot,
            'curva_cp'          => $curvaCP,
            'curva_lp'          => $curvaLP,
            'curva_oc'          => $curvaOC,
            'run_chart'         => $runChart,
            'violaciones'       => $violaciones,
            'viol_por_subgrupo' => $violPorRegla,
            'advertencias'      => $advertencias,
            'histograma'        => $histograma,
            'xbars'             => array_map(fn($v) => round($v, 4), array_column($valores, 'valor')),
            'rangos'            => array_map(fn($v) => round($v, 4), $mr),
            'rangos_mr'         => array_pad(array_map(fn($v) => round($v, 4), $mr), $k, null),
            'todos_valores'     => $vals,
            'fase'              => 'I',
            'k_recomendado'     => 20,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CONSTANTES X̄-S interpoladas para n no listado
    // Ref: Montgomery (2013), Cap.5, Tabla VI
    // ═══════════════════════════════════════════════════════════
    private function getConstantesS(int $n): array
    {
        if (isset(self::CONSTANTES_S[$n])) return self::CONSTANTES_S[$n];

        $keys  = array_keys(self::CONSTANTES_S);
        $lower = null; $upper = null;
        foreach ($keys as $key) {
            if ($key <= $n) $lower = $key;
            if ($key >= $n && $upper === null) $upper = $key;
        }
        if ($lower === null) return self::CONSTANTES_S[min($keys)];
        if ($upper === null) return self::CONSTANTES_S[max($keys)];
        if ($lower === $upper) return self::CONSTANTES_S[$lower];

        $t  = ($n - $lower) / ($upper - $lower);
        $cL = self::CONSTANTES_S[$lower];
        $cU = self::CONSTANTES_S[$upper];
        return [
            'c4' => round($cL['c4'] + $t * ($cU['c4'] - $cL['c4']), 4),
            'A3' => round($cL['A3'] + $t * ($cU['A3'] - $cL['A3']), 4),
            'B3' => round($cL['B3'] + $t * ($cU['B3'] - $cL['B3']), 4),
            'B4' => round($cL['B4'] + $t * ($cU['B4'] - $cL['B4']), 4),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // INTERVALOS DE CONFIANZA Cp y Cpk (95%)
    // IC Cp: chi-cuadrado — Wilson & Hilferty (1931)
    // IC Cpk: aproximación de Bissell (1990)
    // Ref: Montgomery (2013), Cap.6, p.357-358
    // ═══════════════════════════════════════════════════════════
    private function intervaloConfianzaCp(mixed $cp, int $n): ?array
    {
        if (!is_numeric($cp) || $cp === '—' || $n < 2) return null;
        $cp = (float)$cp;
        $gl = $n - 1;
        $z  = 1.96;
        $chi2_lower = max(0.001, $gl * pow(1 - 2 / (9 * $gl) - $z * sqrt(2 / (9 * $gl)), 3));
        $chi2_upper = max(0.001, $gl * pow(1 - 2 / (9 * $gl) + $z * sqrt(2 / (9 * $gl)), 3));
        return [
            'lci'       => round($cp * sqrt($chi2_lower / $gl), 4),
            'lcs'       => round($cp * sqrt($chi2_upper / $gl), 4),
            'confianza' => 95,
            'n'         => $n,
        ];
    }

    private function intervaloConfianzaCpk(mixed $cpk, int $n): ?array
    {
        if (!is_numeric($cpk) || $cpk === '—' || $n < 2) return null;
        $cpk = (float)$cpk;
        $se  = sqrt(1 / (9 * $n * max($cpk, 0.001) ** 2) + 1 / (2 * ($n - 1)));
        $z   = 1.96;
        return [
            'lci'       => round($cpk - $z * $cpk * $se, 4),
            'lcs'       => round($cpk + $z * $cpk * $se, 4),
            'confianza' => 95,
            'n'         => $n,
            'ref'       => 'Bissell (1990), citado en Montgomery (2013), Cap.6, p.358',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // REGLAS DE NELSON
    // Ref: Nelson (1984); Montgomery (2013), Cap.5, p.196-200
    // ═══════════════════════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    // REGLAS DE NELSON — delega en Services/ReglasNelsonService.php,
    // el mismo que usa M2 (RegistroSubgrupoSpc). Antes esta función tenía
    // su propia lógica con solo 4 reglas, y lo que aquí se llamaba
    // "Regla 4" (2 de 3 puntos fuera de ±2σ) era en realidad la Regla 5
    // en la numeración estándar de Nelson — la misma que ya usa M2. Esa
    // inconsistencia quedaba resuelta al compartir una sola fuente de
    // verdad para las 8 reglas.
    // ═══════════════════════════════════════════════════════════
    private function reglasNelson(array $vals, float $media, float $sigma,
                                    float $lcs, float $lci): array
    {
        $resultadosPorPunto = ReglasNelsonService::evaluarSerieCompleta(
            $vals, $media, $sigma, $lcs, $lci
        );

        $violaciones = [];
        foreach ($resultadosPorPunto as $i => $r) {
            foreach ($r['reglas'] as $numRegla) {
                $violaciones[] = [
                    'subgrupo' => $i + 1,
                    'regla'    => $numRegla,
                    'desc'     => ReglasNelsonService::DESCRIPCIONES[$numRegla] ?? "Regla {$numRegla}",
                ];
            }
        }
        return $violaciones;
    }

    // ═══════════════════════════════════════════════════════════
    // HISTOGRAMA — Regla de Sturges
    // Ref: Sturges (1926); Montgomery (2013), Cap.3
    // ═══════════════════════════════════════════════════════════
    private function calcularHistograma(array $vals, float $lie, float $lse): array
    {
        if (empty($vals)) return [];
        $min = min($vals); $max = max($vals); $n = count($vals);
        if ($min == $max) return [];
        $bins  = max(5, (int)ceil(1 + 3.322 * log10($n)));
        $ancho = ($max - $min) / $bins ?: 0.0001;
        $freq  = array_fill(0, $bins, 0);
        $etiq  = [];
        for ($i = 0; $i < $bins; $i++) $etiq[] = round($min + ($i + 0.5) * $ancho, 4);
        foreach ($vals as $v) {
            $bin = min((int)(($v - $min) / $ancho), $bins - 1);
            $freq[$bin]++;
        }
        return ['etiquetas' => $etiq, 'frecuencias' => $freq, 'ancho_bin' => round($ancho, 4),
                'lie' => $lie, 'lse' => $lse];
    }

    // ═══════════════════════════════════════════════════════════
    // ÍNDICES DE CAPACIDAD — Cp, Cpk, Pp, Ppk, CPU, CPL
    // Ref: Montgomery (2013), Cap.6, Ec.6.4-6.8
    // ═══════════════════════════════════════════════════════════
    private function calcularCapacidad(float $media, float $sigmaCP,
                                        float $sigmaLP, array $specs): array
    {
        $lse = (float)($specs['lse'] ?? 0);
        $lie = (float)($specs['lie'] ?? 0);
        $tol = $lse - $lie;

        $cp  = ($sigmaCP > 0 && $tol > 0) ? $tol / (6 * $sigmaCP) : null;
        $cpu = ($sigmaCP > 0 && $lse > 0)  ? ($lse - $media) / (3 * $sigmaCP) : null;
        $cpl = ($sigmaCP > 0 && $lie > 0)  ? ($media - $lie) / (3 * $sigmaCP) : null;
        $cpk = ($cpu !== null && $cpl !== null) ? min($cpu, $cpl) : null;

        $pp  = ($sigmaLP > 0 && $tol > 0) ? $tol / (6 * $sigmaLP) : null;
        $ppu = ($sigmaLP > 0 && $lse > 0)  ? ($lse - $media) / (3 * $sigmaLP) : null;
        $ppl = ($sigmaLP > 0 && $lie > 0)  ? ($media - $lie) / (3 * $sigmaLP) : null;
        $ppk = ($ppu !== null && $ppl !== null) ? min($ppu, $ppl) : null;

        $ppmCP = ($sigmaCP > 0 && $lse > 0 && $lie > 0) ? $this->calcularPPM($media, $sigmaCP, $lie, $lse) : null;
        $ppmLP = ($sigmaLP > 0 && $lse > 0 && $lie > 0) ? $this->calcularPPM($media, $sigmaLP, $lie, $lse) : null;

        return [
            'cp'          => $cp  !== null ? round($cp, 4)  : '—',
            'cpk'         => $cpk !== null ? round($cpk, 4) : '—',
            'cpu'         => $cpu !== null ? round($cpu, 4) : '—',
            'cpl'         => $cpl !== null ? round($cpl, 4) : '—',
            'pp'          => $pp  !== null ? round($pp, 4)  : '—',
            'ppk'         => $ppk !== null ? round($ppk, 4) : '—',
            'ppu'         => $ppu !== null ? round($ppu, 4) : '—',
            'ppl'         => $ppl !== null ? round($ppl, 4) : '—',
            'ppm_cp'      => $ppmCP !== null ? round($ppmCP) : '—',
            'ppm_lp'      => $ppmLP !== null ? round($ppmLP) : '—',
            'sigma_nivel' => $cpk  !== null ? round($cpk * 3, 2) : '—',
            'lse'         => $lse > 0 ? $lse : '—',
            'lie'         => $lie > 0 ? $lie : '—',
            'tolerancia'  => $tol > 0 ? round($tol, 4) : '—',
            'ic_cp'       => null,
            'ic_cpk'      => null,
            'arl0'        => 370,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CURVA OC — X̄-R y X-MR
    // β(δ) = Φ(L−δ√n) − Φ(−L−δ√n), L=3
    // Ref: Montgomery (2013), Cap.5, p.220-225, Ec.5.23
    // ═══════════════════════════════════════════════════════════
    private function calcularCurvaOC(int $n, float $L = 3.0): array
    {
        $puntos = []; $sqrtN = sqrt($n); $delta = 0.0;
        while ($delta <= 6.0) {
            $d    = round($delta, 1);
            $beta = max(0, min(1, $this->phi($L - $d * $sqrtN) - $this->phi(-$L - $d * $sqrtN)));
            $arl  = $beta < 1 ? round(1 / (1 - $beta), 1) : 9999;
            $puntos[] = ['delta' => $d, 'beta' => round($beta, 4), 'beta_pct' => round($beta * 100, 2),
                          'arl' => $arl, 'poder' => round((1 - $beta) * 100, 2)];
            $delta = round($delta + 0.1, 1);
        }
        $refs = [
            ['delta' => 0.0, 'desc' => 'Sin cambio (ARL₀≈370)'],
            ['delta' => 0.5, 'desc' => '0.5σ — Cambio muy pequeño'],
            ['delta' => 1.0, 'desc' => '1σ — Cambio pequeño'],
            ['delta' => 1.5, 'desc' => '1.5σ — Cambio moderado'],
            ['delta' => 2.0, 'desc' => '2σ — Cambio medio'],
            ['delta' => 3.0, 'desc' => '3σ — Cambio grande'],
        ];
        $refsCalc = array_map(function ($r) use ($n, $L, $sqrtN) {
            $beta = max(0, min(1, $this->phi($L - $r['delta'] * $sqrtN) - $this->phi(-$L - $r['delta'] * $sqrtN)));
            $arl  = $beta < 1 ? round(1 / (1 - $beta), 1) : 9999;
            return array_merge($r, ['beta' => round($beta, 4), 'beta_pct' => round($beta * 100, 2),
                                     'arl' => $arl, 'poder' => round((1 - $beta) * 100, 2)]);
        }, $refs);
        return ['puntos' => $puntos, 'referencias' => $refsCalc, 'n' => $n, 'L' => $L,
                'ref_biblio' => 'Montgomery (2013), Cap.5, p.220-225, Ec.5.23'];
    }

    // ═══════════════════════════════════════════════════════════
    // CURVA OC — Carta p
    // β(p₁) = Φ((LCS-p₁)/σ₁) − Φ((LCI-p₁)/σ₁)
    // Ref: Montgomery (2013), Cap.7, p.300-303
    // ═══════════════════════════════════════════════════════════
    private function calcularCurvaOC_p(float $pbar, int $n, float $lcs, float $lci): array
    {
        $puntos = []; $maxP1 = min(0.99, $lcs * 2.5); $paso = $maxP1 / 60;
        for ($i = 0; $i <= 60; $i++) {
            $p1     = max(0.001, min(0.999, round($paso * $i, 4)));
            $sigma1 = sqrt($p1 * (1 - $p1) / $n);
            $beta   = 0;
            if ($sigma1 > 0) {
                $beta = max(0, min(1, $this->phi(($lcs - $p1) / $sigma1) - $this->phi(($lci - $p1) / $sigma1)));
            } elseif ($p1 <= $lcs && $p1 >= $lci) {
                $beta = 1.0;
            }
            $arl      = $beta < 1 ? round(1 / (1 - $beta), 1) : 9999;
            $puntos[] = ['p1' => $p1, 'beta' => round($beta, 4), 'beta_pct' => round($beta * 100, 2),
                          'arl' => $arl, 'poder' => round((1 - $beta) * 100, 2)];
        }
        $refs = [];
        foreach ([0, $pbar, round($pbar + 0.05, 3), round($pbar + 0.10, 3), round($pbar + 0.15, 3), round($lcs, 4)] as $p1) {
            $p1     = max(0.001, min(0.999, (float)$p1));
            $sigma1 = sqrt($p1 * (1 - $p1) / $n);
            $beta   = $sigma1 > 0
                ? max(0, min(1, $this->phi(($lcs - $p1) / $sigma1) - $this->phi(($lci - $p1) / $sigma1)))
                : ($p1 <= $lcs ? 1 : 0);
            $arl    = $beta < 1 ? round(1 / (1 - $beta), 1) : 9999;
            $refs[] = ['p1' => $p1, 'beta' => round($beta, 4), 'beta_pct' => round($beta * 100, 2),
                        'arl' => $arl, 'poder' => round((1 - $beta) * 100, 2),
                        'desc' => $p1 == $pbar ? 'p̄ (referencia)' : ($p1 >= $lcs ? '≥ LCS' : 'p₁=' . round($p1, 3))];
        }
        return ['puntos' => $puntos, 'referencias' => $refs, 'pbar' => $pbar, 'n' => $n,
                'lcs' => $lcs, 'lci' => $lci, 'tipo' => 'p',
                'ref_biblio' => 'Montgomery (2013), Cap.7, p.300-303'];
    }

    // ═══════════════════════════════════════════════════════════
    // PRUEBA ANDERSON-DARLING
    // Ref: Anderson & Darling (1954); Montgomery (2013), Cap.3
    // ═══════════════════════════════════════════════════════════
    private function andersonDarling(array $vals): array
    {
        $n = count($vals);
        if ($n < 7) return ['ad' => null, 'adc' => null, 'p_value' => null, 'normal' => null,
                            'mensaje' => 'Se requieren al menos 7 observaciones'];
        sort($vals);
        $media = array_sum($vals) / $n;
        $sigma = $this->desviacionMuestral($vals);
        if ($sigma <= 0) return ['ad' => null, 'adc' => null, 'p_value' => null, 'normal' => null,
                                    'mensaje' => 'Desviación estándar = 0'];
        $S = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $zi   = ($vals[$i] - $media) / $sigma;
            $phi  = max(1e-10, min(1 - 1e-10, $this->phi($zi)));
            $phi2 = max(1e-10, min(1 - 1e-10, 1 - $this->phi(($vals[$n - 1 - $i] - $media) / $sigma)));
            $S   += (2 * ($i + 1) - 1) * (log($phi) + log($phi2));
        }
        $AD     = -$n - $S / $n;
        $ADc    = $AD * (1 + 4 / $n - 25 / ($n * $n));
        $pValue = $this->adPValue($ADc);
        return ['ad' => round($AD, 4), 'adc' => round($ADc, 4), 'p_value' => round($pValue, 4),
                'normal'  => $pValue >= 0.05,
                'mensaje' => $pValue >= 0.05 ? 'Datos normales (p ≥ 0.05)' : 'Datos NO normales (p < 0.05)'];
    }

    // Φ(z) — Abramowitz & Stegun (1964), fórmula 26.2.17
    private function phi(float $z): float
    {
        $t   = 1 / (1 + 0.2316419 * abs($z));
        $p   = $t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937 + $t * (-1.821255978 + $t * 1.330274429))));
        $pdf = exp(-0.5 * $z * $z) / sqrt(2 * M_PI);
        $cdf = 1 - $pdf * $p;
        return $z >= 0 ? $cdf : 1 - $cdf;
    }

    // p-value AD — Marsaglia & Marsaglia (2004)
    private function adPValue(float $z): float
    {
        if ($z < 0.2)  return 1 - exp(-13.436 + 101.14 * $z - 223.73 * $z * $z);
        if ($z < 0.34) return 1 - exp(-8.318  + 42.796 * $z - 59.938 * $z * $z);
        if ($z < 0.6)  return exp(0.9177 - 4.279 * $z - 1.38 * $z * $z);
        if ($z < 153)  return exp(1.2937 - 5.709 * $z + 0.0186 * $z * $z);
        return 0.0;
    }

    // ═══════════════════════════════════════════════════════════
    // Q-Q PLOT — Posición de graficación de Blom (1958)
    // Ref: Blom, G. (1958). Statistical Estimates and Transformed
    // Beta-Variables. New York: Wiley.
    // La fórmula (i-0.375)/(n+0.25) corresponde a Blom, no a Filliben
    // (1975) — la fórmula de Filliben usa constantes distintas
    // (0.3175/0.365) y casos especiales para el primer y último punto.
    // ═══════════════════════════════════════════════════════════
    private function qqPlot(array $vals, float $media, float $sigma): array
    {
        $n = count($vals);
        if ($n < 3 || $sigma <= 0) return [];
        sort($vals);
        $puntos = [];
        for ($i = 0; $i < $n; $i++) {
            $pi       = ($i + 1 - 0.375) / ($n + 0.25);
            $puntos[] = ['x_muestral' => round($vals[$i], 4),
                          'x_teorico'  => round($media + $sigma * $this->invNormal($pi), 4),
                            'z_teorico'  => round($this->invNormal($pi), 4)];
        }
        $q1idx     = (int)floor(0.25 * $n);
        $q3idx     = (int)floor(0.75 * $n);
        $z1        = $this->invNormal(0.25);
        $z3        = $this->invNormal(0.75);
        $pendiente = ($vals[$q3idx] - $vals[$q1idx]) / ($z3 - $z1);
        $intercepto = $vals[$q1idx] - $pendiente * $z1;
        $zMin      = ($vals[0]       - $intercepto) / $pendiente;
        $zMax      = ($vals[$n - 1]  - $intercepto) / $pendiente;
        return ['puntos' => $puntos, 'linea' => [
            ['x_teorico' => round($media + $sigma * $zMin, 4), 'x_muestral' => round($vals[0], 4)],
            ['x_teorico' => round($media + $sigma * $zMax, 4), 'x_muestral' => round($vals[$n - 1], 4)],
        ]];
    }

    // Inversa normal — Algoritmo de Acklam (2003)
    // Ref: Acklam, P.J. (2003). "An algorithm for computing the inverse
    // normal cumulative distribution function". Los coeficientes de esta
    // implementación corresponden a la aproximación racional de Acklam
    // (precisión ~1.15×10⁻⁹), no a Beasley-Springer-Moro (que usa
    // coeficientes distintos) — confusión frecuente entre ambos métodos
    // en implementaciones que circulan públicamente.
    private function invNormal(float $p): float
    {
        if ($p <= 0) return -8.0;
        if ($p >= 1) return 8.0;
        $a = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02,
                1.383577518672690e+02, -3.066479806614716e+01,  2.506628277459239e+00];
        $b = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02,
                6.680131188771972e+01, -1.328068155288572e+01];
        $c = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00,
                -2.549732539343734e+00,  4.374664141464968e+00,  2.938163982698783e+00];
        $d = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00, 3.754408661907416e+00];
        $pLow = 0.02425; $pHigh = 1 - $pLow;
        if ($p < $pLow) {
            $q = sqrt(-2 * log($p));
            return ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                   ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1));
        } elseif ($p <= $pHigh) {
            $q = $p - 0.5; $r = $q * $q;
            return (((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q) /
                   ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1);
        } else {
            $q = sqrt(-2 * log(1 - $p));
            return -((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                    ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1));
        }
    }

    // Curva normal PDF — Montgomery (2013), Cap.6
    private function curvaNomal(float $media, float $sigma,
                                float $min, float $max, int $puntos = 50): array
    {
        if ($sigma <= 0) return [];
        $paso = ($max - $min) / $puntos;
        $x = []; $y = [];
        for ($i = 0; $i <= $puntos; $i++) {
            $xi  = $min + $i * $paso;
            $x[] = round($xi, 4);
            $y[] = round((1 / ($sigma * sqrt(2 * M_PI))) * exp(-0.5 * (($xi - $media) / $sigma) ** 2), 6);
        }
        return ['x' => $x, 'y' => $y];
    }

    // PPM — Montgomery (2013), Cap.6
    private function calcularPPM(float $media, float $sigma, float $lie, float $lse): float
    {
        return ($this->phiC(($lse - $media) / $sigma) + $this->phiC(($media - $lie) / $sigma)) * 1_000_000;
    }

    private function phiC(float $z): float
    {
        if ($z < 0) return 1 - $this->phiC(-$z);
        $t = 1 / (1 + 0.2316419 * $z);
        $p = $t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937 + $t * (-1.821255978 + $t * 1.330274429))));
        return $p * exp(-0.5 * $z * $z) / sqrt(2 * M_PI);
    }

    private function desviacionMuestral(array $vals): float
    {
        $n = count($vals);
        if ($n < 2) return 0;
        $m = array_sum($vals) / $n;
        return sqrt(array_sum(array_map(fn($v) => ($v - $m) ** 2, $vals)) / ($n - 1));
    }

    private function mediana(array $vals): float
    {
        $ord = $vals; sort($ord); $n = count($ord);
        if (!$n) return 0;
        $m = (int)($n / 2);
        return $n % 2 === 0 ? ($ord[$m - 1] + $ord[$m]) / 2 : $ord[$m];
    }

    // ═══════════════════════════════════════════════════════════
    // TABLA ANOVA — Montgomery (2013), Cap.4, p.165-170
    // ═══════════════════════════════════════════════════════════
    private function calcularANOVA(array $subgrupos, float $mediaGlobal): array
    {
        $k = count($subgrupos);
        if ($k < 2) return [];
        $SCB = 0; $N = 0; $glB = $k - 1;
        foreach ($subgrupos as $sg) {
            $ni   = count(array_filter($sg['valores_raw'] ?? [], fn($v) => $v !== null && (float)$v > 0)) ?: 1;
            $SCB += $ni * pow((float)$sg['xbar'] - $mediaGlobal, 2);
            $N   += $ni;
        }
        $SCW = 0; $glW = $N - $k;
        foreach ($subgrupos as $sg) {
            $xbari = (float)$sg['xbar'];
            foreach ($sg['valores_raw'] as $v) {
                if ($v !== null && (float)$v > 0) $SCW += pow((float)$v - $xbari, 2);
            }
        }
        $SCT = $SCB + $SCW; $glT = $N - 1;
        $CMB = $glB > 0 ? $SCB / $glB : 0;
        $CMW = $glW > 0 ? $SCW / $glW : 0;
        $F   = $CMW > 0 ? $CMB / $CMW : null;
        $nBar        = $k > 0 ? $N / $k : 1;
        $var_dentro  = $CMW;
        $var_entre   = max(0, ($CMB - $CMW) / $nBar);
        $var_total   = $var_dentro + $var_entre;
        $pct_dentro  = $var_total > 0 ? round($var_dentro / $var_total * 100, 2) : 0;
        $pct_entre   = $var_total > 0 ? round($var_entre  / $var_total * 100, 2) : 0;
        $St          = sqrt($var_total);
        return [
            'k' => $k, 'N' => $N, 'n_bar' => round($nBar, 2),
            'SCB' => round($SCB, 4), 'SCW' => round($SCW, 4), 'SCT' => round($SCT, 4),
            'glB' => $glB, 'glW' => $glW, 'glT' => $glT,
            'CMB' => round($CMB, 4), 'CMW' => round($CMW, 4),
            'F'   => $F !== null ? round($F, 4) : '—',
            'var_dentro'   => round($var_dentro, 4), 'var_entre' => round($var_entre, 4),
            'var_total'    => round($var_total, 4),
            'pct_dentro'   => $pct_dentro, 'pct_entre' => $pct_entre,
            'sigma_dentro' => round(sqrt($var_dentro), 4), 'sigma_entre' => round(sqrt($var_entre), 4),
            'St'  => round($St, 4), 'ref' => 'Montgomery (2013), Cap.4, p.165-170',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // ÍNDICES ADICIONALES: Cpm, k_centrado, St
    // Ref: Montgomery (2013), Cap.6, p.357-371
    // ═══════════════════════════════════════════════════════════
    private function calcularIndicesAdicionales(float $media, float $sigmaCP,
                                                float $sigmaLP, array $specs,
                                                ?float $St = null): array
    {
        $lse        = (float)($specs['lse'] ?? 0);
        $lie        = (float)($specs['lie'] ?? 0);
        $nominal    = (float)($specs['nominal'] ?? ($lse + $lie) / 2);
        $tol        = $lse - $lie;
        $m          = ($lse + $lie) / 2;
        $k_centrado = ($tol > 0) ? round(abs($media - $m) / ($tol / 2), 4) : null;
        $desvT_CP   = sqrt($sigmaCP ** 2 + ($media - $nominal) ** 2);
        $desvT_LP   = sqrt($sigmaLP ** 2 + ($media - $nominal) ** 2);
        $cpm_cp     = ($desvT_CP > 0 && $tol > 0) ? round($tol / (6 * $desvT_CP), 4) : null;
        $cpm_lp     = ($desvT_LP > 0 && $tol > 0) ? round($tol / (6 * $desvT_LP), 4) : null;
        $stVal      = $St ?? $sigmaLP;

        $interpK = null;
        if ($k_centrado !== null) {
            if ($k_centrado <= 0.1)      $interpK = 'Proceso perfectamente centrado';
            elseif ($k_centrado <= 0.3)  $interpK = 'Proceso bien centrado';
            elseif ($k_centrado <= 0.5)  $interpK = 'Descentrado leve';
            else                          $interpK = 'Proceso descentrado — acción requerida';
        }
        $interpCpm = null;
        if ($cpm_cp !== null) {
            if ($cpm_cp >= 1.33)      $interpCpm = 'Capaz y centrado';
            elseif ($cpm_cp >= 1.00)  $interpCpm = 'Marginal';
            else                       $interpCpm = 'No capaz o descentrado';
        }
        return [
            'nominal'    => $nominal,
            'k_centrado' => $k_centrado ?? '—', 'interp_k'   => $interpK   ?? '—',
            'cpm_cp'     => $cpm_cp     ?? '—', 'cpm_lp'     => $cpm_lp    ?? '—',
            'interp_cpm' => $interpCpm  ?? '—',
            'St'         => round($stVal, 4),
            'desvT_CP'   => round($desvT_CP, 4), 'desvT_LP' => round($desvT_LP, 4),
            'ref_cpm'    => 'Montgomery (2013), Cap.6, p.369-371',
            'ref_k'      => 'Montgomery (2013), Cap.6, p.357',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // RUN CHART — Montgomery (2013), Cap.3, p.97-98
    // ═══════════════════════════════════════════════════════════
    private function calcularRunChart(array $vals, array $fechas): array
    {
        $n = count($vals);
        if ($n < 4) return [];
        $mediana         = $this->mediana($vals);
        $signos          = array_map(fn($v) => (float)$v > $mediana ? 'A' : ((float)$v < $mediana ? 'B' : null), $vals);
        $signosFiltrados = array_values(array_filter($signos, fn($s) => $s !== null));
        $nF              = count($signosFiltrados);

        if ($nF === 0) {
            return ['valores' => array_map(fn($v) => round((float)$v, 4), $vals), 'fechas' => $fechas,
                    'mediana' => round($mediana, 4), 'n' => $n, 'n_arriba' => 0, 'n_abajo' => 0,
                    'rachas' => 0, 'media_rachas' => 0, 'sigma_rachas' => 0, 'z_rachas' => 0,
                    'p_value' => 1, 'aleatorio' => true,
                    'interpretacion' => 'Todos los valores coinciden con la mediana — no se puede analizar rachas.',
                    'tendencias' => [], 'hay_ciclos' => false, 'signos' => $signos,
                    'ref' => 'Montgomery (2013), Cap.3, p.97-98'];
        }

        $rachas = 1;
        for ($i = 1; $i < $nF; $i++) {
            if ($signosFiltrados[$i] !== $signosFiltrados[$i - 1]) $rachas++;
        }
        $nA           = count(array_filter($signosFiltrados, fn($s) => $s === 'A'));
        $nB           = count(array_filter($signosFiltrados, fn($s) => $s === 'B'));
        $mediaRachas  = $nF > 0 ? 1 + (2 * $nA * $nB) / $nF : 1;
        $varRachas    = ($nF > 1) ? (2 * $nA * $nB * (2 * $nA * $nB - $nF)) / ($nF ** 2 * ($nF - 1)) : 0;
        $sigmaRachas  = $varRachas > 0 ? sqrt($varRachas) : 0;
        $zRachas      = $sigmaRachas > 0 ? ($rachas - $mediaRachas) / $sigmaRachas : 0;
        $pRachas      = 2 * (1 - $this->phi(abs($zRachas)));

        $tendencias = [];
        for ($i = 5; $i < $n; $i++) {
            $seg = array_slice($vals, $i - 5, 6);
            $asc = true; $des = true;
            for ($j = 1; $j < 6; $j++) {
                if ($seg[$j] <= $seg[$j - 1]) $asc = false;
                if ($seg[$j] >= $seg[$j - 1]) $des = false;
            }
            if ($asc) $tendencias[] = ['pos' => $i + 1, 'tipo' => 'ascendente'];
            if ($des) $tendencias[] = ['pos' => $i + 1, 'tipo' => 'descendente'];
        }

        $alternancia = 0;
        for ($i = 1; $i < $nF; $i++) {
            if ($signosFiltrados[$i] !== $signosFiltrados[$i - 1]) $alternancia++;
        }
        $hayCiclos      = ($alternancia / max(1, $nF - 1)) > 0.8;
        $aleatoriedad   = $pRachas >= 0.05;
        $interpretacion = $aleatoriedad
            ? 'Proceso aleatorio — sin patrones detectados'
            : ($zRachas > 0
                ? 'Pocas rachas — posible agrupamiento o mezcla'
                : 'Muchas rachas — posible sobrecontrol o ciclos');

        return [
            'valores'        => array_map(fn($v) => round((float)$v, 4), $vals),
            'fechas'         => $fechas,
            'mediana'        => round($mediana, 4),
            'n'              => $n, 'n_arriba' => $nA, 'n_abajo' => $nB,
            'rachas'         => $rachas, 'media_rachas' => round($mediaRachas, 2),
            'sigma_rachas'   => round($sigmaRachas, 4), 'z_rachas' => round($zRachas, 4),
            'p_value'        => round($pRachas, 4), 'aleatorio' => $aleatoriedad,
            'interpretacion' => $interpretacion, 'tendencias' => $tendencias,
            'hay_ciclos'     => $hayCiclos, 'signos' => $signos,
            'ref'            => 'Montgomery (2013), Cap.3, p.97-98; Swed & Eisenhart (1943)',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS DE CONSTRUCCIÓN
    // ═══════════════════════════════════════════════════════════
    private function buildSpecs(array $specs): array
    {
        return ['lse'     => (float)($specs['lse']    ?? 0),
                'lie'     => (float)($specs['lie']    ?? 0),
                'nominal' => (float)($specs['nominal'] ?? 0),
                'unidad'  => $specs['unidad'] ?? ''];
    }

    private function buildEstadisticos(float $media, float $mediana, float $sigmaCP,
                                        float $sigmaLP, float $cv, array $allVals, int $N): array
    {
        return [
            'media'   => round($media,   4),
            'mediana' => round($mediana, 4),
            'desv_cp' => round($sigmaCP, 4),
            'desv_lp' => round($sigmaLP, 4),
            'cv_pct'  => round($cv, 2),
            'minimo'  => !empty($allVals) ? round(min($allVals), 4) : '—',
            'maximo'  => !empty($allVals) ? round(max($allVals), 4) : '—',
            'n_total' => $N,
            'arl0'    => 370,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — Carta p
    // ═══════════════════════════════════════════════════════════
    private function getDatosCartaP(int $prodId, int $paramId,
                                    ?string $desde, ?string $hasta): array
    {
        $sql = "SELECT ia.fecha, CONCAT(ia.fecha,' (',ia.turno,')') AS fecha_label,
                        ia.turno, lp.codigo_lote AS lote_ref, 'inspeccion' AS origen,
                        ia.n_inspeccionado, ia.n_no_conformes AS no_conformes
                FROM reg_inspeccion_atributos ia
                JOIN lotes_produccion lp ON lp.id = ia.lote_id
                WHERE ia.producto_id = ? AND ia.parametro_id = ?";
        $params = [$prodId, $paramId];
        if ($desde) { $sql .= " AND ia.fecha >= ?"; $params[] = $desde; }
        if ($hasta) { $sql .= " AND ia.fecha <= ?"; $params[] = $hasta; }
        $sql .= " ORDER BY ia.fecha ASC, ia.turno ASC";
        return $this->db->fetchAll($sql, $params);
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — Subgrupos SPC genérico
    //
    // Fuente: reg_subgrupos_spc — tabla genérica con n lecturas en
    // campo `valores` (JSON) y estadísticos precalculados.
    // Aquí se agregan: valores_raw, n, xbar, valor (X-MR),
    // minimo, maximo y desv_est (desviación muestral del subgrupo).
    // ═══════════════════════════════════════════════════════════
    private function getSubgruposGenerico(int $prodId, int $paramId,
                                            ?string $desde, ?string $hasta): array
    {
        $sql = "SELECT rs.*, sr.fecha, sr.turno, lp.codigo_lote AS lote_ref,
                        CONCAT(sr.fecha,' (',sr.turno,')') AS fecha_label
                FROM reg_subgrupos_spc rs
                JOIN sesiones_registro sr ON sr.id = rs.sesion_id
                JOIN lotes_produccion lp  ON lp.id = sr.lote_id
                WHERE lp.producto_id = ? AND rs.parametro_id = ?";
        $params = [$prodId, $paramId];
        if ($desde) { $sql .= " AND sr.fecha >= ?"; $params[] = $desde; }
        if ($hasta) { $sql .= " AND sr.fecha <= ?"; $params[] = $hasta; }
        $sql .= " ORDER BY sr.fecha ASC, rs.hora ASC";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $valores            = json_decode($row['valores'], true) ?: [];
            $row['valores_raw'] = array_map('floatval', $valores);
            $row['n']           = count($row['valores_raw']);
            $row['xbar']        = (float)$row['promedio_xbar'];
            $row['valor']       = $row['valores_raw'][0] ?? null; // para X-MR (n=1)
            $row['minimo']      = !empty($row['valores_raw']) ? min($row['valores_raw']) : 0;
            $row['maximo']      = !empty($row['valores_raw']) ? max($row['valores_raw']) : 0;
            // Desviación estándar muestral del subgrupo — usada para CV% en sgTabla
            $row['desv_est']    = count($row['valores_raw']) >= 2
                                    ? $this->desviacionMuestral($row['valores_raw'])
                                    : 0;
        }
        unset($row);
        return $rows;
    }

    private function getSubgruposXR(int $prodId, int $paramId,
                                    ?string $desde, ?string $hasta): array
    {
        return $this->getSubgruposGenerico($prodId, $paramId, $desde, $hasta);
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — X-MR
    // ═══════════════════════════════════════════════════════════
    private function getValoresXMR(int $prodId, int $paramId,
                                    ?string $desde, ?string $hasta): array
    {
        $rows = $this->getSubgruposGenerico($prodId, $paramId, $desde, $hasta);
        return array_values(array_filter($rows, fn($r) => $r['valor'] !== null));
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — helpers generales
    // ═══════════════════════════════════════════════════════════
    private function getEspecificaciones(int $paramId): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, nombre, etapa, unidad, tipo_dato,
                    valor_min AS lie, valor_max AS lse,
                    valor_nominal AS nominal, tamanio_subgrupo
            FROM parametros_proceso WHERE id = ?",
            [$paramId]
        );
    }

    private function getProductos(): array
    {
        return $this->db->fetchAll(
            "SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre"
        );
    }

    private function getParametrosSPC(): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.producto_id, pr.nombre AS producto_nombre,
                    p.nombre AS parametro_nombre, p.etapa, p.unidad,
                    p.valor_min AS lie, p.valor_max AS lse,
                    p.valor_nominal AS nominal, p.tamanio_subgrupo, p.tipo_dato
            FROM parametros_proceso p
            JOIN productos pr ON pr.id = p.producto_id
            WHERE p.es_variable_spc = 1 AND p.activo = 1
                AND p.tipo_dato IN ('numerico','seleccion','si_no')
            ORDER BY pr.nombre, p.etapa, p.nombre"
        );
    }
}