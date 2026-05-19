<?php
/** @var array $datos @var array $stats @var string $fechaDesde @var string $fechaHasta */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Recepción MP</title>
<style>
<?php include __DIR__ . '/_estilos_reporte.php'; ?>
</style>
</head>
<body>
<?php include __DIR__ . '/_encabezado_reporte.php'; ?>

<div class="titulo-reporte">Reporte de Recepción de Materia Prima</div>
<div class="subtitulo">
    Período: <?= date('d/m/Y', strtotime($fechaDesde)) ?>
    al <?= date('d/m/Y', strtotime($fechaHasta)) ?>
</div>

<!-- Resumen -->
<div class="resumen-grid">
    <div class="resumen-item">
        <div class="resumen-val"><?= $stats['total'] ?></div>
        <div class="resumen-lbl">Total recepciones</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#15803d"><?= $stats['aprobadas'] ?></div>
        <div class="resumen-lbl">Aprobadas</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#dc2626"><?= $stats['rechazadas'] ?></div>
        <div class="resumen-lbl">Rechazadas</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#d97706"><?= $stats['observadas'] ?></div>
        <div class="resumen-lbl">Observadas</div>
    </div>
</div>

<table class="tabla-reporte">
    <thead>
        <tr>
            <th>Código</th>
            <th>Insumo</th>
            <th>Proveedor</th>
            <th>Lote prov.</th>
            <th>F. análisis</th>
            <th>F. venc.</th>
            <th>Cantidad</th>
            <th>Decisión</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($datos as $r):
        $decColor = match($r['decision']) {
            'aprobado'  => '#15803d',
            'rechazado' => '#dc2626',
            'observado' => '#d97706',
            default     => '#64748b',
        };
    ?>
    <tr>
        <td><?= htmlspecialchars($r['codigo_recepcion']) ?></td>
        <td>
            <strong><?= htmlspecialchars($r['insumo_nombre']) ?></strong>
            <div style="font-size:8px;color:#666">
                <?= htmlspecialchars($r['insumo_codigo']) ?>
            </div>
        </td>
        <td><?= htmlspecialchars($r['proveedor_nombre']) ?></td>
        <td><?= htmlspecialchars($r['lote_proveedor']) ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha_analisis'])) ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha_vencimiento'])) ?></td>
        <td style="text-align:right">
            <?= number_format($r['cantidad_recibida'],2) ?>
            <?= htmlspecialchars($r['unidad_medida']) ?>
        </td>
        <td style="color:<?= $decColor ?>;font-weight:700;text-align:center">
            <?= strtoupper($r['decision']) ?>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?php include __DIR__ . '/_pie_reporte.php'; ?>
</body>
</html>