<?php
/** @var array $lote @var array $consumoMP @var array $estados */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Lote <?= htmlspecialchars($lote['codigo_lote']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #000;
    background: #fff;
    padding: 20px;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.empresa-nombre {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
}
.empresa-sub { font-size: 10px; color: #555; margin-top: 2px; }
.titulo-doc {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
}
.seccion {
    margin-bottom: 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
}
.seccion-titulo {
    background: #1e3a5f;
    color: #fff;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.seccion-body { padding: 8px 10px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
.grid4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px; }
.campo { margin-bottom: 5px; }
.campo-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #555;
    letter-spacing: .04em;
}
.campo-valor {
    font-size: 11px;
    border-bottom: 1px solid #999;
    min-height: 16px;
    padding: 1px 2px;
    font-weight: 500;
}
table.det {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}
table.det th {
    background: #f0f0f0;
    padding: 4px 6px;
    text-align: left;
    border: 1px solid #ccc;
    font-size: 10px;
}
table.det td {
    padding: 4px 6px;
    border: 1px solid #ccc;
}
table.det td.right { text-align: right; font-weight: 600; }
table.det td.center { text-align: center; }
.rend-box {
    border: 2px solid #1e3a5f;
    border-radius: 6px;
    padding: 10px;
    text-align: center;
    margin: 8px 0;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 8px;
}
.rend-item { text-align: center; }
.rend-val {
    font-size: 20px;
    font-weight: 900;
    color: #1e3a5f;
}
.rend-lbl {
    font-size: 9px;
    text-transform: uppercase;
    color: #666;
}
.estado-box {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.no-print { display: block; }
@media print {
    .no-print { display: none !important; }
    body { padding: 10px; }
}
</style>
</head>
<body>

<!-- Botones -->
<div class="no-print" style="text-align:right;margin-bottom:12px">
    <button onclick="window.print()"
            style="background:#1e3a5f;color:#fff;padding:8px 20px;
                border:none;border-radius:6px;cursor:pointer;font-size:13px">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#374151;padding:8px 16px;
                border:1px solid #e2e8f0;border-radius:6px;
                cursor:pointer;font-size:13px;margin-left:8px">
        ✕ Cerrar
    </button>
</div>

<!-- Encabezado -->
<div class="header">
    <div>
        <div class="empresa-nombre">Industrias Alimenticias Gustossi SRL</div>
        <div class="empresa-sub">Programa Municipal del Desayuno Escolar — GAMLP</div>
        <div class="empresa-sub">La Paz, Bolivia</div>
    </div>
    <div style="text-align:center">
        <div class="titulo-doc">Seguimiento de Producción</div>
        <div class="empresa-sub" style="margin-top:4px">
            Registro de Lote — Control de Rendimiento
        </div>
    </div>
    <div style="text-align:right;font-size:11px">
        <div>
            <strong>Lote:</strong>
            <span style="font-size:16px;font-weight:900;font-family:monospace">
                <?= htmlspecialchars($lote['codigo_lote']) ?>
            </span>
        </div>
        <div>
            <strong>Fecha:</strong>
            <?= date('d/m/Y', strtotime($lote['fecha_produccion'])) ?>
        </div>
        <div>
            <strong>Estado:</strong>
            <?= $estados[$lote['estado']]['label'] ?? $lote['estado'] ?>
        </div>
    </div>
</div>

<!-- Datos del lote -->
<div class="seccion">
    <div class="seccion-titulo">1. Datos del lote</div>
    <div class="seccion-body">
        <div class="grid3" style="margin-bottom:8px">
            <div class="campo">
                <div class="campo-label">Producto</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['producto_nombre']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Línea de producción</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['linea_nombre']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Receta utilizada</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['receta_nombre']
                        .' v'.$lote['receta_version']) ?>
                </div>
            </div>
        </div>
        <div class="grid4">
            <div class="campo">
                <div class="campo-label">Fecha de producción</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($lote['fecha_produccion'])) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Turno</div>
                <div class="campo-valor">
                    <?= ucfirst($lote['turno']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">N° de recetas</div>
                <div class="campo-valor">
                    <?= number_format((float)$lote['numero_recetas'],1) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Nivel / Destino</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['nivel'] ?? '—') ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Fecha elaboración</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($lote['fecha_elaboracion'])) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Fecha vencimiento</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($lote['fecha_vencimiento'])) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Supervisor</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['supervisor_nombre']
                        .' '.$lote['supervisor_apellidos']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Código de producto</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($lote['producto_codigo']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rendimiento -->
<div class="seccion">
    <div class="seccion-titulo">2. Rendimiento de producción</div>
    <div class="seccion-body">
        <div class="rend-box">
            <div class="rend-item">
                <div class="rend-val">
                    <?= number_format($lote['rendimiento_teorico_total'] ?? 0) ?>
                </div>
                <div class="rend-lbl">Teórico (und)</div>
            </div>
            <div class="rend-item">
                <div class="rend-val"
                    style="color:<?= (float)($lote['porcentaje_rendimiento'] ?? 0) >= 95
                        ? '#15803d' : ((float)($lote['porcentaje_rendimiento'] ?? 0) >= 85
                        ? '#d97706' : '#dc2626') ?>">
                    <?= number_format($lote['rendimiento_real_total'] ?? 0) ?>
                </div>
                <div class="rend-lbl">Real (und)</div>
            </div>
            <div class="rend-item">
                <?php
                $dif = (int)($lote['diferencia_unidades'] ?? 0);
                $colorDif = $dif >= 0 ? '#15803d' : '#dc2626';
                ?>
                <div class="rend-val" style="color:<?= $colorDif ?>">
                    <?= ($dif >= 0 ? '+' : '') . number_format($dif) ?>
                </div>
                <div class="rend-lbl">Diferencia (und)</div>
            </div>
            <div class="rend-item">
                <?php
                $pct = (float)($lote['porcentaje_rendimiento'] ?? 0);
                $colorPct = $pct >= 95 ? '#15803d' : ($pct >= 85 ? '#d97706' : '#dc2626');
                ?>
                <div class="rend-val" style="color:<?= $colorPct ?>">
                    <?= $pct > 0 ? number_format($pct,1).'%' : '—' ?>
                </div>
                <div class="rend-lbl">Rendimiento (%)</div>
            </div>
        </div>

        <!-- Mermas -->
        <?php
        $mermas = [];
        foreach (\App\Models\LoteProduccion::MERMAS as $campo => $info) {
            $mermas[$info['label']] = (float)($lote[$campo] ?? 0);
        }
        $totalMerma = array_sum($mermas);
        if ($totalMerma > 0):
        ?>
        <div style="margin-top:8px">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;
                        color:#666;margin-bottom:4px">
                Desglose de mermas
            </div>
            <table class="det">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="right">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mermas as $label => $val): ?>
                    <?php if ($val <= 0) continue; ?>
                    <tr>
                        <td><?= $label ?></td>
                        <td class="right"><?= number_format($val,3) ?> kg</td>
                    </tr>
                    <?php endforeach ?>
                    <tr>
                        <td style="font-weight:700">TOTAL</td>
                        <td class="right" style="color:#dc2626;font-weight:700">
                            <?= number_format($totalMerma,3) ?> kg
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- MP Consumida -->
<?php if (!empty($consumoMP)): ?>
<div class="seccion">
    <div class="seccion-titulo">3. Materia prima consumida</div>
    <div class="seccion-body">
        <table class="det">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th>Lote proveedor</th>
                    <th>Vencimiento stock</th>
                    <th class="right">Cantidad usada</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($consumoMP as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['insumo_codigo']) ?></td>
                <td><?= htmlspecialchars($c['insumo_nombre']) ?></td>
                <td><?= htmlspecialchars($c['stock_lote']) ?></td>
                <td class="center">
                    <?= date('d/m/Y', strtotime($c['fecha_vencimiento'])) ?>
                </td>
                <td class="right">
                    <?= number_format((float)$c['cantidad_usada'],3) ?>
                    <?= htmlspecialchars($c['unidad_medida']) ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Firmas -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;
            gap:20px;margin-top:24px">
    <div style="text-align:center;border-top:1px solid #000;padding-top:4px">
        <div style="height:36px"></div>
        <strong>Jefe de Producción</strong>
    </div>
    <div style="text-align:center;border-top:1px solid #000;padding-top:4px">
        <div style="height:36px"></div>
        <strong>Control de Calidad</strong>
    </div>
    <div style="text-align:center;border-top:1px solid #000;padding-top:4px">
        <div style="height:36px"></div>
        <strong>Encargado D.E.</strong>
    </div>
</div>

<div style="margin-top:16px;text-align:center;font-size:9px;
            color:#888;border-top:1px solid #ccc;padding-top:8px">
    SIACEP — Sistema Integrado de Análisis, Control y Ejecución de Producción ·
    Gustossi SRL · Generado: <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>