<?php
/** @var array $oee @var array $kpisProducto @var array $tendencia
 *  @var array $mermas @var array $stockCritico @var array $kpisGenerales
 *  @var string $fechaDesde @var string $fechaHasta */
?>

<div class="page-header">
    <div>
        <div class="page-title">📊 Dashboard — KPIs y OEE</div>
        <div class="page-sub">
            <?= date('d/m/Y', strtotime($fechaDesde)) ?> al
            <?= date('d/m/Y', strtotime($fechaHasta)) ?>
        </div>
    </div>
    <div class="page-actions">
        <form method="GET" action="<?= APP_URL ?>/dashboard"
                style="display:flex;gap:8px;align-items:center">
            <input type="date" name="fecha_desde" class="form-control"
                    value="<?= $fechaDesde ?>"
                    style="width:140px">
            <input type="date" name="fecha_hasta" class="form-control"
                    value="<?= $fechaHasta ?>"
                    style="width:140px">
            <button type="submit" class="btn btn-primary">
                📊 Actualizar
            </button>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
    OEE — Overall Equipment Effectiveness
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:16px;
        border:2px solid <?= $oee['clasificacion']['color'] ?>">
    <div class="card-header"
            style="background:<?= $oee['clasificacion']['color'] ?>;color:#fff">
        <span class="card-title" style="color:#fff;font-size:16px">
            ⚡ OEE — Overall Equipment Effectiveness
        </span>
        <span style="color:#fff;font-size:12px;opacity:.85">
            <?= $oee['clasificacion']['label'] ?>
        </span>
    </div>
    <div class="card-body">
        <!-- OEE Principal -->
        <div style="text-align:center;margin-bottom:20px">
            <div style="font-size:11px;font-weight:700;color:#64748b;
                        text-transform:uppercase;letter-spacing:.08em;
                        margin-bottom:6px">
                OEE del período
            </div>
            <div style="font-size:56px;font-weight:900;
                        color:<?= $oee['clasificacion']['color'] ?>;
                        line-height:1">
                <?= number_format($oee['oee'], 1) ?>%
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:4px">
                D × R × C =
                <?= number_format($oee['disponibilidad']['pct'],1) ?>% ×
                <?= number_format($oee['rendimiento']['pct'],1) ?>% ×
                <?= number_format($oee['calidad']['pct'],1) ?>%
            </div>
            <!-- Barra OEE -->
            <div style="height:12px;background:#f1f5f9;border-radius:8px;
                        margin:10px auto;max-width:400px;overflow:hidden">
                <?php
                $barraOeeStyle = 'height:100%;width:'.min(100,$oee['oee']).'%;'
                    .'background:'.$oee['clasificacion']['color'].';border-radius:8px';
                ?>
                <div style="<?= $barraOeeStyle ?>"></div>
            </div>
            <div style="font-size:11px;color:#94a3b8">
                Referencia: ≥85% Clase Mundial · ≥75% Bueno · ≥65% Regular
            </div>
        </div>

        <!-- Los 3 componentes -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">

            <!-- Disponibilidad -->
            <?php
            $D = $oee['disponibilidad'];
            $colorD = $D['pct'] >= 90 ? '#15803d' : ($D['pct'] >= 75 ? '#d97706' : '#dc2626');
            ?>
            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;
                        text-align:center">
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:6px">
                    ⏱️ Disponibilidad
                </div>
                <div style="font-size:32px;font-weight:900;color:<?= $colorD ?>">
                    <?= number_format($D['pct'],1) ?>%
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:4px;
                            margin:8px 0;overflow:hidden">
                    <?php $barraD = 'height:100%;width:'.min(100,$D['pct']).'%;background:'.$colorD.';border-radius:4px'; ?>
                    <div style="<?= $barraD ?>"></div>
                </div>
                <div style="font-size:11px;color:#64748b;line-height:1.6">
                    Tiempo planificado:
                    <strong><?= number_format($D['tiempo_plan_min']/60,1) ?>h</strong><br>
                    Paros correctivos:
                    <strong style="color:#dc2626">
                        <?= number_format($D['paros_min']/60,1) ?>h
                    </strong><br>
                    Tiempo operativo:
                    <strong><?= number_format($D['tiempo_op_min']/60,1) ?>h</strong>
                </div>
            </div>

            <!-- Rendimiento -->
            <?php
            $R = $oee['rendimiento'];
            $colorR = $R['pct'] >= 90 ? '#15803d' : ($R['pct'] >= 75 ? '#d97706' : '#dc2626');
            ?>
            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;
                        text-align:center">
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:6px">
                    ⚙️ Rendimiento
                </div>
                <div style="font-size:32px;font-weight:900;color:<?= $colorR ?>">
                    <?= number_format($R['pct'],1) ?>%
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:4px;
                            margin:8px 0;overflow:hidden">
                    <?php $barraR = 'height:100%;width:'.min(100,$R['pct']).'%;background:'.$colorR.';border-radius:4px'; ?>
                    <div style="<?= $barraR ?>"></div>
                </div>
                <div style="font-size:11px;color:#64748b;line-height:1.6">
                    Und. teóricas:
                    <strong><?= number_format($R['unidades_teorico']) ?></strong><br>
                    Und. reales:
                    <strong><?= number_format($R['unidades_reales']) ?></strong><br>
                    Diferencia:
                    <strong style="color:<?= $R['diferencia'] >= 0 ? '#15803d' : '#dc2626' ?>">
                        <?= $R['diferencia'] >= 0 ? '+' : '' ?>
                        <?= number_format($R['diferencia']) ?>
                    </strong>
                </div>
            </div>

            <!-- Calidad -->
            <?php
            $C = $oee['calidad'];
            $colorC = $C['pct'] >= 95 ? '#15803d' : ($C['pct'] >= 85 ? '#d97706' : '#dc2626');
            ?>
            <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;
                        text-align:center">
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:6px">
                    ✅ Calidad
                </div>
                <div style="font-size:32px;font-weight:900;color:<?= $colorC ?>">
                    <?= number_format($C['pct'],1) ?>%
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:4px;
                            margin:8px 0;overflow:hidden">
                    <?php $barraC = 'height:100%;width:'.min(100,$C['pct']).'%;background:'.$colorC.';border-radius:4px'; ?>
                    <div style="<?= $barraC ?>"></div>
                </div>
                <div style="font-size:11px;color:#64748b;line-height:1.6">
                    Und. buenas (liberadas):
                    <strong style="color:#15803d">
                        <?= number_format($C['unidades_buenas']) ?>
                    </strong><br>
                    Und. total producidas:
                    <strong><?= number_format($C['unidades_total']) ?></strong><br>
                    Und. con problemas:
                    <strong style="color:#dc2626">
                        <?= number_format($C['unidades_malas']) ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KPIs generales del sistema -->
