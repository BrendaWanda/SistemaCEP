<?php
/** @var array $sesion @var array $confOpc */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro <?= htmlspecialchars($sesion['codigo_lote']) ?></title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body {
    font-family: Arial, sans-serif;
    font-size: 10px;
    color: #000;
    background: #fff;
    padding: 16px;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #000;
    padding-bottom: 8px;
    margin-bottom: 12px;
}
.empresa { font-size:13px; font-weight:700; text-transform:uppercase; }
.sub     { font-size:9px; color:#555; margin-top:2px; }
.titulo  { font-size:12px; font-weight:700; text-transform:uppercase;
            text-align:center; }
.codigo  { font-size:10px; text-align:right; }

.seccion {
    margin-bottom:10px;
    border:1px solid #999;
    border-radius:3px;
    overflow:hidden;
}
.seccion-titulo {
    background:#1e3a5f; color:#fff;
    padding:3px 8px;
    font-size:10px; font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.seccion-body { padding:6px 8px; }

.encabezado-grid {
    display:grid;
    grid-template-columns: repeat(4,1fr);
    gap:6px;
    margin-bottom:6px;
}
.campo-label {
    font-size:8px; font-weight:700;
    text-transform:uppercase;
    color:#555; letter-spacing:.04em;
}
.campo-valor {
    font-size:10px;
    border-bottom:1px solid #999;
    min-height:14px;
    padding:1px 2px;
}

table.reg {
    width:100%;
    border-collapse:collapse;
    font-size:9px;
}
table.reg th {
    background:#f0f0f0;
    padding:3px 4px;
    border:1px solid #ccc;
    text-align:center;
    font-size:9px;
}
table.reg td {
    padding:3px 4px;
    border:1px solid #ccc;
    text-align:center;
}
table.reg td.left { text-align:left; }
table.reg tr.señal { background:#fef2f2; }

.decision-box {
    border:2px solid #000;
    border-radius:4px;
    padding:8px;
    text-align:center;
    margin:8px 0;
}
.decision-label {
    font-size:9px; font-weight:700;
    text-transform:uppercase; color:#555;
}
.decision-valor {
    font-size:16px; font-weight:900;
    text-transform:uppercase;
    margin-top:3px;
}
.decision-liberado  { border-color:#15803d; color:#15803d; }
.decision-cuarentena{ border-color:#d97706; color:#d97706; }

.firmas {
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:16px;
    margin-top:16px;
}
.firma-box {
    text-align:center;
    border-top:1px solid #000;
    padding-top:3px;
    font-size:9px;
}
.firma-linea { height:30px; }

.no-print { display:block; }
@media print {
    .no-print { display:none !important; }
    body { padding:8px; }
}
</style>
</head>
<body>

<!-- Botones -->
<div class="no-print" style="text-align:right;margin-bottom:10px">
    <button onclick="window.print()"
            style="background:#1e3a5f;color:#fff;padding:7px 18px;
                border:none;border-radius:5px;cursor:pointer;font-size:12px">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#374151;padding:7px 14px;
                border:1px solid #e2e8f0;border-radius:5px;
                cursor:pointer;font-size:12px;margin-left:6px">
        ✕ Cerrar
    </button>
</div>

<!-- Encabezado -->
<div class="header">
    <div>
        <div class="empresa">Industrias Alimenticias Gustossi SRL</div>
        <div class="sub">Programa Municipal del Desayuno Escolar — GAMLP</div>
        <div class="sub">La Paz, Bolivia</div>
    </div>
    <div style="text-align:center">
        <div class="titulo">Registro de Control de Proceso</div>
        <div class="sub" style="margin-top:3px">C-PROCESO-G · SIREMU</div>
    </div>
    <div class="codigo">
        <div>
            <strong>Lote:</strong>
            <span style="font-size:14px;font-weight:900;font-family:monospace">
                <?= htmlspecialchars($sesion['codigo_lote']) ?>
            </span>
        </div>
        <div>
            <strong>Fecha:</strong>
            <?= date('d/m/Y', strtotime($sesion['fecha'])) ?>
        </div>
        <div>
            <strong>Turno:</strong> <?= ucfirst($sesion['turno']) ?>
        </div>
    </div>
</div>

<!-- Encabezado del registro -->
<div class="seccion">
    <div class="seccion-titulo">Encabezado</div>
    <div class="seccion-body">
        <div class="encabezado-grid">
            <div>
                <div class="campo-label">Producto</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($sesion['producto_nombre']) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Código</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($sesion['producto_codigo']) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Peso nominal (g)</div>
                <div class="campo-valor">
                    <?= number_format($sesion['peso_nominal_g'],1) ?>g
                    (±<?= $sesion['tolerancia_pct'] ?>%)
                </div>
            </div>
            <div>
                <div class="campo-label">Supervisor</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($sesion['supervisor_nombre']
                        .' '.$sesion['supervisor_apellidos']) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">LSE (g)</div>
                <div class="campo-valor" style="color:#15803d;font-weight:600">
                    <?= number_format($sesion['lse_g'],3) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">LIE (g)</div>
                <div class="campo-valor" style="color:#dc2626;font-weight:600">
                    <?= number_format($sesion['lie_g'],3) ?>
                </div>
            </div>
            <div>
                <div class="campo-label">Nivel / Destino</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($sesion['nivel'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Amasado -->
<?php if (!empty($sesion['amasados'])): ?>
<div class="seccion">
    <div class="seccion-titulo">1. Control de amasado</div>
    <div class="seccion-body">
        <table class="reg">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>T° masa (°C)</th>
                    <th>pH masa</th>
                    <th class="left">Observaciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['amasados'] as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['hora']) ?></td>
                <td>
                    <?= $a['temperatura_masa_c'] !== null
                        ? number_format($a['temperatura_masa_c'],1).'°C'
                        : '—' ?>
                </td>
                <td>
                    <?= $a['ph_masa'] !== null
                        ? number_format($a['ph_masa'],2) : '—' ?>
                </td>
                <td class="left">
                    <?= htmlspecialchars($a['observaciones_masa'] ?? '') ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Pesos masa cruda -->
<?php if (!empty($sesion['pesos'])): ?>
<div class="seccion">
    <div class="seccion-titulo">
        2. Control de pesos — Masa cruda
        (LSE: <?= number_format($sesion['lse_g'],3) ?>g /
        LIE: <?= number_format($sesion['lie_g'],3) ?>g)
    </div>
    <div class="seccion-body">
        <table class="reg">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>P1</th><th>P2</th><th>P3</th><th>P4</th><th>P5</th>
                    <th>P6</th><th>P7</th><th>P8</th><th>P9</th><th>P10</th>
                    <th style="background:#dbeafe">X̄</th>
                    <th style="background:#ede9fe">R</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['pesos'] as $p): ?>
            <tr class="<?= $p['fuera_de_control'] ? 'señal' : '' ?>">
                <td><?= htmlspecialchars($p['hora']) ?></td>
                <?php for ($i = 1; $i <= 10; $i++):
                    $k = 'peso_'.str_pad($i,2,'0',STR_PAD_LEFT);
                    $v = $p[$k];
                    $fuera = $v !== null
                        && ((float)$v > (float)$sesion['lse_g']
                            || (float)$v < (float)$sesion['lie_g']);
                ?>
                <td style="<?= $fuera
                    ? 'color:#dc2626;font-weight:700' : '' ?>">
                    <?= $v !== null ? number_format((float)$v,1) : '—' ?>
                </td>
                <?php endfor ?>
                <td style="background:#dbeafe;font-weight:700">
                    <?= $p['promedio_xbar'] !== null
                        ? number_format((float)$p['promedio_xbar'],2) : '—' ?>
                </td>
                <td style="background:#ede9fe;font-weight:700">
                    <?= $p['rango_r'] !== null
                        ? number_format((float)$p['rango_r'],2) : '—' ?>
                </td>
                <td style="<?= $p['fuera_de_control']
                    ? 'color:#dc2626;font-weight:700' : '' ?>">
                    <?= $p['fuera_de_control'] ? '⚠️ SEÑAL' : '✓' ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Horneado -->
<?php if (!empty($sesion['horneados'])): ?>
<div class="seccion">
    <div class="seccion-titulo">3. Control de horneado</div>
    <div class="seccion-body">
        <table class="reg">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Fermentación (min)</th>
                    <th>T° horno (°C)</th>
                    <th>Tiempo horno (min)</th>
                    <th>Color</th>
                    <th>Olor</th>
                    <th>Textura</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['horneados'] as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['hora']) ?></td>
                <td><?= $h['tiempo_fermentacion_min'] ?? '—' ?></td>
                <td>
                    <?= $h['temperatura_horno_c'] !== null
                        ? number_format($h['temperatura_horno_c'],1).'°C'
                        : '—' ?>
                </td>
                <td><?= $h['tiempo_horneado_min'] ?? '—' ?></td>
                <?php foreach (['conf_color','conf_olor','conf_textura'] as $c):
                    $cv = $confOpc[$h[$c] ?? ''] ?? null;
                ?>
                <td><?= $cv ? $cv['label'] : '—' ?></td>
                <?php endforeach ?>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Envasado -->
<?php if (!empty($sesion['envasados'])): ?>
<div class="seccion">
    <div class="seccion-titulo">4. Control de envasado</div>
    <div class="seccion-body">
        <table class="reg">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>T° prod (°C)</th>
                    <th>P1(g)</th><th>P2(g)</th>
                    <th>P3(g)</th><th>P4(g)</th>
                    <th>X̄</th>
                    <th>Codif H</th><th>Codif V</th>
                    <th>Sell H</th><th>Sell V</th>
                    <th>Total und</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['envasados'] as $e): ?>
            <tr class="<?= $e['fuera_especificacion'] ? 'señal' : '' ?>">
                <td><?= htmlspecialchars($e['hora']) ?></td>
                <td>
                    <?= $e['temperatura_producto_c'] !== null
                        ? number_format($e['temperatura_producto_c'],1) : '—' ?>
                </td>
                <?php for ($i = 1; $i <= 4; $i++):
                    $k = "peso_unidad_{$i}";
                ?>
                <td>
                    <?= $e[$k] !== null
                        ? number_format((float)$e[$k],1) : '—' ?>
                </td>
                <?php endfor ?>
                <td style="font-weight:700">
                    <?= $e['promedio_peso_unidad'] !== null
                        ? number_format((float)$e['promedio_peso_unidad'],2)
                        : '—' ?>
                </td>
                <?php foreach ([
                    'codif_horizontal','codif_vertical',
                    'sellado_horizontal','sellado_vertical'
                ] as $c):
                    $cv = $confOpc[$e[$c] ?? ''] ?? null;
                ?>
                <td><?= $cv ? $cv['label'] : '—' ?></td>
                <?php endforeach ?>
                <td><?= $e['total_unidades'] ?? '—' ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- Liberación PT -->
<?php if ($sesion['liberacion']): ?>
<?php $lib = $sesion['liberacion']; ?>
<div class="seccion">
    <div class="seccion-titulo">5. Análisis y liberación de producto terminado</div>
    <div class="seccion-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;
                    margin-bottom:8px">
            <table class="reg">
                <thead>
                    <tr>
                        <th class="left">Verificación envase/codificado</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $checkRows = [
                    'Codif. lote legible'  => $lib['codif_lote_legible'],
                    'Codif. lote correcto' => $lib['codif_lote_correcto'],
                    'F.venc. legible'      => $lib['codif_fvenc_legible'],
                    'F.venc. correcta'     => $lib['codif_fvenc_correcto'],
                    'Envase primario'      => $lib['envase_primario'],
                    'Envase secundario'    => $lib['envase_secundario'],
                    'Inocuidad'           => $lib['inocuo'],
                ];
                foreach ($checkRows as $label => $val):
                    $cv = $confOpc[$val ?? ''] ?? null;
                ?>
                <tr>
                    <td class="left"><?= $label ?></td>
                    <td><?= $cv ? $cv['label'] : '—' ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <table class="reg">
                <thead>
                    <tr>
                        <th class="left">Organolépticos PT</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $orgRows = [
                    'Color'     => $lib['conf_color'],
                    'Olor'      => $lib['conf_olor'],
                    'Sabor'     => $lib['conf_sabor'],
                    'Textura'   => $lib['conf_textura'],
                    'Apariencia'=> $lib['conf_apariencia'],
                ];
                if ($lib['resultado_humedad_pct']) {
                    $orgRows['Humedad (%)'] = null;
                }
                foreach ($orgRows as $label => $val):
                    $cv = $confOpc[$val ?? ''] ?? null;
                ?>
                <tr>
                    <td class="left"><?= $label ?></td>
                    <td>
                        <?php if ($label === 'Humedad (%)'): ?>
                        <?= number_format($lib['resultado_humedad_pct'],2) ?>%
                        <?php elseif ($label === 'pH'): ?>
                        <?= number_format($lib['resultado_ph'],2) ?>
                        <?php else: ?>
                        <?= $cv ? $cv['label'] : '—' ?>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Decisión final -->
        <div class="decision-box decision-<?= $lib['decision_final'] ?>">
            <div class="decision-label">Decisión final</div>
            <div class="decision-valor">
                <?= $lib['decision_final'] === 'liberado'
                    ? '✅ PRODUCTO LIBERADO'
                    : '⚠️ PRODUCTO EN CUARENTENA' ?>
            </div>
        </div>

        <?php if ($lib['observaciones']): ?>
        <div style="margin-top:6px;font-size:9px">
            <strong>Observaciones:</strong>
            <?= htmlspecialchars($lib['observaciones']) ?>
        </div>
        <?php endif ?>
    </div>
</div>
<?php endif ?>

<!-- Firmas -->
<div class="firmas">
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Supervisor de Calidad</strong><br>
        <?= htmlspecialchars($sesion['supervisor_nombre']
            .' '.$sesion['supervisor_apellidos']) ?>
    </div>
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Jefe de Producción</strong>
    </div>
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Encargado D.E.</strong>
    </div>
</div>

<div style="margin-top:14px;text-align:center;font-size:8px;
            color:#888;border-top:1px solid #ccc;padding-top:6px">
    SIACEP — Sistema Integrado de Análisis, Control y Ejecución de Producción ·
    Gustossi SRL · Generado: <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>