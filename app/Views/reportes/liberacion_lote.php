<?php
/** @var array $datos @var array $stats @var string $fechaDesde @var string $fechaHasta */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Liberación PT</title>
<style><?php include __DIR__ . '/_estilos_reporte.php'; ?></style>
</head>
<body>
<?php include __DIR__ . '/_encabezado_reporte.php'; ?>

<div class="titulo-reporte">Reporte de Liberación de Producto Terminado</div>
<div class="subtitulo">
    Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?>
    al <?= date('d/m/Y', strtotime($fechaHasta)) ?>
</div>

<div class="resumen-grid">
    <div class="resumen-item">
        <div class="resumen-val"><?= $stats['total'] ?></div>
        <div class="resumen-lbl">Total registros</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#15803d"><?= $stats['liberados'] ?></div>
        <div class="resumen-lbl">Liberados</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#d97706"><?= $stats['cuarentena'] ?></div>
        <div class="resumen-lbl">En cuarentena</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val">
            <?= $stats['total'] > 0
                ? number_format(($stats['liberados']/$stats['total'])*100,1).'%'
                : '—' ?>
        </div>
        <div class="resumen-lbl">% Liberados</div>
    </div>
</div>

<table class="tabla-reporte">
    <thead>
        <tr>
            <th>Lote</th>
            <th>Producto</th>
            <th>Fecha prod.</th>
            <th style="text-align:center">Und. prod.</th>
            <th>Supervisor calidad</th>
            <th style="text-align:center">Decisión</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($datos as $d):
        $decColor = $d['decision_final'] === 'liberado' ? '#15803d' : '#d97706';
    ?>
    <tr>
        <td style="font-family:monospace;font-weight:700">
            <?= htmlspecialchars($d['codigo_lote']) ?>
        </td>
        <td><?= htmlspecialchars($d['producto_nombre']) ?></td>
        <td><?= date('d/m/Y', strtotime($d['fecha_produccion'])) ?></td>
        <td style="text-align:center;font-weight:600">
            <?= number_format($d['rendimiento_real_total'] ?? 0) ?>
        </td>
        <td>
            <?= htmlspecialchars($d['supervisor_cal_nombre']
                .' '.$d['supervisor_cal_apellidos']) ?>
        </td>
        <td style="text-align:center;color:<?= $decColor ?>;font-weight:700">
            <?= strtoupper($d['decision_final']) ?>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?php include __DIR__ . '/_pie_reporte.php'; ?>
</body>
</html>