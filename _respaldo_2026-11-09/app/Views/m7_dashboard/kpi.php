<?php
/** @var array $oee @var array $kpisProducto @var array $tendencia
 *  @var array $mermas @var array $senalesSpc @var array $productos
 *  @var array $filtros */
?>

<div class="page-header">
    <div>
        <div class="page-title">🎯 KPIs y OEE</div>
        <div class="page-sub">
            Indicadores del período ·
            <?= date('d/m/Y', strtotime($filtros['fechaDesde'])) ?>
            al <?= date('d/m/Y', strtotime($filtros['fechaHasta'])) ?>
        </div>
    </div>
</div>

<!-- ── FILTROS ─────────────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-funnel-fill" style="margin-right:6px;color:#2563eb"></i>
            Filtros del período
        </span>
    </div>
    <form method="GET" action="<?= APP_URL ?>/m7">
    <div class="card-body" style="padding:16px 20px">
        <div class="form-row cols-4">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control"
                        value="<?= $filtros['fechaDesde'] ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control"
                        value="<?= $filtros['fechaHasta'] ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-control">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= $filtros['productoId'] == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Agrupar tendencia por</label>
                <select name="agrupar" class="form-control">
                    <option value="dia"    <?= $filtros['agrupar']==='dia'    ? 'selected':'' ?>>Día</option>
                    <option value="semana" <?= $filtros['agrupar']==='semana' ? 'selected':'' ?>>Semana</option>
                    <option value="mes"    <?= $filtros['agrupar']==='mes'    ? 'selected':'' ?>>Mes</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m7" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Limpiar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i> Aplicar filtros
        </button>
    </div>
    </form>
</div>

