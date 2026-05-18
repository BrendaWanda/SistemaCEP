<?php
/** @var array $productos @var array $filtros @var array $subgrupos
 *  @var array $limites @var array $capacidad @var array $grafico
 *  @var array $estadistica @var array|null $producto @var array $stats */
?>

<div class="page-header">
    <div>
        <div class="page-title">📊 Control Estadístico de Proceso (SPC)</div>
        <div class="page-sub">
            Gráfico X̄-R · Índices Cp/Cpk · Reglas de Western Electric
        </div>
    </div>
</div>

<!-- Estadísticas generales -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">📊</div>
        <div>
            <div class="stat-value"><?= $stats['total_subgrupos'] ?></div>
            <div class="stat-label">Subgrupos registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⚠️</div>
        <div>
            <div class="stat-value"
                    style="color:<?= $stats['senales_nuevas'] > 0 ? '#dc2626' : '#15803d' ?>">
                <?= $stats['senales_nuevas'] ?>
            </div>
            <div class="stat-label">Señales sin resolver</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">🏭</div>
        <div>
            <div class="stat-value"><?= $stats['lotes_con_senales'] ?></div>
            <div class="stat-label">Lotes con señales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">🍞</div>
        <div>
            <div class="stat-value"><?= $stats['productos_analizados'] ?></div>
            <div class="stat-label">Productos analizados</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">🔍 Filtros de análisis</span></div>
    <form method="GET" action="<?= APP_URL ?>/m6">
    <div class="card-body">
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-control">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= ($filtros['productoId'] ?? '') == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control"
                        value="<?= htmlspecialchars($filtros['fechaDesde'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control"
                        value="<?= htmlspecialchars($filtros['fechaHasta'] ?? '') ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end">
                <button type="submit" class="btn btn-primary btn-full">
                    📊 Analizar
                </button>
            </div>
        </div>
    </div>
    </form>
</div>

