<?php
// app/Controllers/M6_SPC/SpcController.php
namespace App\Controllers\M6_SPC;
use App\Core\Controller;
use App\Core\Auth;

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

    // ── Constantes carta X-MR (n=2)
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
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX: Análisis SPC — detecta tipo de carta automáticamente
    // ═══════════════════════════════════════════════════════════
    public function analizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $productoId  = (int)($_GET['producto_id']  ?? 0);
        $parametroId = (int)($_GET['parametro_id'] ?? 0);
        $fechaDesde  = $_GET['fecha_desde'] ?? null;
        $fechaHasta  = $_GET['fecha_hasta'] ?? null;

        if (!$productoId || !$parametroId) {
            echo json_encode(['error'=>'Seleccione producto y parámetro.']); exit;
        }
        $specs = $this->getEspecificaciones($parametroId);
        if (!$specs) {
            echo json_encode(['error'=>'Parámetro no encontrado.']); exit;
        }

        $tipoDato = $specs['tipo_dato'];
        $n        = (int)($specs['tamanio_subgrupo'] ?? 1);

        if ($tipoDato === 'seleccion' || $tipoDato === 'si_no') {
            $datos = $this->getDatosCartaP($productoId, $parametroId, $fechaDesde, $fechaHasta);
            if (count($datos) < 3) {
                echo json_encode(['error'=>'Se necesitan al menos 3 lotes para la carta p. Actualmente hay '.count($datos).'.']); exit;
            }
            echo json_encode($this->calcularCartaP($datos, $specs));
        } elseif ($n > 1) {
            $subgrupos = $this->getSubgruposXR($productoId, $parametroId, $fechaDesde, $fechaHasta);
            if (count($subgrupos) < 3) {
                echo json_encode(['error'=>'Se necesitan al menos 3 subgrupos. Actualmente hay '.count($subgrupos).'.']); exit;
            }
            // Detección dinámica: X̄-R si n<6, X̄-S si n>=6
            echo json_encode($this->calcularXR($subgrupos, $specs));
        } else {
            $valores = $this->getValoresXMR($productoId, $parametroId, $fechaDesde, $fechaHasta);
            if (count($valores) < 3) {
                echo json_encode(['error'=>'Se necesitan al menos 3 observaciones. Actualmente hay '.count($valores).'.']); exit;
            }
            echo json_encode($this->calcularXMR($valores, $specs));
        }
        exit;
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

        // Curva OC carta p
        $curvaOC_p = $this->calcularCurvaOC_p($pbar, $ni, $lcs_p, $lci_p);

        // ── Violaciones Nelson adaptadas a carta p (reglas 1, 2, 3)
        // Ref: Montgomery (2013), Cap.7, p.306
        $violaciones = [];
        foreach ($proporciones as $i => $p) {
            // Regla 1: fuera de límites ±3σ_p
            if ($p > $lcs_p || ($lci_p > 0 && $p < $lci_p)) {
                $violaciones[] = ['subgrupo'=>$i+1,'regla'=>1,
                    'desc'=>'Proporción fuera de límites (±3σ_p)'];
            }
            // Regla 2: 9 lotes del mismo lado de p̄
            if ($i >= 8) {
                $seg = array_slice($proporciones, $i-8, 9);
                $arr = count(array_filter($seg, fn($x) => $x > $pbar));
                $aba = count(array_filter($seg, fn($x) => $x < $pbar));
                if ($arr === 9 || $aba === 9) {
                    $violaciones[] = ['subgrupo'=>$i+1,'regla'=>2,
                        'desc'=>'9 lotes consecutivos del mismo lado de p̄'];
                }
            }
            // Regla 3: 6 lotes en tendencia monótona
            if ($i >= 5) {
                $seg = array_slice($proporciones, $i-5, 6);
                $asc = true; $des = true;
                for ($j=1;$j<6;$j++) {
                    if ($seg[$j] <= $seg[$j-1]) $asc = false;
                    if ($seg[$j] >= $seg[$j-1]) $des = false;
                }
                if ($asc || $des) {
                    $violaciones[] = ['subgrupo'=>$i+1,'regla'=>3,
                        'desc'=>'6 lotes en tendencia monótona — deriva en proporciones'];
                }
            }
        }

        $histograma = $this->calcularHistograma($proporciones, 0, $lcs_p * 1.5);

        // Run Chart para carta p
        $fechas_p = array_map(fn($d) => $d['fecha_label'], $datos);
        $runChart = $this->calcularRunChart($proporciones, $fechas_p);

        // Pareto de no conformes
        $paretoDatos = array_map(fn($d, $i) => [
            'lote'         => $d['lote_ref'] ?? "Lote ".($i+1),
            'fecha'        => $d['fecha_label'],
            'no_conformes' => (int)$d['no_conformes'],
            'n_insp'       => $ns[$i],
            'proporcion'   => round($proporciones[$i], 4),
            'porcentaje'   => round($proporciones[$i] * 100, 2),
        ], $datos, array_keys($datos));
        usort($paretoDatos, fn($a,$b) => $b['no_conformes'] <=> $a['no_conformes']);
        $totalNC = array_sum(array_column($paretoDatos, 'no_conformes'));
        $acum = 0;
        foreach ($paretoDatos as &$pd) {
            $acum += $pd['no_conformes'];
            $pd['pct_relativo']  = $totalNC > 0 ? round($pd['no_conformes']/$totalNC*100,2) : 0;
            $pd['pct_acumulado'] = $totalNC > 0 ? round($acum/$totalNC*100,2) : 0;
        }
        unset($pd);

        // Tabla de subgrupos
        $sgTabla = array_map(fn($d, $i) => [
            'numero'       => $i+1,
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

        // Advertencias metodológicas
        $advertencias = [];
        if ($k < 25) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'FASE1_K_INSUFICIENTE',
                'titulo'=>'Lotes insuficientes para Fase I',
                'msg'=>"k={$k} lotes. Montgomery (2013, Cap.7, p.304) recomienda k≥25.",
                'ref'=>'Montgomery (2013), Cap.7, p.304'];
        }
        $criterio1 = round($pbar * $ni, 2);
        if ($criterio1 < 5) {
            $advertencias[] = ['tipo'=>'danger','codigo'=>'N_INSUFICIENTE_CARTA_P',
                'titulo'=>'Tamaño de muestra insuficiente',
                'msg'=>"p̄·n={$criterio1}<5. Se necesita n≥".ceil(5/max($pbar,0.0001))." unidades.",
                'ref'=>'Montgomery (2013), Cap.7, p.296'];
        }
        if (count($violaciones) > 0) {
            $nv = count($violaciones);
            $advertencias[] = ['tipo'=>'danger','codigo'=>'PROCESO_INESTABLE',
                'titulo'=>'Proceso fuera de control — carta p',
                'msg'=>"{$nv} señal(es). Identifique causas asignables antes de Fase II.",
                'ref'=>'Montgomery (2013), Cap.7, p.306'];
        }
        if (($pbar - 3 * $factor) < 0) {
            $advertencias[] = ['tipo'=>'info','codigo'=>'LCI_TRUNCADO',
                'titulo'=>'LCI truncado a 0',
                'msg'=>"LCI negativo truncado a 0. LCI=máx(0, p̄−3√(p̄(1-p̄)/n)).",
                'ref'=>'Montgomery (2013), Cap.7, p.296'];
        }

        return [
            'tipo_carta'      => 'p',
            'subtipo_carta'   => 'p',
            'n'               => $ni,
            'k'               => $k,
            'subgrupos'       => $sgTabla,
            'limites_p'       => ['lcs'=>round($lcs_p,4),'lci'=>round($lci_p,4),'media'=>round($pbar,4)],
            'limites_xbar'    => ['lcs'=>round($lcs_p,4),'lci'=>round($lci_p,4),'media'=>round($pbar,4)],
            'limites_r'       => ['lcs'=>0,'lci'=>0,'media'=>0],
            'specs'           => ['lse'=>round($lcs_p,4),'lie'=>round($lci_p,4),'nominal'=>round($pbar,4),'unidad'=>'proporción'],
            'estadisticos'    => [
                'media'   => round($pbar,4),
                'mediana' => round($this->mediana($proporciones),4),
                'desv_cp' => round($factor,4),
                'desv_lp' => round($this->desviacionMuestral($proporciones),4),
                'cv_pct'  => $pbar > 0 ? round($factor/$pbar*100,2) : 0,
                'minimo'  => round(min($proporciones),4),
                'maximo'  => round(max($proporciones),4),
                'n_total' => $k * $ni,
                'arl0'    => 370,
            ],
            'capacidad'       => ['cp'=>'—','cpk'=>'—','cpu'=>'—','cpl'=>'—','pp'=>'—','ppk'=>'—',
                                  'ppu'=>'—','ppl'=>'—','ppm_cp'=>round($pbar*1_000_000),
                                  'ppm_lp'=>round($pbar*1_000_000),'sigma_nivel'=>'—',
                                  'lse'=>'—','lie'=>'—','tolerancia'=>'—',
                                  'ic_cp'=>null,'ic_cpk'=>null],
            'violaciones'     => $violaciones,
            'advertencias'    => $advertencias,
            'histograma'      => $histograma,
            'run_chart'       => $runChart,
            'pareto'          => $paretoDatos,
            'total_nc_pareto' => $totalNC,
            'xbars'           => array_map(fn($v)=>round($v,4),$proporciones),
            'rangos'          => [],
            'todos_valores'   => $proporciones,
            'pbar'            => round($pbar,4),
            'pbar_pct'        => round($pbar*100,2),
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

        // Determinar si usar carta S o carta R
        $usarCartaS = $n >= self::N_UMBRAL_S;

        // Valores raw de todos los subgrupos
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
            // ── CARTA X̄-S ─────────────────────────────────────
            // Constantes c4, A3, B3, B4 — Montgomery Cap.5, Tabla VI
            $cs = $this->getConstantesS($n);

            // s̄ = promedio de desviaciones estándar de subgrupos
            // Se calcula desde valores raw de cada subgrupo
            $sValues = [];
            foreach ($subgrupos as $sg) {
                $vals_sg = array_filter($sg['valores_raw'], fn($v) => $v !== null && (float)$v > 0);
                if (count($vals_sg) >= 2) {
                    $sValues[] = $this->desviacionMuestral(array_values($vals_sg));
                }
            }
            $sbar    = count($sValues) > 0 ? array_sum($sValues) / count($sValues) : 0;

            // σ̂ = s̄/c₄ — Montgomery Cap.5, Ec.5.43
            $sigmaCP = $cs['c4'] > 0 ? $sbar / $cs['c4'] : 0;

            // Límites carta X̄ — Montgomery Cap.5, Ec.5.41
            // LCS = X̄̄ + A₃·s̄  ·  LCI = X̄̄ - A₃·s̄
            $lcs_xbar = $xdbar + $cs['A3'] * $sbar;
            $lci_xbar = $xdbar - $cs['A3'] * $sbar;

            // Límites carta S — Montgomery Cap.5, Ec.5.42
            // LCS = B₄·s̄  ·  LCI = B₃·s̄
            $lcs_s = $cs['B4'] * $sbar;
            $lci_s = $cs['B3'] * $sbar;

            $subtipo  = 'xs';
            $valorSec = $sValues; // desviaciones de cada subgrupo
            $mediaSecundaria = $sbar;
            $limitesSecundarios = [
                'lcs'   => round($lcs_s,4),
                'lci'   => round($lci_s,4),
                'media' => round($sbar,4),
            ];
            $labelSec = 'S (Desv.Est.)';
            $constInfo = "A₃={$cs['A3']}, B₃={$cs['B3']}, B₄={$cs['B4']}, c₄={$cs['c4']}";

        } else {
            // ── CARTA X̄-R ─────────────────────────────────────
            $cr = self::CONSTANTES_R[$n] ?? self::CONSTANTES_R[5];

            $rangos  = array_map(fn($sg) => (float)$sg['rango_r'], $subgrupos);
            $rdbar   = array_sum($rangos) / $k;
            $sigmaCP = $rdbar / $cr['d2'];

            // Límites — Montgomery Cap.5, Ec.5.5 y 5.6
            $lcs_xbar = $xdbar + $cr['A2'] * $rdbar;
            $lci_xbar = $xdbar - $cr['A2'] * $rdbar;
            $lcs_r    = $cr['D4'] * $rdbar;
            $lci_r    = $cr['D3'] * $rdbar;

            $subtipo  = 'xr';
            $valorSec = $rangos;
            $mediaSecundaria = $rdbar;
            $limitesSecundarios = [
                'lcs'   => round($lcs_r,4),
                'lci'   => round($lci_r,4),
                'media' => round($rdbar,4),
            ];
            $labelSec = 'R (Rango)';
            $constInfo = "A₂={$cr['A2']}, D₃={$cr['D3']}, D₄={$cr['D4']}, d₂={$cr['d2']}";
        }

        $capacidad   = $this->calcularCapacidad($xdbar, $sigmaCP, $sigmaLP, $specs);
        $violaciones = $this->reglasNelson($xbars, $xdbar, $sigmaCP/sqrt($n), $lcs_xbar, $lci_xbar);

        // Marcar violaciones por regla para colorear en gráfico
        $violPorRegla = [];
        foreach ($violaciones as $viol) {
            $violPorRegla[$viol['subgrupo']-1][] = $viol['regla'];
        }

        sort($allVals);
        $mediana    = $this->mediana($allVals);
        $cv         = $mediaGlob != 0 ? ($sigmaLP / abs($mediaGlob)) * 100 : 0;
        $histograma = !empty($allVals) ? $this->calcularHistograma($allVals,(float)$specs['lie'],(float)$specs['lse']) : [];

        $normalidad = $this->andersonDarling($allVals);
        $qqPlot     = $this->qqPlot($allVals, $mediaGlob, $sigmaLP);
        $curvaCP    = !empty($allVals) ? $this->curvaNomal($mediaGlob,$sigmaCP,min($allVals),max($allVals)) : [];
        $curvaLP    = !empty($allVals) ? $this->curvaNomal($mediaGlob,$sigmaLP,min($allVals),max($allVals)) : [];
        $curvaOC    = $this->calcularCurvaOC($n);
        $anova      = $this->calcularANOVA($subgrupos, $mediaGlob);
        $St         = $anova['St'] ?? $sigmaLP;
        $indicesAdc = $this->calcularIndicesAdicionales($mediaGlob,$sigmaCP,$sigmaLP,$specs,$St);
        $fechas     = array_map(fn($sg) => $sg['fecha_label'], $subgrupos);
        $runChart   = $this->calcularRunChart($xbars, $fechas);

        // IC para Cp y Cpk — Montgomery (2013), Cap.6, p.357-358
        $icCp  = $this->intervaloConfianzaCp($capacidad['cp'],  $N);
        $icCpk = $this->intervaloConfianzaCpk($capacidad['cpk'], $N);
        $capacidad['ic_cp']  = $icCp;
        $capacidad['ic_cpk'] = $icCpk;
        $capacidad['arl0']   = 370;

        // Advertencias
        $advertencias = [];
        if ($k < 25) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'FASE1_K_INSUFICIENTE',
                'titulo'=>'Subgrupos insuficientes para Fase I',
                'msg'=>"k={$k}. Montgomery (2013, Cap.5, p.241) recomienda k≥25.",
                'ref'=>'Montgomery (2013), Cap.5, p.241'];
        }
        $nViol = count($violaciones);
        if ($nViol > 0) {
            $advertencias[] = ['tipo'=>'danger','codigo'=>'PROCESO_INESTABLE',
                'titulo'=>'Proceso fuera de control estadístico',
                'msg'=>"{$nViol} señal(es). Cp/Cpk NO son válidos con proceso inestable.",
                'ref'=>'Montgomery (2013), Cap.6, p.354'];
        }
        if ($normalidad['normal'] === false) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'NO_NORMALIDAD',
                'titulo'=>'Datos no normales — índices con cautela',
                'msg'=>"AD={$normalidad['ad']}, p={$normalidad['p_value']}<0.05. Cp, Cpk y PPM asumen normalidad.",
                'ref'=>'Montgomery (2013), Cap.6, p.354'];
        }
        if (!$usarCartaS && $n < 7) {
            $advertencias[] = ['tipo'=>'info','codigo'=>'R_LCI_CERO',
                'titulo'=>"LCI carta R = 0 (n={$n} < 7)",
                'msg'=>"D₃=0 para n={$n}. La carta R no detecta reducciones de variabilidad con n pequeño.",
                'ref'=>'Montgomery (2013), Cap.5, Tabla VI'];
        }
        if ($usarCartaS) {
            $advertencias[] = ['tipo'=>'info','codigo'=>'CARTA_S_ACTIVA',
                'titulo'=>"Carta X̄-S activa (n={$n} ≥ 6)",
                'msg'=>"Con n≥6 la carta S es más eficiente que la carta R. σ̂=s̄/c₄={$constInfo}.",
                'ref'=>'Montgomery (2013), Cap.5, p.238'];
        }

        // Tabla de subgrupos
        $sgTabla = array_map(fn($sg,$i) => [
            'numero'   => $i+1,
            'fecha'    => $sg['fecha_label'],
            'lote_ref' => $sg['lote_ref'] ?? '—',
            'origen'   => $sg['origen']   ?? '—',
            'n_obs'    => (int)$sg['n'],
            'xbar'     => round((float)$sg['xbar'],4),
            'rango'    => round((float)($valorSec[$i] ?? 0),4),
            'desv_est' => isset($sg['desv_est']) ? round((float)$sg['desv_est'],4) : '—',
            'minimo'   => round((float)$sg['minimo'],4),
            'maximo'   => round((float)$sg['maximo'],4),
            'cv'       => round(abs((float)$sg['xbar'])>0
                            ? (float)($sg['desv_est']??0)/abs((float)$sg['xbar'])*100 : 0,2),
            'reglas_violadas' => $violPorRegla[$i] ?? [],
        ], $subgrupos, array_keys($subgrupos));

        return [
            'tipo_carta'        => 'xr',
            'subtipo_carta'     => $subtipo,   // 'xr' o 'xs'
            'usa_carta_s'       => $usarCartaS,
            'n'                 => $n,
            'k'                 => $k,
            'label_secundaria'  => $labelSec,
            'const_info'        => $constInfo,
            'subgrupos'         => $sgTabla,
            'limites_xbar'      => ['lcs'=>round($lcs_xbar,4),'lci'=>round($lci_xbar,4),'media'=>round($xdbar,4)],
            'limites_r'         => $limitesSecundarios,
            'limites_s'         => $usarCartaS ? $limitesSecundarios : null,
            'specs'             => $this->buildSpecs($specs),
            'estadisticos'      => $this->buildEstadisticos($mediaGlob,$mediana,$sigmaCP,$sigmaLP,$cv,$allVals,$N),
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
            'xbars'             => array_map(fn($v)=>round($v,4),$xbars),
            'rangos'            => array_map(fn($v)=>round((float)$v,4),$valorSec),
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
        for ($i = 1; $i < $k; $i++) $mr[] = abs($vals[$i] - $vals[$i-1]);
        $mrbar   = count($mr) > 0 ? array_sum($mr) / count($mr) : 0;
        $sigmaCP = $mrbar / self::D2_MR;
        $sigmaLP = $this->desviacionMuestral($vals);

        $ucl_x  = $xbar + 3 * $sigmaCP;
        $lcl_x  = $xbar - 3 * $sigmaCP;
        $ucl_mr = self::D4_MR * $mrbar;

        $capacidad   = $this->calcularCapacidad($xbar, $sigmaCP, $sigmaLP, $specs);
        $violaciones = $this->reglasNelson($vals, $xbar, $sigmaCP, $ucl_x, $lcl_x);

        // Marcar violaciones por regla
        $violPorRegla = [];
        foreach ($violaciones as $viol) {
            $violPorRegla[$viol['subgrupo']-1][] = $viol['regla'];
        }

        $valsSort   = $vals; sort($valsSort);
        $mediana    = $this->mediana($valsSort);
        $cv         = $xbar != 0 ? ($sigmaLP / abs($xbar)) * 100 : 0;
        $histograma = $this->calcularHistograma($valsSort,(float)$specs['lie'],(float)$specs['lse']);

        $normalidad = $this->andersonDarling($vals);
        $qqPlot     = $this->qqPlot($vals, $xbar, $sigmaLP);
        $curvaCP    = $this->curvaNomal($xbar, $sigmaCP, min($vals), max($vals));
        $curvaLP    = $this->curvaNomal($xbar, $sigmaLP, min($vals), max($vals));
        $curvaOC    = $this->calcularCurvaOC(1);
        $indicesAdc = $this->calcularIndicesAdicionales($xbar,$sigmaCP,$sigmaLP,$specs,$sigmaLP);
        $fechas     = array_map(fn($row) => $row['fecha_label'], $valores);
        $runChart   = $this->calcularRunChart($vals, $fechas);

        // IC para Cp y Cpk
        $icCp  = $this->intervaloConfianzaCp($capacidad['cp'],  $k);
        $icCpk = $this->intervaloConfianzaCpk($capacidad['cpk'], $k);
        $capacidad['ic_cp']  = $icCp;
        $capacidad['ic_cpk'] = $icCpk;
        $capacidad['arl0']   = 370;

        // Advertencias
        $advertencias = [];
        if ($k < 20) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'FASE1_K_INSUFICIENTE',
                'titulo'=>'Observaciones insuficientes para Fase I',
                'msg'=>"k={$k}. Montgomery (2013, Cap.5, p.253) recomienda k≥20.",
                'ref'=>'Montgomery (2013), Cap.5, p.253'];
        }
        if ($k < 10) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'XMR_SIGMA_INESTABLE',
                'titulo'=>'Estimación σ̂ inestable (k < 10)',
                'msg'=>"σ̂=MR̄/d₂ es inestable con k={$k}. Se recomiendan al menos 10 observaciones.",
                'ref'=>'Montgomery (2013), Cap.5, p.253'];
        }
        $nViol = count($violaciones);
        if ($nViol > 0) {
            $advertencias[] = ['tipo'=>'danger','codigo'=>'PROCESO_INESTABLE',
                'titulo'=>'Proceso fuera de control',
                'msg'=>"{$nViol} señal(es). Cp/Cpk NO válidos con proceso inestable.",
                'ref'=>'Montgomery (2013), Cap.6, p.354'];
        }
        if ($normalidad['normal'] === false) {
            $advertencias[] = ['tipo'=>'warning','codigo'=>'NO_NORMALIDAD',
                'titulo'=>'Datos no normales — índices con cautela',
                'msg'=>"AD={$normalidad['ad']}, p={$normalidad['p_value']}<0.05.",
                'ref'=>'Montgomery (2013), Cap.6, p.354'];
        }

        $sgTabla = array_map(fn($row,$i) => [
            'numero'         => $i+1,
            'fecha'          => $row['fecha_label'],
            'lote_ref'       => $row['lote_ref'] ?? '—',
            'origen'         => $row['origen']   ?? '—',
            'n_obs'          => 1,
            'xbar'           => round((float)$row['valor'],4),
            'rango'          => isset($mr[$i-1]) ? round($mr[$i-1],4) : '—',
            'desv_est'       => '—',
            'minimo'         => round((float)$row['valor'],4),
            'maximo'         => round((float)$row['valor'],4),
            'cv'             => '—',
            'reglas_violadas'=> $violPorRegla[$i] ?? [],
        ], $valores, array_keys($valores));

        return [
            'tipo_carta'        => 'xmr',
            'subtipo_carta'     => 'xmr',
            'usa_carta_s'       => false,
            'n'                 => 1,
            'k'                 => $k,
            'label_secundaria'  => 'MR (Rango Móvil)',
            'subgrupos'         => $sgTabla,
            'limites_x'         => ['lcs'=>round($ucl_x,4),'lci'=>round($lcl_x,4),'media'=>round($xbar,4)],
            'limites_xbar'      => ['lcs'=>round($ucl_x,4),'lci'=>round($lcl_x,4),'media'=>round($xbar,4)],
            'limites_mr'        => ['lcs'=>round($ucl_mr,4),'lci'=>0,'media'=>round($mrbar,4)],
            'limites_r'         => ['lcs'=>round($ucl_mr,4),'lci'=>0,'media'=>round($mrbar,4)],
            'specs'             => $this->buildSpecs($specs),
            'estadisticos'      => $this->buildEstadisticos($xbar,$mediana,$sigmaCP,$sigmaLP,$cv,$vals,$k),
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
            'xbars'             => array_map(fn($v)=>round($v,4),array_column($valores,'valor')),
            'rangos'            => array_map(fn($v)=>round($v,4),$mr),
            'rangos_mr'         => array_pad(array_map(fn($v)=>round($v,4),$mr),$k,null),
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
        // Buscar exacto
        if (isset(self::CONSTANTES_S[$n])) return self::CONSTANTES_S[$n];

        // Interpolar entre los valores más cercanos
        $keys  = array_keys(self::CONSTANTES_S);
        $lower = null; $upper = null;
        foreach ($keys as $key) {
            if ($key <= $n) $lower = $key;
            if ($key >= $n && $upper === null) $upper = $key;
        }
        if ($lower === null) return self::CONSTANTES_S[min($keys)];
        if ($upper === null) return self::CONSTANTES_S[max($keys)];
        if ($lower === $upper) return self::CONSTANTES_S[$lower];

        // Interpolación lineal
        $t  = ($n - $lower) / ($upper - $lower);
        $cL = self::CONSTANTES_S[$lower];
        $cU = self::CONSTANTES_S[$upper];
        return [
            'c4' => round($cL['c4'] + $t*($cU['c4']-$cL['c4']),4),
            'A3' => round($cL['A3'] + $t*($cU['A3']-$cL['A3']),4),
            'B3' => round($cL['B3'] + $t*($cU['B3']-$cL['B3']),4),
            'B4' => round($cL['B4'] + $t*($cU['B4']-$cL['B4']),4),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // INTERVALOS DE CONFIANZA Cp y Cpk (95%)
    // Ref: Montgomery (2013), Cap.6, p.357-358
    // IC Cp: basado en distribución chi-cuadrado
    // IC Cpk: aproximación de Bissell (1990)
    // ═══════════════════════════════════════════════════════════
    private function intervaloConfianzaCp(mixed $cp, int $n): ?array
    {
        if (!is_numeric($cp) || $cp === '—' || $n < 2) return null;
        $cp  = (float)$cp;
        $gl  = $n - 1; // grados de libertad

        // IC exacto para Cp basado en chi-cuadrado
        // IC = Cp * sqrt(chi²_{α/2,gl}/gl) a Cp * sqrt(chi²_{1-α/2,gl}/gl)
        // Aproximación: chi²_{0.025,gl} ≈ gl*(1-2/(9*gl)-1.96*sqrt(2/(9*gl)))³
        // Ref: Wilson & Hilferty (1931), citado en Montgomery Cap.6
        $z = 1.96; // z para 95% IC bilateral
        $chi2_lower = $gl * pow(1 - 2/(9*$gl) - $z*sqrt(2/(9*$gl)), 3);
        $chi2_upper = $gl * pow(1 - 2/(9*$gl) + $z*sqrt(2/(9*$gl)), 3);
        $chi2_lower = max(0.001, $chi2_lower);
        $chi2_upper = max(0.001, $chi2_upper);

        $lci = round($cp * sqrt($chi2_lower / $gl), 4);
        $lcs = round($cp * sqrt($chi2_upper / $gl), 4);

        return ['lci'=>$lci,'lcs'=>$lcs,'confianza'=>95,'n'=>$n];
    }

    private function intervaloConfianzaCpk(mixed $cpk, int $n): ?array
    {
        if (!is_numeric($cpk) || $cpk === '—' || $n < 2) return null;
        $cpk = (float)$cpk;

        // Aproximación de Bissell (1990) para IC de Cpk
        // Ref: Montgomery (2013), Cap.6, p.358
        // SE(Cpk) ≈ Cpk * sqrt(1/(9*n*Cpk²) + 1/(2*(n-1)))
        $se  = sqrt(1/(9*$n*max($cpk,0.001)**2) + 1/(2*($n-1)));
        $z   = 1.96;
        $lci = round($cpk - $z*$cpk*$se, 4);
        $lcs = round($cpk + $z*$cpk*$se, 4);

        return ['lci'=>$lci,'lcs'=>$lcs,'confianza'=>95,'n'=>$n,
                'ref'=>'Bissell (1990), citado en Montgomery (2013), Cap.6, p.358'];
    }

    // ═══════════════════════════════════════════════════════════
    // REGLAS DE NELSON
    // Ref: Nelson (1984); Montgomery (2013), Cap.5, p.196-200
    // ═══════════════════════════════════════════════════════════
    private function reglasNelson(array $vals, float $media, float $sigma,
                                   float $lcs, float $lci): array
    {
        $v = []; $n = count($vals);
        for ($i = 0; $i < $n; $i++) {
            if ($vals[$i] > $lcs || $vals[$i] < $lci)
                $v[] = ['subgrupo'=>$i+1,'regla'=>1,'desc'=>'Punto fuera de límites de control (±3σ)'];
            if ($i >= 8) {
                $seg = array_slice($vals,$i-8,9);
                $arr = count(array_filter($seg,fn($x)=>$x>$media));
                $aba = count(array_filter($seg,fn($x)=>$x<$media));
                if ($arr===9||$aba===9)
                    $v[] = ['subgrupo'=>$i+1,'regla'=>2,'desc'=>'9 puntos consecutivos del mismo lado de la línea central'];
            }
            if ($i >= 5) {
                $seg=$asc=$des=true; $seg=array_slice($vals,$i-5,6);
                $asc=true; $des=true;
                for ($j=1;$j<6;$j++) {
                    if ($seg[$j]<=$seg[$j-1]) $asc=false;
                    if ($seg[$j]>=$seg[$j-1]) $des=false;
                }
                if ($asc||$des)
                    $v[] = ['subgrupo'=>$i+1,'regla'=>3,'desc'=>'6 puntos en tendencia monótona (deriva del proceso)'];
            }
            if ($i >= 2) {
                $seg = array_slice($vals,$i-2,3);
                $cnt = count(array_filter($seg,fn($x)=>abs($x-$media)>2*$sigma));
                if ($cnt>=2)
                    $v[] = ['subgrupo'=>$i+1,'regla'=>4,'desc'=>'2 de 3 puntos consecutivos fuera de ±2σ'];
            }
        }
        return $v;
    }

    // ═══════════════════════════════════════════════════════════
    // HISTOGRAMA — Regla de Sturges
    // Ref: Sturges (1926); Montgomery (2013), Cap.3
    // ═══════════════════════════════════════════════════════════
    private function calcularHistograma(array $vals, float $lie, float $lse): array
    {
        if (empty($vals)) return [];
        $min=$min=$max=$n=null;
        $min=min($vals); $max=max($vals); $n=count($vals);
        if ($min==$max) return [];
        $bins  = max(5,(int)ceil(1+3.322*log10($n)));
        $ancho = ($max-$min)/$bins ?: 0.0001;
        $freq  = array_fill(0,$bins,0);
        $etiq  = [];
        for ($i=0;$i<$bins;$i++) $etiq[]=round($min+($i+0.5)*$ancho,4);
        foreach ($vals as $v) { $bin=min((int)(($v-$min)/$ancho),$bins-1); $freq[$bin]++; }
        return ['etiquetas'=>$etiq,'frecuencias'=>$freq,'ancho_bin'=>round($ancho,4),
                'lie'=>$lie,'lse'=>$lse];
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

        $cp  = ($sigmaCP>0&&$tol>0) ? $tol/(6*$sigmaCP) : null;
        $cpu = ($sigmaCP>0&&$lse>0) ? ($lse-$media)/(3*$sigmaCP) : null;
        $cpl = ($sigmaCP>0&&$lie>0) ? ($media-$lie)/(3*$sigmaCP) : null;
        $cpk = ($cpu!==null&&$cpl!==null) ? min($cpu,$cpl) : null;

        $pp  = ($sigmaLP>0&&$tol>0) ? $tol/(6*$sigmaLP) : null;
        $ppu = ($sigmaLP>0&&$lse>0) ? ($lse-$media)/(3*$sigmaLP) : null;
        $ppl = ($sigmaLP>0&&$lie>0) ? ($media-$lie)/(3*$sigmaLP) : null;
        $ppk = ($ppu!==null&&$ppl!==null) ? min($ppu,$ppl) : null;

        $ppmCP = ($sigmaCP>0&&$lse>0&&$lie>0) ? $this->calcularPPM($media,$sigmaCP,$lie,$lse) : null;
        $ppmLP = ($sigmaLP>0&&$lse>0&&$lie>0) ? $this->calcularPPM($media,$sigmaLP,$lie,$lse) : null;

        return [
            'cp'         => $cp  !== null ? round($cp,4)  : '—',
            'cpk'        => $cpk !== null ? round($cpk,4) : '—',
            'cpu'        => $cpu !== null ? round($cpu,4) : '—',
            'cpl'        => $cpl !== null ? round($cpl,4) : '—',
            'pp'         => $pp  !== null ? round($pp,4)  : '—',
            'ppk'        => $ppk !== null ? round($ppk,4) : '—',
            'ppu'        => $ppu !== null ? round($ppu,4) : '—',
            'ppl'        => $ppl !== null ? round($ppl,4) : '—',
            'ppm_cp'     => $ppmCP !== null ? round($ppmCP) : '—',
            'ppm_lp'     => $ppmLP !== null ? round($ppmLP) : '—',
            'sigma_nivel'=> $cpk  !== null ? round($cpk*3,2) : '—',
            'lse'        => $lse > 0 ? $lse : '—',
            'lie'        => $lie > 0 ? $lie : '—',
            'tolerancia' => $tol > 0 ? round($tol,4) : '—',
            'ic_cp'      => null,
            'ic_cpk'     => null,
            'arl0'       => 370,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // CURVA OC — X̄-R y X-MR
    // Ref: Montgomery (2013), Cap.5, p.220-225, Ec.5.23
    // β(δ) = Φ(L−δ√n) − Φ(−L−δ√n), L=3
    // ═══════════════════════════════════════════════════════════
    private function calcularCurvaOC(int $n, float $L = 3.0): array
    {
        $puntos=[]; $sqrtN=sqrt($n); $delta=0.0;
        while ($delta <= 6.0) {
            $d    = round($delta,1);
            $beta = max(0,min(1,$this->phi($L-$d*$sqrtN)-$this->phi(-$L-$d*$sqrtN)));
            $arl  = $beta<1 ? round(1/(1-$beta),1) : 9999;
            $puntos[]=['delta'=>$d,'beta'=>round($beta,4),'beta_pct'=>round($beta*100,2),
                       'arl'=>$arl,'poder'=>round((1-$beta)*100,2)];
            $delta=round($delta+0.1,1);
        }
        $refs=[['delta'=>0.0,'desc'=>'Sin cambio (ARL₀≈370)'],
               ['delta'=>0.5,'desc'=>'0.5σ — Cambio muy pequeño'],
               ['delta'=>1.0,'desc'=>'1σ — Cambio pequeño'],
               ['delta'=>1.5,'desc'=>'1.5σ — Cambio moderado'],
               ['delta'=>2.0,'desc'=>'2σ — Cambio medio'],
               ['delta'=>3.0,'desc'=>'3σ — Cambio grande']];
        $refsCalc=array_map(function($r) use ($n,$L,$sqrtN) {
            $beta=max(0,min(1,$this->phi($L-$r['delta']*$sqrtN)-$this->phi(-$L-$r['delta']*$sqrtN)));
            $arl=$beta<1?round(1/(1-$beta),1):9999;
            return array_merge($r,['beta'=>round($beta,4),'beta_pct'=>round($beta*100,2),
                                   'arl'=>$arl,'poder'=>round((1-$beta)*100,2)]);
        },$refs);
        return ['puntos'=>$puntos,'referencias'=>$refsCalc,'n'=>$n,'L'=>$L,
                'ref_biblio'=>'Montgomery (2013), Cap.5, p.220-225, Ec.5.23'];
    }

    // ═══════════════════════════════════════════════════════════
    // CURVA OC — Carta p
    // Ref: Montgomery (2013), Cap.7, p.300-303
    // β(p₁) = Φ((LCS-p₁)/σ₁) − Φ((LCI-p₁)/σ₁)
    // ═══════════════════════════════════════════════════════════
    private function calcularCurvaOC_p(float $pbar, int $n, float $lcs, float $lci): array
    {
        $puntos=[]; $maxP1=min(0.99,$lcs*2.5); $paso=$maxP1/60;
        for ($i=0;$i<=60;$i++) {
            $p1=max(0.001,min(0.999,round($paso*$i,4)));
            $sigma1=sqrt($p1*(1-$p1)/$n);
            $beta=0;
            if ($sigma1>0) {
                $beta=max(0,min(1,$this->phi(($lcs-$p1)/$sigma1)-$this->phi(($lci-$p1)/$sigma1)));
            } elseif ($p1<=$lcs&&$p1>=$lci) { $beta=1.0; }
            $arl=$beta<1?round(1/(1-$beta),1):9999;
            $puntos[]=['p1'=>$p1,'beta'=>round($beta,4),'beta_pct'=>round($beta*100,2),
                       'arl'=>$arl,'poder'=>round((1-$beta)*100,2)];
        }
        $refs=[];
        foreach ([0,$pbar,round($pbar+0.05,3),round($pbar+0.10,3),round($pbar+0.15,3),round($lcs,4)] as $p1) {
            $p1=max(0.001,min(0.999,(float)$p1));
            $sigma1=sqrt($p1*(1-$p1)/$n);
            $beta=$sigma1>0?max(0,min(1,$this->phi(($lcs-$p1)/$sigma1)-$this->phi(($lci-$p1)/$sigma1))):($p1<=$lcs?1:0);
            $arl=$beta<1?round(1/(1-$beta),1):9999;
            $refs[]=['p1'=>$p1,'beta'=>round($beta,4),'beta_pct'=>round($beta*100,2),
                     'arl'=>$arl,'poder'=>round((1-$beta)*100,2),
                     'desc'=>$p1==$pbar?'p̄ (referencia)':($p1>=$lcs?'≥ LCS':'p₁='.round($p1,3))];
        }
        return ['puntos'=>$puntos,'referencias'=>$refs,'pbar'=>$pbar,'n'=>$n,
                'lcs'=>$lcs,'lci'=>$lci,'tipo'=>'p',
                'ref_biblio'=>'Montgomery (2013), Cap.7, p.300-303'];
    }

    // ═══════════════════════════════════════════════════════════
    // PRUEBA ANDERSON-DARLING
    // Ref: Anderson & Darling (1954); Montgomery (2013), Cap.3
    // ═══════════════════════════════════════════════════════════
    private function andersonDarling(array $vals): array
    {
        $n=count($vals);
        if ($n<7) return ['ad'=>null,'adc'=>null,'p_value'=>null,'normal'=>null,
                          'mensaje'=>'Se requieren al menos 7 observaciones'];
        sort($vals);
        $media=array_sum($vals)/$n;
        $sigma=$this->desviacionMuestral($vals);
        if ($sigma<=0) return ['ad'=>null,'adc'=>null,'p_value'=>null,'normal'=>null,
                               'mensaje'=>'Desviación estándar = 0'];
        $S=0.0;
        for ($i=0;$i<$n;$i++) {
            $zi=($vals[$i]-$media)/$sigma;
            $phi=max(1e-10,min(1-1e-10,$this->phi($zi)));
            $phi2=max(1e-10,min(1-1e-10,1-$this->phi(($vals[$n-1-$i]-$media)/$sigma)));
            $S+=(2*($i+1)-1)*(log($phi)+log($phi2));
        }
        $AD=-$n-$S/$n;
        $ADc=$AD*(1+4/$n-25/($n*$n));
        $pValue=$this->adPValue($ADc);
        return ['ad'=>round($AD,4),'adc'=>round($ADc,4),'p_value'=>round($pValue,4),
                'normal'=>$pValue>=0.05,
                'mensaje'=>$pValue>=0.05?'Datos normales (p ≥ 0.05)':'Datos NO normales (p < 0.05)'];
    }

    // Φ(z) — Abramowitz & Stegun (1964), fórmula 26.2.17
    private function phi(float $z): float
    {
        $t=1/(1+0.2316419*abs($z));
        $p=$t*(0.319381530+$t*(-0.356563782+$t*(1.781477937+$t*(-1.821255978+$t*1.330274429))));
        $pdf=exp(-0.5*$z*$z)/sqrt(2*M_PI);
        $cdf=1-$pdf*$p;
        return $z>=0?$cdf:1-$cdf;
    }

    // p-value AD — Marsaglia & Marsaglia (2004)
    private function adPValue(float $z): float
    {
        if ($z<0.2)  return 1-exp(-13.436+101.14*$z-223.73*$z*$z);
        if ($z<0.34) return 1-exp(-8.318+42.796*$z-59.938*$z*$z);
        if ($z<0.6)  return exp(0.9177-4.279*$z-1.38*$z*$z);
        if ($z<153)  return exp(1.2937-5.709*$z+0.0186*$z*$z);
        return 0.0;
    }

    // ═══════════════════════════════════════════════════════════
    // Q-Q PLOT — Montgomery (2013), Cap.3; Filliben (1975)
    // ═══════════════════════════════════════════════════════════
    private function qqPlot(array $vals, float $media, float $sigma): array
    {
        $n=count($vals); if ($n<3||$sigma<=0) return [];
        sort($vals); $puntos=[];
        for ($i=0;$i<$n;$i++) {
            $pi=($i+1-0.375)/($n+0.25);
            $puntos[]=['x_muestral'=>round($vals[$i],4),
                       'x_teorico'=>round($media+$sigma*$this->invNormal($pi),4),
                       'z_teorico'=>round($this->invNormal($pi),4)];
        }
        $q1idx=(int)floor(0.25*$n); $q3idx=(int)floor(0.75*$n);
        $z1=$this->invNormal(0.25); $z3=$this->invNormal(0.75);
        $pendiente=($vals[$q3idx]-$vals[$q1idx])/($z3-$z1);
        $intercepto=$vals[$q1idx]-$pendiente*$z1;
        $zMin=($vals[0]-$intercepto)/$pendiente;
        $zMax=($vals[$n-1]-$intercepto)/$pendiente;
        return ['puntos'=>$puntos,'linea'=>[
            ['x_teorico'=>round($media+$sigma*$zMin,4),'x_muestral'=>round($vals[0],4)],
            ['x_teorico'=>round($media+$sigma*$zMax,4),'x_muestral'=>round($vals[$n-1],4)],
        ]];
    }

    // Inversa normal — Beasley-Springer-Moro; Glasserman (2004)
    private function invNormal(float $p): float
    {
        if ($p<=0) return -8.0; if ($p>=1) return 8.0;
        $a=[-3.969683028665376e+01,2.209460984245205e+02,-2.759285104469687e+02,
            1.383577518672690e+02,-3.066479806614716e+01,2.506628277459239e+00];
        $b=[-5.447609879822406e+01,1.615858368580409e+02,-1.556989798598866e+02,
            6.680131188771972e+01,-1.328068155288572e+01];
        $c=[-7.784894002430293e-03,-3.223964580411365e-01,-2.400758277161838e+00,
            -2.549732539343734e+00,4.374664141464968e+00,2.938163982698783e+00];
        $d=[7.784695709041462e-03,3.224671290700398e-01,2.445134137142996e+00,3.754408661907416e+00];
        $pLow=0.02425; $pHigh=1-$pLow;
        if ($p<$pLow) {
            $q=sqrt(-2*log($p));
            return ((((($c[0]*$q+$c[1])*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5])/
                   ((((($d[0]*$q+$d[1])*$q+$d[2])*$q+$d[3])*$q+1));
        } elseif ($p<=$pHigh) {
            $q=$p-0.5; $r=$q*$q;
            return (((((($a[0]*$r+$a[1])*$r+$a[2])*$r+$a[3])*$r+$a[4])*$r+$a[5])*$q)/
                   ((((($b[0]*$r+$b[1])*$r+$b[2])*$r+$b[3])*$r+$b[4])*$r+1);
        } else {
            $q=sqrt(-2*log(1-$p));
            return -((((($c[0]*$q+$c[1])*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5])/
                    ((((($d[0]*$q+$d[1])*$q+$d[2])*$q+$d[3])*$q+1));
        }
    }

    // Curva normal PDF — Montgomery (2013), Cap.6
    private function curvaNomal(float $media, float $sigma,
                                  float $min, float $max, int $puntos=50): array
    {
        if ($sigma<=0) return [];
        $paso=($max-$min)/$puntos; $x=[]; $y=[];
        for ($i=0;$i<=$puntos;$i++) {
            $xi=$min+$i*$paso; $x[]=round($xi,4);
            $y[]=round((1/($sigma*sqrt(2*M_PI)))*exp(-0.5*(($xi-$media)/$sigma)**2),6);
        }
        return ['x'=>$x,'y'=>$y];
    }

    // PPM — Montgomery (2013), Cap.6
    private function calcularPPM(float $media, float $sigma, float $lie, float $lse): float
    {
        return ($this->phiC(($lse-$media)/$sigma)+$this->phiC(($media-$lie)/$sigma))*1_000_000;
    }
    private function phiC(float $z): float
    {
        if ($z<0) return 1-$this->phiC(-$z);
        $t=1/(1+0.2316419*$z);
        $p=$t*(0.319381530+$t*(-0.356563782+$t*(1.781477937+$t*(-1.821255978+$t*1.330274429))));
        return $p*exp(-0.5*$z*$z)/sqrt(2*M_PI);
    }
    private function desviacionMuestral(array $vals): float
    {
        $n=count($vals); if ($n<2) return 0;
        $m=array_sum($vals)/$n;
        return sqrt(array_sum(array_map(fn($v)=>($v-$m)**2,$vals))/($n-1));
    }
    private function mediana(array $vals): float
    {
        $ord=$vals; sort($ord); $n=count($ord); if (!$n) return 0;
        $m=(int)($n/2); return $n%2===0?($ord[$m-1]+$ord[$m])/2:$ord[$m];
    }

    // ═══════════════════════════════════════════════════════════
    // TABLA ANOVA — Montgomery (2013), Cap.4, p.165-170
    // ═══════════════════════════════════════════════════════════
    private function calcularANOVA(array $subgrupos, float $mediaGlobal): array
    {
        $k=count($subgrupos); if ($k<2) return [];
        $SCB=0; $N=0; $glB=$k-1;
        foreach ($subgrupos as $sg) {
            $ni=count(array_filter($sg['valores_raw']??[],fn($v)=>$v!==null&&(float)$v>0)) ?: 1;
            $SCB+=$ni*pow((float)$sg['xbar']-$mediaGlobal,2); $N+=$ni;
        }
        $SCW=0; $glW=$N-$k;
        foreach ($subgrupos as $sg) {
            $xbari=(float)$sg['xbar'];
            foreach ($sg['valores_raw'] as $v) {
                if ($v!==null&&(float)$v>0) $SCW+=pow((float)$v-$xbari,2);
            }
        }
        $SCT=$SCB+$SCW; $glT=$N-1;
        $CMB=$glB>0?$SCB/$glB:0; $CMW=$glW>0?$SCW/$glW:0;
        $F=$CMW>0?$CMB/$CMW:null;
        $nBar=$k>0?$N/$k:1;
        $var_dentro=$CMW; $var_entre=max(0,($CMB-$CMW)/$nBar);
        $var_total=$var_dentro+$var_entre;
        $pct_dentro=$var_total>0?round($var_dentro/$var_total*100,2):0;
        $pct_entre=$var_total>0?round($var_entre/$var_total*100,2):0;
        $St=sqrt($var_total);
        return ['k'=>$k,'N'=>$N,'n_bar'=>round($nBar,2),
                'SCB'=>round($SCB,4),'SCW'=>round($SCW,4),'SCT'=>round($SCT,4),
                'glB'=>$glB,'glW'=>$glW,'glT'=>$glT,
                'CMB'=>round($CMB,4),'CMW'=>round($CMW,4),
                'F'=>$F!==null?round($F,4):'—',
                'var_dentro'=>round($var_dentro,4),'var_entre'=>round($var_entre,4),
                'var_total'=>round($var_total,4),
                'pct_dentro'=>$pct_dentro,'pct_entre'=>$pct_entre,
                'sigma_dentro'=>round(sqrt($var_dentro),4),'sigma_entre'=>round(sqrt($var_entre),4),
                'St'=>round($St,4),'ref'=>'Montgomery (2013), Cap.4, p.165-170'];
    }

    // ═══════════════════════════════════════════════════════════
    // ÍNDICES ADICIONALES: Cpm, k, St
    // Ref: Montgomery (2013), Cap.6, p.357-371
    // ═══════════════════════════════════════════════════════════
    private function calcularIndicesAdicionales(float $media, float $sigmaCP,
                                                  float $sigmaLP, array $specs,
                                                  ?float $St=null): array
    {
        $lse=(float)($specs['lse']??0); $lie=(float)($specs['lie']??0);
        $nominal=(float)($specs['nominal']??($lse+$lie)/2);
        $tol=$lse-$lie; $m=($lse+$lie)/2;
        $k_centrado=($tol>0)?round(abs($media-$m)/($tol/2),4):null;
        $desvT_CP=sqrt($sigmaCP**2+($media-$nominal)**2);
        $desvT_LP=sqrt($sigmaLP**2+($media-$nominal)**2);
        $cpm_cp=($desvT_CP>0&&$tol>0)?round($tol/(6*$desvT_CP),4):null;
        $cpm_lp=($desvT_LP>0&&$tol>0)?round($tol/(6*$desvT_LP),4):null;
        $stVal=$St??$sigmaLP;
        $interpK=null;
        if ($k_centrado!==null) {
            if ($k_centrado<=0.1) $interpK='Proceso perfectamente centrado';
            elseif ($k_centrado<=0.3) $interpK='Proceso bien centrado';
            elseif ($k_centrado<=0.5) $interpK='Descentrado leve';
            else $interpK='Proceso descentrado — acción requerida';
        }
        $interpCpm=null;
        if ($cpm_cp!==null) {
            if ($cpm_cp>=1.33) $interpCpm='✔ Capaz y centrado';
            elseif ($cpm_cp>=1.00) $interpCpm='⚠ Marginal';
            else $interpCpm='✗ No capaz o descentrado';
        }
        return ['nominal'=>$nominal,'k_centrado'=>$k_centrado??'—','interp_k'=>$interpK??'—',
                'cpm_cp'=>$cpm_cp??'—','cpm_lp'=>$cpm_lp??'—','interp_cpm'=>$interpCpm??'—',
                'St'=>round($stVal,4),'desvT_CP'=>round($desvT_CP,4),'desvT_LP'=>round($desvT_LP,4),
                'ref_cpm'=>'Montgomery (2013), Cap.6, p.369-371',
                'ref_k'=>'Montgomery (2013), Cap.6, p.357'];
    }

    // ═══════════════════════════════════════════════════════════
    // RUN CHART — Montgomery (2013), Cap.3, p.97-98
    // ═══════════════════════════════════════════════════════════
    private function calcularRunChart(array $vals, array $fechas): array
    {
        $n=count($vals); if ($n<4) return [];
        $mediana=$this->mediana($vals);
        $signos=array_map(fn($v)=>(float)$v>$mediana?'A':((float)$v<$mediana?'B':null),$vals);
        $signosFiltrados=array_values(array_filter($signos,fn($s)=>$s!==null));
        $nF=count($signosFiltrados);
        $rachas=1;
        for ($i=1;$i<$nF;$i++) if ($signosFiltrados[$i]!==$signosFiltrados[$i-1]) $rachas++;
        $nA=count(array_filter($signosFiltrados,fn($s)=>$s==='A'));
        $nB=count(array_filter($signosFiltrados,fn($s)=>$s==='B'));
        $mediaRachas=1+(2*$nA*$nB)/$nF;
        $varRachas=(2*$nA*$nB*(2*$nA*$nB-$nF))/($nF**2*($nF-1)+0.0001);
        $sigmaRachas=$varRachas>0?sqrt($varRachas):0;
        $zRachas=$sigmaRachas>0?($rachas-$mediaRachas)/$sigmaRachas:0;
        $pRachas=2*(1-$this->phi(abs($zRachas)));
        $tendencias=[];
        for ($i=5;$i<$n;$i++) {
            $seg=array_slice($vals,$i-5,6); $asc=true; $des=true;
            for ($j=1;$j<6;$j++) {
                if ($seg[$j]<=$seg[$j-1]) $asc=false;
                if ($seg[$j]>=$seg[$j-1]) $des=false;
            }
            if ($asc) $tendencias[]=['pos'=>$i+1,'tipo'=>'ascendente'];
            if ($des) $tendencias[]=['pos'=>$i+1,'tipo'=>'descendente'];
        }
        $alternancia=0;
        for ($i=1;$i<$nF;$i++) if ($signosFiltrados[$i]!==$signosFiltrados[$i-1]) $alternancia++;
        $hayCiclos=($alternancia/max(1,$nF-1))>0.8;
        $aleatoriedad=$pRachas>=0.05;
        $interpretacion=$aleatoriedad?'Proceso aleatorio — sin patrones detectados':
            ($zRachas>0?'Pocas rachas — posible agrupamiento o mezcla':'Muchas rachas — posible sobrecontrol o ciclos');
        return ['valores'=>array_map(fn($v)=>round((float)$v,4),$vals),'fechas'=>$fechas,
                'mediana'=>round($mediana,4),'n'=>$n,'n_arriba'=>$nA,'n_abajo'=>$nB,
                'rachas'=>$rachas,'media_rachas'=>round($mediaRachas,2),
                'sigma_rachas'=>round($sigmaRachas,4),'z_rachas'=>round($zRachas,4),
                'p_value'=>round($pRachas,4),'aleatorio'=>$aleatoriedad,
                'interpretacion'=>$interpretacion,'tendencias'=>$tendencias,
                'hay_ciclos'=>$hayCiclos,'signos'=>$signos,
                'ref'=>'Montgomery (2013), Cap.3, p.97-98; Swed & Eisenhart (1943)'];
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS DE CONSTRUCCIÓN
    // ═══════════════════════════════════════════════════════════
    private function buildSpecs(array $specs): array
    {
        return ['lse'=>(float)($specs['lse']??0),'lie'=>(float)($specs['lie']??0),
                'nominal'=>(float)($specs['nominal']??0),'unidad'=>$specs['unidad']??''];
    }
    private function buildEstadisticos(float $media, float $mediana, float $sigmaCP,
                                        float $sigmaLP, float $cv, array $allVals, int $N): array
    {
        return ['media'=>round($media,4),'mediana'=>round($mediana,4),
                'desv_cp'=>round($sigmaCP,4),'desv_lp'=>round($sigmaLP,4),
                'cv_pct'=>round($cv,2),
                'minimo'=>!empty($allVals)?round(min($allVals),4):'—',
                'maximo'=>!empty($allVals)?round(max($allVals),4):'—',
                'n_total'=>$N,'arl0'=>370];
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — Carta p
    // ═══════════════════════════════════════════════════════════
    private function getDatosCartaP(int $prodId, int $paramId,
                                     ?string $desde, ?string $hasta): array
    {
        $sql="SELECT ia.fecha, CONCAT(ia.fecha,' (',ia.turno,')') AS fecha_label,
                     ia.turno, lp.codigo_lote AS lote_ref, 'inspeccion' AS origen,
                     ia.n_inspeccionado, ia.n_no_conformes AS no_conformes
              FROM reg_inspeccion_atributos ia
              JOIN lotes_produccion lp ON lp.id=ia.lote_id
              WHERE ia.producto_id=? AND ia.parametro_id=?";
        $params=[$prodId,$paramId];
        if ($desde){$sql.=" AND ia.fecha>=?";$params[]=$desde;}
        if ($hasta){$sql.=" AND ia.fecha<=?";$params[]=$hasta;}
        $sql.=" ORDER BY ia.fecha ASC, ia.turno ASC";
        return $this->db->fetchAll($sql,$params);
    }

    // ═══════════════════════════════════════════════════════════
    // CONSULTAS BD — Subgrupos SPC (X̄-R/S y X-MR), genérico por parámetro
    //
    // Fuente única: reg_subgrupos_spc (tabla genérica creada por
    // RegistroDinamicoController en M2 — ver migración M2 dinámico).
    // Cada fila ya trae las n lecturas del subgrupo en `valores` (JSON)
    // y sus estadísticos precalculados (promedio_xbar, rango_r).
    // Para X-MR (parámetros con tamanio_subgrupo=1) se usa el primer
    // (único) valor de cada fila.
    // ═══════════════════════════════════════════════════════════
    private function getSubgruposGenerico(int $prodId, int $paramId,
                                           ?string $desde, ?string $hasta): array
    {
        $sql="SELECT rs.*, sr.fecha, sr.turno, lp.codigo_lote AS lote_ref,
                     CONCAT(sr.fecha,' (',sr.turno,')') AS fecha_label
              FROM reg_subgrupos_spc rs
              JOIN sesiones_registro sr ON sr.id=rs.sesion_id
              JOIN lotes_produccion lp ON lp.id=sr.lote_id
              WHERE lp.producto_id=? AND rs.parametro_id=?";
        $params=[$prodId,$paramId];
        if ($desde){$sql.=" AND sr.fecha>=?";$params[]=$desde;}
        if ($hasta){$sql.=" AND sr.fecha<=?";$params[]=$hasta;}
        $sql.=" ORDER BY sr.fecha ASC, rs.hora ASC";

        $rows=$this->db->fetchAll($sql,$params);
        foreach ($rows as &$row) {
            $valores            = json_decode($row['valores'], true) ?: [];
            $row['valores_raw'] = array_map('floatval', $valores);
            $row['n']           = count($row['valores_raw']);
            $row['xbar']        = (float)$row['promedio_xbar'];
            $row['valor']       = $row['valores_raw'][0] ?? null; // para X-MR (n=1)
        }
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
            "SELECT id,nombre,etapa,unidad,tipo_dato,
                    valor_min AS lie,valor_max AS lse,
                    valor_nominal AS nominal,tamanio_subgrupo
             FROM parametros_proceso WHERE id=?",[$paramId]);
    }
    private function getProductos(): array
    {
        return $this->db->fetchAll("SELECT id,nombre FROM productos WHERE activo=1 ORDER BY nombre");
    }
    private function getParametrosSPC(): array
    {
        return $this->db->fetchAll(
            "SELECT p.id,p.producto_id,pr.nombre AS producto_nombre,
                    p.nombre AS parametro_nombre,p.etapa,p.unidad,
                    p.valor_min AS lie,p.valor_max AS lse,
                    p.valor_nominal AS nominal,p.tamanio_subgrupo,p.tipo_dato
             FROM parametros_proceso p JOIN productos pr ON pr.id=p.producto_id
             WHERE p.es_variable_spc=1 AND p.activo=1
               AND p.tipo_dato IN ('numerico','seleccion','si_no')
             ORDER BY pr.nombre,p.etapa,p.nombre");
    }
}