<div class="stats-grid" style="margin-bottom:16px">
    <?php
    $kpiCards = [
        ['🏭', 'Lotes producidos',     $kpisGenerales['lotes_mes'],       '#dbeafe', null],
        ['🚛', 'Recepciones MP',        $kpisGenerales['recepciones_mes'], '#dcfce7', null],
        ['📋', 'Sesiones registro',     $kpisGenerales['sesiones_mes'],    '#fef9c3', null],
        ['⚠️', 'Señales SPC nuevas',    $kpisGenerales['senales_spc'],     '#fee2e2',
            $kpisGenerales['senales_spc'] > 0 ? '#dc2626' : null],
        ['🔧', 'Mant. pendientes',      $kpisGenerales['mant_pendientes'], '#fff7ed',
            $kpisGenerales['mant_pendientes'] > 0 ? '#d97706' : null],
        ['📦', 'Stock alertas (30d)',   $kpisGenerales['stock_alertas'],   '#fee2e2',
            $kpisGenerales['stock_alertas'] > 0 ? '#dc2626' : null],
    ];
    foreach ($kpiCards as [$icon, $label, $valor, $bg, $color]):
    ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $bg ?>"><?= $icon ?></div>
        <div>
            <div class="stat-value"
                <?= $color ? "style=\"color:{$color}\"" : '' ?>>
                <?= $valor ?>
            </div>
            <div class="stat-label"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<!-- Tendencia diaria + Mermas -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;
            margin-bottom:16px">

    <!-- Tendencia de producción -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">📈 Tendencia diaria de producción</span>
        </div>
        <div class="card-body">
            <?php if (empty($tendencia)): ?>
            <div class="text-center text-muted" style="padding:30px">
                Sin datos de producción en el período.
            </div>
            <?php else: ?>
            <canvas id="graficTendencia" height="120"></canvas>
            <?php endif ?>
        </div>
    </div>

    <!-- Mermas por tipo -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">⚠️ Mermas por tipo (kg)</span>
        </div>
        <div class="card-body">
            <?php
            $totalMerma = array_sum($mermas);
            if ($totalMerma > 0):
            ?>
            <canvas id="graficoMermas" height="180"
                    style="margin-bottom:12px"></canvas>
            <div style="font-size:12px;font-weight:700;text-align:center;
                        color:#dc2626">
                Total: <?= number_format($totalMerma, 3) ?> kg
            </div>
            <?php else: ?>
            <div class="text-center text-muted" style="padding:30px">
                Sin mermas registradas en el período.
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- KPIs por producto -->
<?php if (!empty($kpisProducto)): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">🍞 KPIs por producto</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center">Lotes</th>
                    <th style="text-align:center">Und. reales</th>
                    <th style="text-align:center">Rend. prom.</th>
                    <th style="text-align:center">Merma total</th>
                    <th style="text-align:center">Liberados</th>
                    <th style="text-align:center">Cuarentena</th>
                    <th style="text-align:center">Rechazados</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kpisProducto as $k):
                $rend = (float)($k['rend_promedio'] ?? 0);
                $colorRend = $rend >= 95 ? '#15803d'
                            : ($rend >= 85 ? '#d97706' : '#dc2626');
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($k['producto_nombre']) ?></strong>
                    <div style="font-size:11px;color:#94a3b8">
                        <?= htmlspecialchars($k['producto_codigo']) ?>
                    </div>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= (int)$k['total_lotes'] ?>
                </td>
                <td style="text-align:center;font-weight:700">
                    <?= number_format((int)$k['und_reales']) ?>
                </td>
                <td style="text-align:center">
                    <span style="font-weight:700;color:<?= $colorRend ?>">
                        <?= number_format($rend, 1) ?>%
                    </span>
                </td>
                <td style="text-align:center;color:#dc2626;font-weight:600">
                    <?= number_format((float)($k['merma_total_kg'] ?? 0), 3) ?> kg
                </td>
                <td style="text-align:center">
                    <span class="badge badge-success">
                        <?= (int)($k['liberados'] ?? 0) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $k['cuarentena'] > 0
                        ? 'badge-warning' : 'badge-muted' ?>">
                        <?= (int)($k['cuarentena'] ?? 0) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $k['rechazados'] > 0
                        ? 'badge-danger' : 'badge-muted' ?>">
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

