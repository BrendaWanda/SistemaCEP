<?php
/** @var array $datos @var array $stats @var string $fechaDesde @var string $fechaHasta */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Seguimiento Producción</title>
<style><?php include __DIR__ . '/_estilos_reporte.php'; ?></style>
</head>
<body>
<?php include __DIR__ . '/_encabezado_reporte.php'; ?>

<div class="titulo-reporte">Reporte de Seguimiento de Producción</div>
<div class="subtitulo">
    Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?>
    al <?= date('d/m/Y', strtotime($fechaHasta)) ?>
</div>

<div class="resumen-grid">
    <div class="resumen-item">
        <div class="resumen-val"><?= $stats['total_lotes'] ?></div>
        <div class="resumen-lbl">Lotes producidos</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val"><?= number_format($stats['und_reales']) ?></div>
        <div class="resumen-lbl">Und. reales</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val"><?= number_format($stats['rend_promedio'],1) ?>%</div>
        <div class="resumen-lbl">Rend. promedio</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#15803d"><?= $stats['liberados'] ?></div>
        <div class="resumen-lbl">Liberados</div>
    </div>
</div>

<table class="tabla-reporte">
    <thead>
        <tr>
            <th>Lote</th>
            <th>Producto</th>
            <th>Fecha</th>
            <th>Turno</th>
            <th style="text-align:center">Teórico</th>
            <th style="text-align:center">Real</th>
            <th style="text-align:center">Rend.</th>
            <th style="text-align:center">Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($datos as $l):
        $pct = (float)($l['porcentaje_rendimiento'] ?? 0);
        $estColor = match($l['estado']) {
            'liberado'   => '#15803d',
            'cuarentena' => '#d97706',
            'rechazado'  => '#dc2626',
            default      => '#64748b',
        };
    ?>
    <tr>
        <td style="font-family:monospace;font-weight:700">
            <?= htmlspecialchars($l['codigo_lote']) ?>
        </td>
        <td><?= htmlspecialchars($l['producto_nombre']) ?></td>
        <td><?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?></td>
        <td><?= ucfirst($l['turno']) ?></td>
        <td style="text-align:center">
            <?= number_format($l['rendimiento_teorico_total'] ?? 0) ?>
        </td>
        <td style="text-align:center;font-weight:700">
            <?= number_format($l['rendimiento_real_total'] ?? 0) ?>
        </td>
        <td style="text-align:center;font-weight:700;
                    color:<?= $pct >= 95 ? '#15803d' : ($pct >= 85 ? '#d97706' : '#dc2626') ?>">
            <?= $pct > 0 ? number_format($pct,1).'%' : '—' ?>
        </td>
        <td style="text-align:center;color:<?= $estColor ?>;font-weight:700">
            <?= strtoupper($l['estado']) ?>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?php include __DIR__ . '/_pie_reporte.php'; ?>
</body>
</html>