<?php
/** @var array $sesion @var array $etapas
 *  @var array $parametros_por_etapa @var array $valores_por_etapa
 *  @var array $subgrupos_por_parametro @var array $parametros_atributo
 *  @var array $inspecciones_atributos */

$inspeccionPorParametro = [];
foreach ($inspecciones_atributos as $ia) {
    $inspeccionPorParametro[(int)$ia['parametro_id']] = $ia;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro <?= htmlspecialchars($sesion['codigo_lote']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
    margin-bottom:6px;
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

.subseccion-titulo {
    font-size:9px; font-weight:700; color:#1e3a5f;
    text-transform:uppercase;
    margin:4px 0 3px;
}

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
        <i class="bi bi-printer"></i> Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#374151;padding:7px 14px;
                border:1px solid #e2e8f0;border-radius:5px;
                cursor:pointer;font-size:12px;margin-left:6px">
        <i class="bi bi-x-lg"></i> Cerrar
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

<?php
$numSeccion = 0;
foreach ($etapas as $etapaKey => $etapaLabel):
    $params = $parametros_por_etapa[$etapaKey] ?? [];
    if (empty($params)) continue;

    $numSeccion++;

    $noSpc   = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 0));
    $spcNum  = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 1 && $p['tipo_dato'] === 'numerico'));
    $spcAttr = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 1 && in_array($p['tipo_dato'], ['seleccion','si_no'], true)));

    $valoresEtapa = $valores_por_etapa[$etapaKey] ?? [];

    $porHora = [];
    foreach ($valoresEtapa as $v) {
        $porHora[$v['hora']][(int)$v['parametro_id']] = $v['valor'];
    }
    ksort($porHora);
?>
<div class="seccion">
    <div class="seccion-titulo"><?= $numSeccion ?>. <?= htmlspecialchars($etapaLabel) ?></div>
    <div class="seccion-body">

        <!-- Parámetros simples -->
        <?php if (!empty($noSpc)): ?>
            <?php if (!empty($porHora)): ?>
            <table class="reg">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <?php foreach ($noSpc as $p): ?>
                        <th>
                            <?= htmlspecialchars($p['nombre']) ?>
                            <?= $p['unidad'] ? ' ('.htmlspecialchars($p['unidad']).')' : '' ?>
                        </th>
                        <?php endforeach ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($porHora as $hora => $valoresFila): ?>
                <tr>
                    <td><?= htmlspecialchars($hora) ?></td>
                    <?php foreach ($noSpc as $p):
                        $val = $valoresFila[(int)$p['id']] ?? null;
                    ?>
                    <td>
                        <?php if ($val === null): ?>—
                        <?php elseif ($p['tipo_dato'] === 'si_no'): ?>
                            <?= $val === '1' ? 'Sí' : 'No' ?>
                        <?php else: ?>
                            <?= htmlspecialchars($val) ?>
                        <?php endif ?>
                    </td>
                    <?php endforeach ?>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="sub">Sin registros.</div>
            <?php endif ?>
        <?php endif ?>

        <!-- Parámetros numéricos SPC -->
        <?php foreach ($spcNum as $p):
            $pid       = (int)$p['id'];
            $n         = max(1, (int)$p['tamanio_subgrupo']);
            $subgrupos = $subgrupos_por_parametro[$pid] ?? [];
        ?>
        <div class="subseccion-titulo">
            <?= htmlspecialchars($p['nombre']) ?>
            (<?= htmlspecialchars($p['unidad']) ?> ·
            <?= $n > 1 ? "n={$n}, carta X̄-" . ($n >= 6 ? 'S' : 'R') : 'n=1, carta X-MR' ?>)
        </div>
        <?php if (!empty($subgrupos)): ?>
        <table class="reg">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Lecturas</th>
                    <th style="background:#dbeafe">X̄</th>
                    <?php if ($n > 1): ?><th style="background:#ede9fe">R</th><?php endif ?>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($subgrupos as $sg): ?>
            <tr class="<?= $sg['fuera_de_control'] ? 'señal' : '' ?>">
                <td><?= htmlspecialchars($sg['hora']) ?></td>
                <td><?= implode(' / ', array_map(fn($v) => number_format((float)$v,2), $sg['valores'])) ?></td>
                <td style="background:#dbeafe;font-weight:700">
                    <?= $sg['promedio_xbar'] !== null ? number_format((float)$sg['promedio_xbar'],2) : '—' ?>
                </td>
                <?php if ($n > 1): ?>
                <td style="background:#ede9fe;font-weight:700">
                    <?= $sg['rango_r'] !== null ? number_format((float)$sg['rango_r'],2) : '—' ?>
                </td>
                <?php endif ?>
                <td style="<?= $sg['fuera_de_control'] ? 'color:#dc2626;font-weight:700' : '' ?>">
                    <?= $sg['fuera_de_control'] ? 'SEÑAL' : 'OK' ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="sub" style="margin-bottom:6px">Sin registros.</div>
        <?php endif ?>
        <?php endforeach ?>

        <!-- Carta p (atributos) -->
        <?php if (!empty($spcAttr)): ?>
        <table class="reg">
            <thead>
                <tr>
                    <th class="left">Parámetro (Carta p)</th>
                    <th>n inspeccionado</th>
                    <th>No conformes</th>
                    <th>Proporción p</th>
                    <th class="left">Observaciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($spcAttr as $p):
                $pid  = (int)$p['id'];
                $insp = $inspeccionPorParametro[$pid] ?? null;
            ?>
            <tr>
                <td class="left"><?= htmlspecialchars($p['nombre']) ?></td>
                <td><?= $insp ? $insp['n_inspeccionado'] : '—' ?></td>
                <td><?= $insp ? $insp['n_no_conformes'] : '—' ?></td>
                <td><?= $insp ? number_format($insp['proporcion_p'],4) : '—' ?></td>
                <td class="left"><?= $insp ? htmlspecialchars($insp['observaciones'] ?? '') : '—' ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>

    </div>
</div>
<?php endforeach ?>

<!-- Liberación PT -->
<?php if ($sesion['liberacion']): ?>
<?php $lib = $sesion['liberacion']; ?>
<div class="seccion">
    <div class="seccion-titulo">
        <?= $numSeccion + 1 ?>. Análisis y liberación de producto terminado
    </div>
    <div class="seccion-body">
        <div class="decision-box decision-<?= $lib['decision_final'] ?>">
            <div class="decision-label">Decisión final</div>
            <div class="decision-valor">
                <?= $lib['decision_final'] === 'liberado'
                    ? 'PRODUCTO LIBERADO'
                    : 'PRODUCTO EN CUARENTENA' ?>
            </div>
            <div class="sub" style="margin-top:3px">
                Supervisor: <?= htmlspecialchars($sesion['supervisor_nombre'].' '.$sesion['supervisor_apellidos']) ?>
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