<!-- Stock MP crítico -->
<?php if (!empty($stockCritico)): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">📦 Stock MP — Vista rápida</span>
        <a href="<?= APP_URL ?>/m1/stock"
            class="btn btn-sm btn-secondary">Ver completo</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th style="text-align:right">Stock disponible</th>
                    <th style="text-align:center">Próx. vencimiento</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stockCritico as $s):
                $dias = $s['proximo_vence']
                    ? (int)((strtotime($s['proximo_vence']) - time()) / 86400)
                    : null;
                $colorStock = $s['stock'] <= 0 ? '#dc2626'
                            : ($s['stock'] < 10 ? '#d97706' : '#15803d');
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($s['descripcion']) ?></strong>
                    <div style="font-size:11px;color:#94a3b8">
                        <?= htmlspecialchars($s['codigo']) ?>
                    </div>
                </td>
                <td style="text-align:right;font-weight:700;
                            color:<?= $colorStock ?>">
                    <?= number_format((float)$s['stock'], 3) ?>
                    <?= htmlspecialchars($s['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?php if ($s['proximo_vence'] && $dias !== null): ?>
                    <span style="color:<?= $dias < 0 ? '#dc2626'
                                    : ($dias < 30 ? '#d97706' : '#374151') ?>;
                                    font-weight:<?= $dias < 30 ? '600' : '400' ?>">
                        <?= date('d/m/Y', strtotime($s['proximo_vence'])) ?>
                        (<?= $dias < 0 ? 'Vencido' : 'en '.$dias.'d' ?>)
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $s['stock'] <= 0 ? 'badge-danger'
                        : ($s['stock'] < 10 ? 'badge-warning' : 'badge-success') ?>">
                        <?= $s['stock'] <= 0 ? 'Sin stock'
                            : ($s['stock'] < 10 ? 'Stock bajo' : 'OK') ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<script src="<?= APP_URL ?>/assets/vendor/chart.min.js"></script>
<script>
<?php if (!empty($tendencia)): ?>
// ── Gráfico tendencia diaria ──────────────────────────────────────────────────
const tendenciaCtx = document.getElementById('graficTendencia').getContext('2d');
new Chart(tendenciaCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(
            fn($t) => date('d/m', strtotime($t['fecha'])),
            $tendencia
        )) ?>,
        datasets: [
            {
                label: 'Unidades producidas',
                data: <?= json_encode(array_column($tendencia,'unidades')) ?>,
                backgroundColor: 'rgba(37,99,235,.7)',
                borderColor: '#2563eb',
                borderWidth: 1,
                yAxisID: 'y',
            },
            {
                label: 'Rendimiento (%)',
                data: <?= json_encode(array_map(
                    fn($t) => round((float)($t['rend_pct'] ?? 0), 1),
                    $tendencia
                )) ?>,
                type: 'line',
                borderColor: '#15803d',
                pointRadius: 4,
                tension: 0.3,
                yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y:  { title: { display:true, text:'Unidades' }, position:'left' },
            y2: { title: { display:true, text:'Rendimiento (%)' },
                    position:'right', min:0, max:100,
                    grid: { drawOnChartArea:false } }
        }
    }
});
<?php endif ?>

<?php if (!empty($mermas) && array_sum($mermas) > 0): ?>
// ── Gráfico mermas ────────────────────────────────────────────────────────────
const mermasCtx = document.getElementById('graficoMermas').getContext('2d');
new Chart(mermasCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($mermas)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($mermas)) ?>,
            backgroundColor: [
                '#dc2626','#f97316','#7c3aed','#d97706','#92400e'
            ],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'bottom', labels:{ font:{size:10} } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.label+': '+ctx.raw.toFixed(3)+' kg'
                }
            }
        }
    }
});
<?php endif ?>
</script>