<!-- ── OEE PRINCIPAL ───────────────────────────────────────────────────────── -->
<?php
$oeeColor  = $oee['clasificacion']['color'];
$oeeLabel  = $oee['clasificacion']['label'];
$oeeVal    = number_format($oee['oee'], 1);
$oeeBarW   = min(100, (float)$oee['oee']);
$dispPct   = number_format($oee['disponibilidad']['pct'], 1);
$rendPct   = number_format($oee['rendimiento']['pct'], 1);
$calPct    = number_format($oee['calidad']['pct'], 1);
?>
<div class="card" style="margin-bottom:20px;border-left:4px solid <?= $oeeColor ?>">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-lightning-charge-fill"
                style="color:<?= $oeeColor ?>;margin-right:6px"></i>
            OEE — Overall Equipment Effectiveness
        </span>
        <span class="badge"
                style="background:<?= $oeeColor ?>;color:#fff;font-size:12px;padding:4px 12px">
            <?= $oeeLabel ?>
        </span>
    </div>
    <div class="card-body">

        <!-- OEE valor grande -->
        <div style="text-align:center;margin-bottom:24px">
            <div style="font-size:11px;font-weight:700;color:#71717a;
                        text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">
                OEE del período
            </div>
            <div style="font-size:60px;font-weight:900;line-height:1;color:<?= $oeeColor ?>">
                <?= $oeeVal ?>%
            </div>
            <div style="font-size:13px;color:#71717a;margin-top:8px">
                D × R × C =
                <?= $dispPct ?>% × <?= $rendPct ?>% × <?= $calPct ?>%
            </div>
            <!-- Barra OEE -->
            <div style="height:10px;background:#f4f4f5;border-radius:6px;
                        max-width:400px;margin:14px auto 0;overflow:hidden">
                <div style="height:100%;border-radius:6px;transition:width .4s;
                            width:<?= $oeeBarW ?>%;background:<?= $oeeColor ?>">
                </div>
            </div>
            <div style="font-size:11px;color:#a1a1aa;margin-top:6px">
                ≥85% Clase Mundial · ≥75% Bueno · ≥65% Regular
            </div>
        </div>

        <!-- Los 3 componentes -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">

            <?php
            $comps = [
                [
                    'icon'   => 'bi-clock-fill',
                    'titulo' => 'Disponibilidad',
                    'pct'    => (float)$oee['disponibilidad']['pct'],
                    'datos'  => [
                        'T. planificado' => number_format($oee['disponibilidad']['tiempo_plan_min']/60,1).'h',
                        'Paros'          => number_format($oee['disponibilidad']['paros_min']/60,1).'h',
                        'T. operativo'   => number_format($oee['disponibilidad']['tiempo_op_min']/60,1).'h',
                    ],
                ],
                [
                    'icon'   => 'bi-gear-fill',
                    'titulo' => 'Rendimiento',
                    'pct'    => (float)$oee['rendimiento']['pct'],
                    'datos'  => [
                        'Teórico' => number_format($oee['rendimiento']['unidades_teorico']).' und',
                        'Real'    => number_format($oee['rendimiento']['unidades_reales']).' und',
                        'Dif.'    => ($oee['rendimiento']['diferencia']>=0?'+':'').number_format($oee['rendimiento']['diferencia']).' und',
                    ],
                ],
                [
                    'icon'   => 'bi-shield-check',
                    'titulo' => 'Calidad',
                    'pct'    => (float)$oee['calidad']['pct'],
                    'datos'  => [
                        'Buenas' => number_format($oee['calidad']['unidades_buenas']).' und',
                        'Total'  => number_format($oee['calidad']['unidades_total']).' und',
                        'Malas'  => number_format($oee['calidad']['unidades_malas']).' und',
                    ],
                ],
            ];

            foreach ($comps as $c):
                $colorC = $c['pct'] >= 90 ? '#16a34a'
                        : ($c['pct'] >= 75 ? '#d97706' : '#dc2626');
                $pctVal = number_format($c['pct'], 1);
                $barWC  = min(100, $c['pct']);
            ?>
            <div style="border:1px solid #e4e4e7;border-radius:8px;padding:16px;
                        border-top:3px solid <?= $colorC ?>">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                    <i class="bi <?= $c['icon'] ?>"
                        style="font-size:15px;color:<?= $colorC ?>"></i>
                    <span style="font-size:12px;font-weight:700;color:#3f3f46;
                                text-transform:uppercase;letter-spacing:.06em">
                        <?= $c['titulo'] ?>
                    </span>
                </div>
                <div style="font-size:32px;font-weight:900;color:<?= $colorC ?>;
                            margin-bottom:10px">
                    <?= $pctVal ?>%
                </div>
                <div style="height:5px;background:#f4f4f5;border-radius:4px;
                            margin-bottom:12px;overflow:hidden">
                    <div style="height:100%;border-radius:4px;
                                width:<?= $barWC ?>%;background:<?= $colorC ?>">
                    </div>
                </div>
                <?php foreach ($c['datos'] as $lbl => $val): ?>
                <div style="display:flex;justify-content:space-between;
                            font-size:12px;padding:3px 0;
                            border-bottom:1px solid #f4f4f5">
                    <span style="color:#71717a"><?= $lbl ?></span>
                    <span style="font-weight:600;color:#09090b"><?= $val ?></span>
                </div>
                <?php endforeach ?>
            </div>
            <?php endforeach ?>

        </div>
    </div>
</div>

<!-- ── TENDENCIA + MERMAS ──────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-graph-up-arrow" style="margin-right:6px;color:#2563eb"></i>
                Tendencia de producción
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($tendencia)): ?>
            <div style="text-align:center;padding:40px;color:#71717a">
                Sin datos en el período seleccionado.
            </div>
            <?php else: ?>
            <canvas id="graficoTendencia" height="160"></canvas>
            <?php endif ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-pie-chart-fill" style="margin-right:6px;color:#dc2626"></i>
                Mermas por tipo
            </span>
        </div>
        <div class="card-body">
            <?php $totalMerma = array_sum($mermas); ?>
            <?php if ($totalMerma > 0): ?>
            <canvas id="graficoMermas" height="180"></canvas>
            <div style="text-align:center;margin-top:10px;font-size:13px;
                        font-weight:700;color:#dc2626">
                Total: <?= number_format($totalMerma, 3) ?> kg
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:#71717a">
                Sin mermas registradas.
            </div>
            <?php endif ?>
        </div>
    </div>

</div>

