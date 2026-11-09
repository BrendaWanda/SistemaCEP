<?php
/** @var array $datos @var string $codigo */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Trazabilidad Lote <?= htmlspecialchars($codigo) ?></title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:Arial,sans-serif; font-size:10px; color:#000; padding:16px; }
.header {
    display:flex; justify-content:space-between;
    border-bottom:2px solid #000; padding-bottom:8px; margin-bottom:12px;
}
.empresa { font-size:13px; font-weight:700; text-transform:uppercase; }
.sub     { font-size:9px; color:#555; margin-top:2px; }
.titulo  { font-size:12px; font-weight:700; text-align:center; }
.seccion {
    margin-bottom:10px; border:1px solid #999;
    border-radius:3px; overflow:hidden;
}
.seccion-titulo {
    padding:3px 8px; font-size:10px; font-weight:700;
    text-transform:uppercase; color:#fff;
}
.seccion-body { padding:6px 8px; }
table.t { width:100%; border-collapse:collapse; font-size:9px; }
table.t th {
    background:#f0f0f0; padding:3px 5px;
    border:1px solid #ccc; text-align:left;
}
table.t td { padding:3px 5px; border:1px solid #ccc; }
table.t td.right { text-align:right; font-weight:600; }
table.t td.center { text-align:center; }
.grid2 { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
.campo-label { font-size:8px; font-weight:700; color:#555; text-transform:uppercase; }
.campo-valor { font-size:10px; border-bottom:1px solid #999; min-height:14px; }
.estado-box {
    border:2px solid; border-radius:4px; padding:6px;
    text-align:center; margin:8px 0;
    font-size:14px; font-weight:900; text-transform:uppercase;
}
.no-print { display:block; }
@media print { .no-print { display:none !important; } body { padding:8px; } }
</style>
</head>
<body>

<div class="no-print" style="text-align:right;margin-bottom:10px">
    <button onclick="window.print()"
            style="background:#1e3a5f;color:#fff;padding:7px 18px;
                    border:none;border-radius:5px;cursor:pointer">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#374151;padding:7px 14px;
                    border:1px solid #e2e8f0;border-radius:5px;
                    cursor:pointer;margin-left:6px">
        ✕ Cerrar
    </button>
</div>

<div class="header">
    <div>
        <div class="empresa">Industrias Alimenticias Gustossi SRL</div>
        <div class="sub">Programa Municipal del Desayuno Escolar — GAMLP</div>
    </div>
    <div style="text-align:center">
        <div class="titulo">Reporte de Trazabilidad</div>
        <div class="sub">SIREMU — Control de Calidad</div>
    </div>
    <div style="text-align:right;font-size:10px">
        <div>
            <strong>Lote:</strong>
            <span style="font-size:14px;font-weight:900;font-family:monospace">
                <?= htmlspecialchars($codigo) ?>
            </span>
        </div>
        <div><strong>Generado:</strong> <?= date('d/m/Y H:i') ?></div>
    </div>
</div>

<!-- Producto terminado -->
<div class="seccion">
    <div class="seccion-titulo" style="background:#1e3a5f">
        Nivel 1 — Producto Terminado
    </div>
    <div class="seccion-body">
        <div class="grid2">
            <div>
                <div class="campo-label">Lote</div>
                <div class="campo-valor" style="font-family:monospace;font-weight:900;font-size:13px">
                    <?= htmlspecialchars($datos['codigo_lote']) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Producto</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($datos['producto_nombre']) ?>
                    (<?= htmlspecialchars($datos['producto_codigo']) ?>)
                </div>
            </div>
            <div>
                <div class="campo-label">Fecha producción</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($datos['fecha_produccion'])) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Estado</div>
                <div class="campo-valor" style="font-weight:700">
                    <?= strtoupper($datos['estado']) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Rendimiento real / teórico</div>
                <div class="campo-valor">
                    <?= number_format($datos['rendimiento_real_total'] ?? 0) ?> /
                    <?= number_format($datos['rendimiento_teorico_total'] ?? 0) ?> und
                    (<?= number_format($datos['porcentaje_rendimiento'] ?? 0, 1) ?>%)
                </div>
            </div>
            <div>
                <div class="campo-label">F. vencimiento</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($datos['fecha_vencimiento'])) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MP consumida -->
<?php if (!empty($datos['mp_consumida'])): ?>
<div class="seccion">
    <div class="seccion-titulo" style="background:#0891b2">
        Nivel 2 — Materia Prima consumida
    </div>
    <div class="seccion-body">
        <table class="t">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Lote proveedor</th>
                    <th>Proveedor</th>
                    <th>F. venc. stock</th>
                    <th class="right">Cantidad</th>
                    <th>Recepción</th>
                    <th class="center">Decisión</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($datos['mp_consumida'] as $mp): ?>
            <tr>
                <td><strong><?= htmlspecialchars($mp['insumo_codigo']) ?></strong>
                    — <?= htmlspecialchars($mp['insumo_nombre']) ?></td>
                <td><?= htmlspecialchars($mp['lote_proveedor']) ?></td>
                <td><?= htmlspecialchars($mp['proveedor_nombre']) ?></td>
                <td class="center">
                    <?= date('d/m/Y', strtotime($mp['fecha_vencimiento'])) ?>
                </td>
                <td class="right">
                    <?= number_format((float)$mp['cantidad_usada'],3) ?>
                    <?= htmlspecialchars($mp['unidad_medida']) ?>
                </td>
                <td><?= htmlspecialchars($mp['codigo_recepcion']) ?></td>
                <td class="center">
                    <?= strtoupper($mp['decision_recepcion']) ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Liberación -->
<?php if ($datos['liberacion']): ?>
<?php $lib = $datos['liberacion']; ?>
<div class="seccion">
    <div class="seccion-titulo" style="background:#7c3aed">
        Nivel 4 — Liberación de Producto Terminado
    </div>
    <div class="seccion-body">
        <div class="estado-box"
                style="border-color:<?= $lib['decision_final']==='liberado'
                    ? '#15803d' : '#d97706' ?>;
                    color:<?= $lib['decision_final']==='liberado'
                    ? '#15803d' : '#d97706' ?>">
            <?= $lib['decision_final'] === 'liberado'
                ? 'PRODUCTO LIBERADO' : 'PRODUCTO EN CUARENTENA' ?>
        </div>
        <div class="grid2">
            <div>
                <div class="campo-label">Supervisor calidad</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lib['supervisor_cal_nombre']
                        .' '.($lib['supervisor_cal_apellidos'] ?? '')) ?>
                </div>
            </div>
            <?php if ($lib['resultado_humedad_pct']): ?>
            <div>
                <div class="campo-label">Humedad PT (%)</div>
                <div class="campo-valor">
                    <?= number_format($lib['resultado_humedad_pct'],2) ?>%
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?php endif ?>

<!-- Firmas -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;
            gap:20px;margin-top:20px">
    <?php foreach ([
        'Encargado Desayuno Escolar',
        'Supervisor de Calidad',
        'Jefe de Producción'
    ] as $cargo): ?>
    <div style="text-align:center;border-top:1px solid #000;padding-top:3px">
        <div style="height:30px"></div>
        <strong><?= $cargo ?></strong>
    </div>
    <?php endforeach ?>
</div>

<div style="margin-top:14px;text-align:center;font-size:8px;
            color:#888;border-top:1px solid #ccc;padding-top:6px">
    SIACEP — Gustossi SRL · Generado: <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>