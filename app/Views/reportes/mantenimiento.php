<?php
/** @var array $datos @var array $stats @var string $fechaDesde @var string $fechaHasta */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Mantenimiento</title>
<style><?php include __DIR__ . '/_estilos_reporte.php'; ?></style>
</head>
<body>
<?php include __DIR__ . '/_encabezado_reporte.php'; ?>

<div class="titulo-reporte">Reporte de Mantenimiento</div>
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
        <div class="resumen-val" style="color:#1d4ed8"><?= $stats['preventivos'] ?></div>
        <div class="resumen-lbl">Preventivos</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#dc2626"><?= $stats['correctivos'] ?></div>
        <div class="resumen-lbl">Correctivos</div>
    </div>
    <div class="resumen-item">
        <div class="resumen-val" style="color:#dc2626">
            <?= number_format($stats['horas_paro'],1) ?>h
        </div>
        <div class="resumen-lbl">Horas de paro</div>
    </div>
</div>

<table class="tabla-reporte">
    <thead>
        <tr>
            <th>Equipo</th>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Fecha inicio</th>
            <th style="text-align:center">Duración</th>
            <th style="text-align:center">Paro prod.</th>
            <th>Ejecutado por</th>
            <th style="text-align:center">Resultado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($datos as $m):
        $durMin = (int)($m['duracion_min'] ?? 0);
        $durFmt = $durMin >= 60
            ? floor($durMin/60).'h '.($durMin%60).'min'
            : $durMin.'min';
        $tipoColor = match($m['tipo']) {
            'preventivo'  => '#1d4ed8',
            'correctivo'  => '#dc2626',
            'calibracion' => '#7c3aed',
            default       => '#64748b',
        };
    ?>
    <tr>
        <td>
            <strong><?= htmlspecialchars($m['equipo_nombre']) ?></strong>
            <div style="font-size:8px;color:#666">
                <?= htmlspecialchars($m['equipo_codigo']) ?>
            </div>
        </td>
        <td style="color:<?= $tipoColor ?>;font-weight:700">
            <?= ucfirst($m['tipo']) ?>
        </td>
        <td style="font-size:8px">
            <?= htmlspecialchars(mb_strimwidth($m['descripcion_trabajo'] ?? '', 0, 50, '...')) ?>
        </td>
        <td>
            <?= $m['fecha_inicio']
                ? date('d/m/Y', strtotime($m['fecha_inicio'])) : '—' ?>
        </td>
        <td style="text-align:center"><?= $durMin > 0 ? $durFmt : '—' ?></td>
        <td style="text-align:center;color:<?= $m['paro_produccion'] ? '#dc2626' : '#15803d' ?>">
            <?= $m['paro_produccion']
                ? '⚠️ '.($m['tiempo_paro_min'] ?? 0).'min' : 'No' ?>
        </td>
        <td><?= htmlspecialchars($m['ejecutado_nombre'] ?? '—') ?></td>
        <td style="text-align:center"><?= ucfirst($m['resultado']) ?></td>
    </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?php include __DIR__ . '/_pie_reporte.php'; ?>
</body>
</html>