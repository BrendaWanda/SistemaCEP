<?php
/** @var array $oee @var array $kpisProducto @var array $mermas
 *  @var string $fechaDesde @var string $fechaHasta */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte KPIs y OEE</title>
<style>
<?php include __DIR__ . '/_estilos_reporte.php'; ?>
.oee-box {
    border:3px solid #1e3a5f;border-radius:8px;
    padding:16px;text-align:center;margin-bottom:14px;
}
.oee-valor {
    font-size:48px;font-weight:900;
    color:#1e3a5f;line-height:1;
}
.oee-label {
    font-size:10px;font-weight:700;
    text-transform:uppercase;color:#64748b;
    margin-bottom:6px;
}
.componentes {
    display:grid;grid-template-columns:1fr 1fr 1fr;
    gap:10px;margin-bottom:14px;
}
.comp-box {
    border:1px solid #e2e8f0;border-radius:6px;
    padding:10px;text-align:center;
}
.comp-val { font-size:24px;font-weight:900; }
.comp-lbl {
    font-size:8px;text-transform:uppercase;
    color:#64748b;margin-bottom:4px;
}
.comp-det { font-size:8px;color:#64748b;line-height:1.6;margin-top:4px; }
.barra-cont {
    height:6px;background:#f1f5f9;
    border-radius:4px;margin:6px 0;overflow:hidden;
}
.barra-fill { height:100%;border-radius:4px; }
</style>
</head>
<body>

<?php include __DIR__ . '/_encabezado_reporte.php'; ?>

<div class="titulo-reporte">Reporte Ejecutivo — KPIs y OEE</div>
<div class="subtitulo">
    Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?>
    al <?= date('d/m/Y', strtotime($fechaHasta)) ?>
    · Industrias Alimenticias Gustossi SRL
    · Programa Desayuno Escolar GAMLP
</div>

<!-- OEE Principal -->
<div class="oee-box"
     style="border-color:<?= $oee['clasificacion']['color'] ?>">
    <div class="oee-label">Overall Equipment Effectiveness (OEE)</div>
    <div class="oee-valor"
         style="color:<?= $oee['clasificacion']['color'] ?>">
        <?= number_format($oee['oee'], 1) ?>%
    </div>
    <div style="font-size:11px;color:<?= $oee['clasificacion']['color'] ?>;
                font-weight:700;margin-top:6px">
        <?= $oee['clasificacion']['label'] ?>
    </div>
    <div style="font-size:9px;color:#64748b;margin-top:4px">
        D × R × C =
        <?= number_format($oee['disponibilidad']['pct'],1) ?>% ×
        <?= number_format($oee['rendimiento']['pct'],1) ?>% ×
        <?= number_format($oee['calidad']['pct'],1) ?>%
    </div>
    <!-- Barra OEE -->
    <div class="barra-cont" style="max-width:300px;margin:8px auto">
        <?php $barOee = 'height:100%;width:'.min(100,$oee['oee']).'%;'
            .'background:'.$oee['clasificacion']['color'].';border-radius:4px'; ?>
        <div class="barra-fill" style="<?= $barOee ?>"></div>
    </div>
    <div style="font-size:8px;color:#94a3b8">
        Referencia: ≥85% Clase Mundial · ≥75% Bueno · ≥65% Regular
    </div>
</div>

<!-- Los 3 componentes -->
<div class="componentes">
    <?php
    $comps = [
        [
            'titulo' => '⏱️ Disponibilidad',
            'pct'    => $oee['disponibilidad']['pct'],
            'color'  => $oee['disponibilidad']['pct'] >= 90 ? '#15803d'
                      : ($oee['disponibilidad']['pct'] >= 75 ? '#d97706' : '#dc2626'),
            'detalles' => [
                'Tiempo planificado' => number_format($oee['disponibilidad']['tiempo_plan_min']/60,1).'h',
                'Paros correctivos'  => number_format($oee['disponibilidad']['paros_min']/60,1).'h',
                'Tiempo operativo'   => number_format($oee['disponibilidad']['tiempo_op_min']/60,1).'h',
                'Días hábiles'       => $oee['disponibilidad']['dias_habiles'],
            ],
        ],
        [
            'titulo' => '⚙️ Rendimiento',
            'pct'    => $oee['rendimiento']['pct'],
            'color'  => $oee['rendimiento']['pct'] >= 90 ? '#15803d'
                      : ($oee['rendimiento']['pct'] >= 75 ? '#d97706' : '#dc2626'),
            'detalles' => [
                'Und. teóricas' => number_format($oee['rendimiento']['unidades_teorico']),
                'Und. reales'   => number_format($oee['rendimiento']['unidades_reales']),
                'Diferencia'    => ($oee['rendimiento']['diferencia'] >= 0 ? '+' : '')
                                   .number_format($oee['rendimiento']['diferencia']).' und',
            ],
        ],
        [
            'titulo' => '✅ Calidad',
            'pct'    => $oee['calidad']['pct'],
            'color'  => $oee['calidad']['pct'] >= 95 ? '#15803d'
                      : ($oee['calidad']['pct'] >= 85 ? '#d97706' : '#dc2626'),
            'detalles' => [
                'Und. buenas (lib.)'=> number_format($oee['calidad']['unidades_buenas']),
                'Und. total prod.'  => number_format($oee['calidad']['unidades_total']),
                'Und. con problemas'=> number_format($oee['calidad']['unidades_malas']),
            ],
        ],
    ];
    foreach ($comps as $c):
    ?>
    <div class="comp-box"
         style="border-color:<?= $c['color'] ?>">
        <div class="comp-lbl"><?= $c['titulo'] ?></div>
        <div class="comp-val" style="color:<?= $c['color'] ?>">
            <?= number_format($c['pct'],1) ?>%
        </div>
        <div class="barra-cont">
            <?php $barComp = 'height:100%;width:'.min(100,$c['pct']).'%;'
                .'background:'.$c['color'].';border-radius:4px'; ?>
            <div class="barra-fill" style="<?= $barComp ?>"></div>
        </div>
        <div class="comp-det">
            <?php foreach ($c['detalles'] as $lbl => $val): ?>
            <?= $lbl ?>: <strong><?= $val ?></strong><br>
            <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>
</div>

<!-- KPIs por producto -->
<?php if (!empty($kpisProducto)): ?>
<div style="font-size:11px;font-weight:700;margin-bottom:6px;
            text-transform:uppercase;color:#1e3a5f">
    KPIs por Producto
</div>
<table class="tabla-reporte" style="margin-bottom:14px">
    <thead>
        <tr>
            <th>Producto</th>
            <th style="text-align:center">Lotes</th>
            <th style="text-align:right">Und. reales</th>
            <th style="text-align:center">Rend. prom.</th>
            <th style="text-align:right">Merma (kg)</th>
            <th style="text-align:center">Liberados</th>
            <th style="text-align:center">Cuarentena</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($kpisProducto as $k):
        $rend = (float)($k['rend_promedio'] ?? 0);
        $colorR = $rend >= 95 ? '#15803d'
                : ($rend >= 85 ? '#d97706' : '#dc2626');
    ?>
    <tr>
        <td>
            <strong><?= htmlspecialchars($k['producto_nombre']) ?></strong>
            <div style="font-size:8px;color:#666">
                <?= htmlspecialchars($k['producto_codigo']) ?>
            </div>
        </td>
        <td style="text-align:center"><?= (int)$k['total_lotes'] ?></td>
        <td style="text-align:right;font-weight:700">
            <?= number_format((int)$k['und_reales']) ?>
        </td>
        <td style="text-align:center;font-weight:700;color:<?= $colorR ?>">
            <?= number_format($rend,1) ?>%
        </td>
        <td style="text-align:right;color:#dc2626">
            <?= number_format((float)($k['merma_total_kg'] ?? 0),3) ?>
        </td>
        <td style="text-align:center;color:#15803d;font-weight:700">
            <?= (int)($k['liberados'] ?? 0) ?>
        </td>
        <td style="text-align:center;color:#d97706;font-weight:700">
            <?= (int)($k['cuarentena'] ?? 0) ?>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<!-- Mermas por tipo -->
<?php
$totalMerma = array_sum($mermas);
if ($totalMerma > 0):
?>
<div style="font-size:11px;font-weight:700;margin-bottom:6px;
            text-transform:uppercase;color:#1e3a5f">
    Mermas por tipo — Total: <?= number_format($totalMerma,3) ?> kg
</div>
<table class="tabla-reporte">
    <thead>
        <tr>
            <?php foreach (array_keys($mermas) as $tipo): ?>
            <th style="text-align:center"><?= $tipo ?></th>
            <?php endforeach ?>
            <th style="text-align:center">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <?php foreach ($mermas as $val): ?>
            <td style="text-align:center;font-weight:600">
                <?= number_format($val,3) ?> kg
            </td>
            <?php endforeach ?>
            <td style="text-align:center;font-weight:900;color:#dc2626">
                <?= number_format($totalMerma,3) ?> kg
            </td>
        </tr>
    </tbody>
</table>
<?php endif ?>

<!-- Firmas -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;
            gap:20px;margin-top:24px">
    <?php foreach ([
        'Encargado Desayuno Escolar',
        'Supervisor de Calidad',
        'Gerente General',
    ] as $cargo): ?>
    <div style="text-align:center;border-top:1px solid #000;padding-top:4px">
        <div style="height:30px"></div>
        <strong style="font-size:9px"><?= $cargo ?></strong>
    </div>
    <?php endforeach ?>
</div>

<?php include __DIR__ . '/_pie_reporte.php'; ?>
</body>
</html>