<?php if (empty($subgrupos)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:60px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">📊</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px">
            Sin datos para analizar
        </div>
        <div style="font-size:13px">
            Seleccione un producto y rango de fechas para ver el análisis SPC.
            Los datos provienen de los subgrupos registrados en el M2.
        </div>
    </div>
</div>
<?php else: ?>

<!-- Info del producto analizado -->
<?php if ($producto): ?>
<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
            padding:10px 16px;margin-bottom:16px;font-size:12px;
            display:flex;gap:24px;flex-wrap:wrap">
    <span>🍞 <strong><?= htmlspecialchars($producto['nombre']) ?></strong></span>
    <span>⚖️ Nominal: <strong><?= number_format($producto['peso_nominal_g'],1) ?>g</strong></span>
    <span style="color:#15803d">⬆️ LSE: <strong><?= number_format($producto['lse_g'],3) ?>g</strong></span>
    <span style="color:#dc2626">⬇️ LIE: <strong><?= number_format($producto['lie_g'],3) ?>g</strong></span>
    <span>📋 Subgrupos analizados: <strong><?= count($subgrupos) ?></strong></span>
    <?php if ($grafico['senales'] ?? 0): ?>
    <span style="color:#dc2626;font-weight:700">
        ⚠️ <?= $grafico['senales'] ?> señal(es) detectada(s)
    </span>
    <?php else: ?>
    <span style="color:#15803d;font-weight:700">✓ Proceso en control</span>
    <?php endif ?>
</div>
<?php endif ?>

<!-- Gráfico X̄-R -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">📈 Gráfico X̄-R</span>
        <?php if (!empty($limites)): ?>
        <div style="display:flex;gap:12px;font-size:12px">
            <span>X̄̄ = <strong><?= number_format($limites['cl_xbar'] ?? 0, 3) ?></strong></span>
            <span style="color:#dc2626">UCL = <strong><?= number_format($limites['ucl_xbar'] ?? 0, 3) ?></strong></span>
            <span style="color:#dc2626">LCL = <strong><?= number_format($limites['lcl_xbar'] ?? 0, 3) ?></strong></span>
            <span>k = <strong><?= $limites['k'] ?? 0 ?></strong> subgrupos</span>
        </div>
        <?php endif ?>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr;gap:16px">
            <!-- Gráfico X̄ -->
            <div>
                <div style="font-size:12px;font-weight:700;color:#374151;
                            margin-bottom:8px">
                    Gráfico X̄ (medias)
                </div>
                <canvas id="graficoXbar" height="120"></canvas>
            </div>
            <!-- Gráfico R -->
            <div>
                <div style="font-size:12px;font-weight:700;color:#374151;
                            margin-bottom:8px">
                    Gráfico R (rangos)
                </div>
                <canvas id="graficoR" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Índices de capacidad + Estadística -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;
            margin-bottom:16px">

    <!-- Capacidad del proceso -->
    <?php if (!empty($capacidad)): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">🎯 Índices de capacidad</span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;
                        margin-bottom:12px">
                <?php
                $indices = [
                    ['Cp',  $capacidad['cp'],  'Capacidad potencial'],
                    ['Cpk', $capacidad['cpk'], 'Capacidad real'],
                    ['Pp',  $capacidad['pp'],  'Desempeño potencial'],
                    ['Ppk', $capacidad['ppk'], 'Desempeño real'],
                ];
                foreach ($indices as [$nombre, $valor, $desc]):
                    if ($valor === null) continue;
                    $color = $valor >= 1.33 ? '#15803d'
                            : ($valor >= 1.0  ? '#d97706' : '#dc2626');
                    $estado = $valor >= 1.33 ? '✅' : ($valor >= 1.0 ? '⚠️' : '❌');
                ?>
                <div style="text-align:center;padding:12px;background:#f8fafc;
                            border-radius:8px;border:1px solid #e2e8f0">
                    <div style="font-size:11px;color:#64748b;
                                text-transform:uppercase;font-weight:700">
                        <?= $nombre ?>
                    </div>
                    <div style="font-size:28px;font-weight:900;color:<?= $color ?>">
                        <?= $estado ?> <?= number_format($valor, 3) ?>
                    </div>
                    <div style="font-size:10px;color:#94a3b8"><?= $desc ?></div>
                </div>
                <?php endforeach ?>
            </div>

            <!-- Interpretación -->
            <?php if ($capacidad['cpk'] !== null): ?>
            <div style="padding:8px 12px;border-radius:6px;font-size:12px;
                        background:<?= $capacidad['cpk'] >= 1.33 ? '#f0fdf4'
                            : ($capacidad['cpk'] >= 1.0 ? '#fffbeb' : '#fef2f2') ?>;
                        border:1px solid <?= $capacidad['cpk'] >= 1.33 ? '#86efac'
                            : ($capacidad['cpk'] >= 1.0 ? '#fde68a' : '#fca5a5') ?>">
                <strong>Interpretación Cpk:</strong>
                <?= htmlspecialchars($capacidad['interpretacion_cpk']) ?>
            </div>
            <?php endif ?>

            <div style="margin-top:10px;font-size:11px;color:#64748b">
                <div>σ estimado: <strong><?= number_format($capacidad['sigma_est'] ?? 0, 6) ?></strong></div>
                <div>LSE: <strong><?= number_format($capacidad['lse'], 3) ?>g</strong> ·
                        LIE: <strong><?= number_format($capacidad['lie'], 3) ?>g</strong></div>
            </div>
        </div>
    </div>
    <?php endif ?>

    <!-- Estadística descriptiva -->
    <?php if (!empty($estadistica)): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">📐 Estadística descriptiva de X̄</span>
        </div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $estRows = [
                ['n (subgrupos)',   $estadistica['n']],
                ['Media (X̄̄)',       number_format($estadistica['media'],4).' g'],
                ['Mediana',         number_format($estadistica['mediana'],4).' g'],
                ['Desv. estándar',  number_format($estadistica['desv_std'],4).' g'],
                ['Varianza',        number_format($estadistica['varianza'],4)],
                ['Mínimo',          number_format($estadistica['min'],3).' g'],
                ['Máximo',          number_format($estadistica['max'],3).' g'],
                ['Rango',           number_format($estadistica['rango'],3).' g'],
                ['Q1',              number_format($estadistica['q1'],3).' g'],
                ['Q3',              number_format($estadistica['q3'],3).' g'],
                ['CV (%)',          $estadistica['cv_pct'] !== null
                                    ? number_format($estadistica['cv_pct'],2).'%'
                                    : '—'],
            ];
            foreach ($estRows as [$label, $valor]):
            ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:5px 0;color:#64748b;font-size:12px">
                    <?= $label ?>
                </td>
                <td style="padding:5px 0;font-weight:600;text-align:right">
                    <?= $valor ?>
                </td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>

<!-- Tabla de señales SPC -->
<?php
$senales = array_filter($subgrupos, fn($s) => $s['fuera_de_control']);
if (!empty($senales)):
?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header" style="background:#fef2f2">
        <span class="card-title" style="color:#dc2626">
            ⚠️ Señales de fuera de control detectadas
        </span>
        <span class="badge badge-danger"><?= count($senales) ?> señal(es)</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="font-size:12px">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Lote</th>
                    <th style="text-align:center">X̄</th>
                    <th style="text-align:center">R</th>
                    <th>Regla violada</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($senales as $s): ?>
            <tr style="background:#fef2f2">
                <td><?= date('d/m/Y', strtotime($s['fecha'])) ?></td>
                <td><?= htmlspecialchars($s['hora']) ?></td>
                <td>
                    <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($s['codigo_lote']) ?>"
                        style="font-family:monospace;font-weight:700;color:#dc2626">
                        <?= htmlspecialchars($s['codigo_lote']) ?>
                    </a>
                </td>
                <td style="text-align:center;font-weight:700;color:#dc2626">
                    <?= number_format((float)$s['promedio_xbar'],3) ?>
                </td>
                <td style="text-align:center;font-weight:700">
                    <?= number_format((float)$s['rango_r'],3) ?>
                </td>
                <td style="color:#dc2626;font-size:11px">
                    <?= htmlspecialchars($s['regla_violada'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<?php endif ?>

<script src="<?= APP_URL ?>/assets/vendor/chart.min.js"></script>
<script>
<?php if (!empty($grafico) && !empty($grafico['puntos'])): ?>

const puntos  = <?= json_encode($grafico['puntos'], JSON_UNESCAPED_UNICODE) ?>;
const limites = <?= json_encode($grafico['limites'] ?? [], JSON_UNESCAPED_UNICODE) ?>;

const labels   = puntos.map((p, i) => 'Sg '+(i+1));
const xbarVals = puntos.map(p => p.xbar);
const rVals    = puntos.map(p => p.r);
const colors   = puntos.map(p => p.fuera_control ? '#dc2626' : '#2563eb');

// ── Gráfico X̄ ─────────────────────────────────────────────────────────────
const ctxXbar = document.getElementById('graficoXbar').getContext('2d');
new Chart(ctxXbar, {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'X̄', data: xbarVals,
                borderColor: '#2563eb',
                backgroundColor: 'transparent',
                pointBackgroundColor: colors,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.3,
            },
            {
                label: 'UCL = <?= number_format($limites['ucl_xbar'] ?? 0, 3) ?>',
                data: Array(xbarVals.length).fill(limites.ucl_xbar),
                borderColor: '#dc2626', borderDash: [5,3],
                pointRadius: 0, fill: false,
            },
            {
                label: 'CL = <?= number_format($limites['cl_xbar'] ?? 0, 3) ?>',
                data: Array(xbarVals.length).fill(limites.cl_xbar),
                borderColor: '#15803d', borderDash: [3,3],
                pointRadius: 0, fill: false,
            },
            {
                label: 'LCL = <?= number_format($limites['lcl_xbar'] ?? 0, 3) ?>',
                data: Array(xbarVals.length).fill(limites.lcl_xbar),
                borderColor: '#dc2626', borderDash: [5,3],
                pointRadius: 0, fill: false,
            },
            <?php if ($producto): ?>
            {
                label: 'LSE = <?= number_format($producto['lse_g'] ?? 0, 3) ?>',
                data: Array(xbarVals.length).fill(<?= (float)($producto['lse_g'] ?? 0) ?>),
                borderColor: '#f97316', borderDash: [8,4],
                pointRadius: 0, fill: false, borderWidth: 1,
            },
            {
                label: 'LIE = <?= number_format($producto['lie_g'] ?? 0, 3) ?>',
                data: Array(xbarVals.length).fill(<?= (float)($producto['lie_g'] ?? 0) ?>),
                borderColor: '#f97316', borderDash: [8,4],
                pointRadius: 0, fill: false, borderWidth: 1,
            },
            <?php endif ?>
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const p = puntos[ctx.dataIndex];
                        if (!p) return ctx.dataset.label + ': ' + ctx.raw;
                        let label = 'X̄ = ' + ctx.raw.toFixed(3) + 'g';
                        if (p.fuera_control) label += ' ⚠️ ' + (p.regla_violada || 'Señal');
                        return label;
                    }
                }
            }
        },
        scales: {
            y: { title: { display: true, text: 'X̄ (g)' } },
            x: { title: { display: true, text: 'Subgrupo' } }
        }
    }
});

// ── Gráfico R ─────────────────────────────────────────────────────────────
const ctxR = document.getElementById('graficoR').getContext('2d');
new Chart(ctxR, {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'R', data: rVals,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.1)',
                pointRadius: 4, tension: 0.3, fill: true,
            },
            {
                label: 'UCL_R = <?= number_format($limites['ucl_r'] ?? 0, 3) ?>',
                data: Array(rVals.length).fill(limites.ucl_r),
                borderColor: '#dc2626', borderDash: [5,3],
                pointRadius: 0, fill: false,
            },
            {
                label: 'R̄ = <?= number_format($limites['cl_r'] ?? 0, 3) ?>',
                data: Array(rVals.length).fill(limites.cl_r),
                borderColor: '#15803d', borderDash: [3,3],
                pointRadius: 0, fill: false,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
        scales: {
            y: { title: { display: true, text: 'R (g)' }, min: 0 },
            x: { title: { display: true, text: 'Subgrupo' } }
        }
    }
});

<?php endif ?>
</script>