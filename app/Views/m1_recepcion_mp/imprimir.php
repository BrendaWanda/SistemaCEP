<?php
/** @var array $recepcion @var array $decisiones @var array $conf */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recepción <?= htmlspecialchars($recepcion['codigo_recepcion']) ?></title>
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
.codigo-doc { font-size: 11px; text-align: right; }

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

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
.grid4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 6px; }

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
}

table.analisis {
    width: 100%;
    border-collapse: collapse;
    font-size: 10px;
}
table.analisis th {
    background: #f0f0f0;
    padding: 4px 6px;
    text-align: center;
    border: 1px solid #ccc;
    font-size: 10px;
}
table.analisis td {
    padding: 4px 6px;
    border: 1px solid #ccc;
    text-align: center;
}
table.analisis td.left { text-align: left; }

.decision-box {
    border: 2px solid #000;
    border-radius: 6px;
    padding: 10px;
    text-align: center;
    margin: 10px 0;
}
.decision-label { font-size: 10px; font-weight: 700; text-transform: uppercase; }
.decision-valor {
    font-size: 18px;
    font-weight: 900;
    text-transform: uppercase;
    margin-top: 4px;
}
.decision-aprobado  { border-color: #15803d; color: #15803d; }
.decision-rechazado { border-color: #dc2626; color: #dc2626; }
.decision-observado { border-color: #d97706; color: #d97706; }
.decision-cuarentena{ border-color: #7c3aed; color: #7c3aed; }

.firmas {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}
.firma-box {
    text-align: center;
    border-top: 1px solid #000;
    padding-top: 4px;
    font-size: 10px;
}
.firma-linea {
    height: 40px;
    border-bottom: 1px solid #999;
    margin-bottom: 4px;
}

.no-print { display: block; }
@media print {
    .no-print { display: none !important; }
    body { padding: 10px; }
}
</style>
</head>
<body>

<!-- Botón imprimir (no imprimible) -->
<div class="no-print" style="text-align:right;margin-bottom:12px">
    <button onclick="window.print()"
            style="background:#1e3a5f;color:#fff;padding:8px 20px;
                    border:none;border-radius:6px;cursor:pointer;font-size:13px">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()"
            style="background:#f1f5f9;color:#374151;padding:8px 16px;
                    border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;
                    font-size:13px;margin-left:8px">
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
        <div class="titulo-doc">Registro de Recepción de Materia Prima</div>
        <div class="empresa-sub" style="margin-top:4px">
            Formulario SIREMU — Control de Calidad
        </div>
    </div>
    <div class="codigo-doc">
        <div><strong>Código:</strong> <?= htmlspecialchars($recepcion['codigo_recepcion']) ?></div>
        <div><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($recepcion['fecha_analisis'])) ?></div>
    </div>
</div>

<!-- Datos generales -->
<div class="seccion">
    <div class="seccion-titulo">1. Datos generales</div>
    <div class="seccion-body">
        <div class="grid2" style="margin-bottom:6px">
            <div class="campo">
                <div class="campo-label">Insumo / Materia Prima</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['insumo_codigo'].' — '.$recepcion['insumo_nombre']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Proveedor</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['proveedor_nombre']) ?>
                    <?= $recepcion['proveedor_procedencia']
                        ? ' ('.$recepcion['proveedor_procedencia'].')' : '' ?>
                </div>
            </div>
        </div>
        <div class="grid4">
            <div class="campo">
                <div class="campo-label">Lote proveedor</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['lote_proveedor']) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Fecha fabricación</div>
                <div class="campo-valor">
                    <?= $recepcion['fecha_fabricacion']
                        ? date('d/m/Y', strtotime($recepcion['fecha_fabricacion'])) : '—' ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Fecha vencimiento</div>
                <div class="campo-valor">
                    <?= date('d/m/Y', strtotime($recepcion['fecha_vencimiento'])) ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Cantidad recibida</div>
                <div class="campo-valor">
                    <?= number_format($recepcion['cantidad_recibida'],3) ?>
                    <?= htmlspecialchars($recepcion['unidad_medida']) ?>
                </div>
            </div>
        </div>
        <div class="grid3" style="margin-top:6px">
            <div class="campo">
                <div class="campo-label">Presentación</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['presentacion'] ?? '—') ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Envase primario</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['envase_primario'] ?? '—') ?>
                </div>
            </div>
            <div class="campo">
                <div class="campo-label">Reg. sanitario</div>
                <div class="campo-valor">
                    <?= htmlspecialchars($recepcion['nro_registro_sanitario'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Análisis organoléptico -->
<div class="seccion">
    <div class="seccion-titulo">2. Análisis organoléptico</div>
    <div class="seccion-body">
        <table class="analisis">
            <thead>
                <tr>
                    <th style="text-align:left;width:20%">Parámetro</th>
                    <th style="width:50%;text-align:left">Resultado observado</th>
                    <th style="width:30%">Conformidad</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $orgRows = [
                ['Sabor / Olor', $recepcion['sabor_olor'],        $recepcion['conf_sabor_olor']],
                ['Color',        $recepcion['color'],             $recepcion['conf_color']],
                ['Desc. física', $recepcion['descripcion_fisica'],$recepcion['conf_descripcion_fisica']],
            ];
            foreach ($orgRows as [$label, $valor, $confVal]):
                $confInfo = $conf[$confVal] ?? ['label'=>'—'];
            ?>
            <tr>
                <td class="left"><strong><?= $label ?></strong></td>
                <td class="left"><?= htmlspecialchars($valor ?? '—') ?></td>
                <td><?= $confInfo['label'] ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php if ($recepcion['obs_organoleptico']): ?>
        <div class="campo" style="margin-top:6px">
            <div class="campo-label">Observaciones</div>
            <div class="campo-valor">
                <?= htmlspecialchars($recepcion['obs_organoleptico']) ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Análisis fisicoquímico -->
<div class="seccion">
    <div class="seccion-titulo">3. Análisis fisicoquímico</div>
    <div class="seccion-body">
        <table class="analisis">
            <thead>
                <tr>
                    <th style="text-align:left;width:20%">Parámetro</th>
                    <th style="width:25%">Especificación</th>
                    <th style="width:25%">Resultado</th>
                    <th style="width:30%">Conformidad</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $fisicoRows = [
                ['Humedad (%)',   $recepcion['esp_humedad'],        $recepcion['res_humedad'],        $recepcion['conf_humedad']],
                ['pH',           $recepcion['esp_ph'],             $recepcion['res_ph'],             $recepcion['conf_ph']],
                ['Densidad',     $recepcion['esp_densidad'],       $recepcion['res_densidad'],       $recepcion['conf_densidad']],
                ['Gluten (%)',   $recepcion['esp_gluten'],         $recepcion['res_gluten'],         $recepcion['conf_gluten']],
                ['Brix (°)',     $recepcion['esp_brix'],           $recepcion['res_brix'],           $recepcion['conf_brix']],
                ['Activ. agua',  $recepcion['esp_actividad_agua'], $recepcion['res_actividad_agua'], $recepcion['conf_actividad_agua']],
                ['Impurezas (%)',$recepcion['esp_impurezas'],      $recepcion['res_impurezas'],      $recepcion['conf_impurezas']],
            ];
            foreach ($fisicoRows as [$label, $esp, $res, $confVal]):
                if (!$esp && !$res) continue;
                $confInfo = $conf[$confVal] ?? ['label'=>'—'];
            ?>
            <tr>
                <td class="left"><strong><?= $label ?></strong></td>
                <td><?= htmlspecialchars($esp ?? '—') ?></td>
                <td><?= $res !== null ? number_format((float)$res, 4) : '—' ?></td>
                <td><?= $confInfo['label'] ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Conclusión y decisión -->
<div class="seccion">
    <div class="seccion-titulo">4. Conclusión y decisión final</div>
    <div class="seccion-body">
        <?php if ($recepcion['conclusion']): ?>
        <div class="campo" style="margin-bottom:10px">
            <div class="campo-label">Conclusión</div>
            <div class="campo-valor">
                <?= htmlspecialchars($recepcion['conclusion']) ?>
            </div>
        </div>
        <?php endif ?>

        <div class="decision-box decision-<?= $recepcion['decision'] ?>">
            <div class="decision-label">Decisión final</div>
            <div class="decision-valor">
                <?= $decisiones[$recepcion['decision']]['label'] ?? strtoupper($recepcion['decision']) ?>
            </div>
            <?php if ($recepcion['stock_disponible_kg'] > 0): ?>
            <div style="font-size:11px;margin-top:4px">
                Cantidad aprobada para stock:
                <strong>
                    <?= number_format($recepcion['stock_disponible_kg'],3) ?>
                    <?= htmlspecialchars($recepcion['unidad_medida']) ?>
                </strong>
            </div>
            <?php endif ?>
        </div>

        <?php if ($recepcion['obs_generales']): ?>
        <div class="campo">
            <div class="campo-label">Observaciones generales</div>
            <div class="campo-valor">
                <?= htmlspecialchars($recepcion['obs_generales']) ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Firmas -->
<div class="firmas">
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Responsable de muestreo</strong><br>
        <?= htmlspecialchars(
            $recepcion['muestreo_nombre'].' '.$recepcion['muestreo_apellidos']
        ) ?>
    </div>
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Responsable de análisis</strong><br>
        <?= htmlspecialchars(
            $recepcion['analisis_nombre'].' '.$recepcion['analisis_apellidos']
        ) ?>
    </div>
    <div class="firma-box">
        <div class="firma-linea"></div>
        <strong>Visto bueno</strong><br>
        <?= $recepcion['visto_bueno_nombre']
            ? htmlspecialchars($recepcion['visto_bueno_nombre'].' '.$recepcion['visto_bueno_apellidos'])
            : '___________________________' ?>
    </div>
</div>

<div style="margin-top:16px;text-align:center;font-size:9px;color:#888;border-top:1px solid #ccc;padding-top:8px">
    SIACEP — Sistema Integrado de Análisis, Control y Ejecución de Producción ·
    Gustossi SRL · Generado: <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>