<!-- ── KPIs POR PRODUCTO ───────────────────────────────────────────────────── -->
<?php if (!empty($kpisProducto)): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-bar-chart-fill" style="margin-right:6px;color:#2563eb"></i>
            KPIs por producto
        </span>
        <span class="badge badge-muted"><?= count($kpisProducto) ?> producto(s)</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center">Lotes</th>
                    <th style="text-align:right">Und. reales</th>
                    <th style="text-align:right">Und. teóricas</th>
                    <th style="text-align:center">Rend. prom.</th>
                    <th style="text-align:right">Merma (kg)</th>
                    <th style="text-align:center">Liberados</th>
                    <th style="text-align:center">Cuarentena</th>
                    <th style="text-align:center">Rechazados</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kpisProducto as $k):
                $rend    = (float)($k['rend_promedio'] ?? 0);
                $colorR  = $rend >= 95 ? '#16a34a' : ($rend >= 85 ? '#d97706' : '#dc2626');
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($k['producto_nombre']) ?></strong>
                    <div style="font-size:11px;color:#71717a">
                        <?= htmlspecialchars($k['producto_codigo']) ?>
                    </div>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= (int)$k['total_lotes'] ?>
                </td>
                <td style="text-align:right;font-weight:700">
                    <?= number_format((int)$k['und_reales']) ?>
                </td>
                <td style="text-align:right;color:#71717a">
                    <?= number_format((int)$k['und_teoricas']) ?>
                </td>
                <td style="text-align:center">
                    <span style="font-weight:800;font-size:15px;color:<?= $colorR ?>">
                        <?= number_format($rend, 1) ?>%
                    </span>
                </td>
                <td style="text-align:right;color:#dc2626;font-weight:600">
                    <?= number_format((float)($k['merma_total_kg'] ?? 0), 3) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-success">
                        <?= (int)($k['liberados'] ?? 0) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= ($k['cuarentena']??0)>0 ? 'badge-warning':'badge-muted' ?>">
                        <?= (int)($k['cuarentena'] ?? 0) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= ($k['rechazados']??0)>0 ? 'badge-danger':'badge-muted' ?>">
                        <?= (int)($k['rechazados'] ?? 0) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- ── SEÑALES SPC ─────────────────────────────────────────────────────────── -->
<?php if (!empty($senalesSpc)): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-exclamation-diamond-fill"
                style="margin-right:6px;color:#d97706"></i>
            Señales SPC detectadas en el período
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center">Total señales</th>
                    <th style="text-align:center">Sin resolver</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($senalesSpc as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['producto_nombre']) ?></td>
                <td style="text-align:center;font-weight:700">
                    <?= (int)$s['total_senales'] ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $s['sin_resolver']>0 ? 'badge-danger':'badge-success' ?>">
                        <?= (int)$s['sin_resolver'] ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
<?php if (!empty($tendencia)): ?>
(function() {
    var ctx = document.getElementById('graficoTendencia').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($tendencia, 'periodo')) ?>,
            datasets: [
                {
                    label: 'Unidades',
                    data: <?= json_encode(array_map(fn($t) => (int)$t['unidades'], $tendencia)) ?>,
                    backgroundColor: 'rgba(37,99,235,.15)',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Rendimiento (%)',
                    data: <?= json_encode(array_map(fn($t) => round((float)($t['rend_pct']??0),1), $tendencia)) ?>,
                    type: 'line',
                    borderColor: '#16a34a',
                    backgroundColor: 'transparent',
                    pointBackgroundColor: '#16a34a',
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top', labels: { font: { size: 12 } } } },
            scales: {
                y:  { title: { display: true, text: 'Unidades' }, position: 'left' },
                y2: { title: { display: true, text: 'Rendimiento (%)' },
                        position: 'right', min: 0, max: 100,
                        grid: { drawOnChartArea: false } }
            }
        }
    });
})();
<?php endif ?>

<?php if (!empty($mermas) && array_sum($mermas) > 0): ?>
(function() {
    var ctx2 = document.getElementById('graficoMermas').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($mermas)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($mermas)) ?>,
                backgroundColor: ['#dc2626','#d97706','#7c3aed','#ea580c','#6b7280'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 8 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.label + ': ' + parseFloat(ctx.raw).toFixed(3) + ' kg';
                        }
                    }
                }
            },
            cutout: '65%',
        }
    });
})();
<?php endif ?>
</script>