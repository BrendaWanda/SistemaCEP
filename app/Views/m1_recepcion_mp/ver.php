<?php
/** @var array $recepcion @var array $decisiones @var array $conf */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php
$dec = $decisiones[$recepcion['decision']] ?? ['label'=>$recepcion['decision'],'badge'=>'badge-muted'];

// Helper para mostrar conformidad
$showConf = function(?string $val) use ($conf): string {
    if (!$val) return '<span style="color:#94a3b8">—</span>';
    $c = $conf[$val] ?? ['label'=>$val,'badge'=>'badge-muted'];
    return "<span class=\"badge {$c['badge']}\">{$c['label']}</span>";
};
?>

<div class="page-header">
    <div>
        <div class="page-title">
            📋 <?= htmlspecialchars($recepcion['codigo_recepcion']) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($recepcion['insumo_nombre']) ?> ·
            <?= htmlspecialchars($recepcion['proveedor_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($recepcion['fecha_analisis'])) ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>/imprimir"
            class="btn btn-secondary" target="_blank">🖨️ Imprimir</a>
        <a href="<?= APP_URL ?>/m1" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<!-- Encabezado con decisión -->
<div style="background:<?= $recepcion['decision']==='aprobado' ? '#f0fdf4' : ($recepcion['decision']==='rechazado' ? '#fef2f2' : '#fff7ed') ?>;
            border:1px solid <?= $recepcion['decision']==='aprobado' ? '#86efac' : ($recepcion['decision']==='rechazado' ? '#fca5a5' : '#fed7aa') ?>;
            border-radius:10px;padding:16px 20px;margin-bottom:16px;
            display:flex;justify-content:space-between;align-items:center">
    <div>
        <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;
                    letter-spacing:.05em;margin-bottom:4px">DECISIÓN FINAL</div>
        <span class="badge <?= $dec['badge'] ?>" style="font-size:14px;padding:4px 12px">
            <?= $dec['label'] ?>
        </span>
    </div>
    <div style="text-align:right;font-size:12px;color:#64748b">
        <div>Lote proveedor: <strong><?= htmlspecialchars($recepcion['lote_proveedor']) ?></strong></div>
        <div>Vencimiento: <strong><?= date('d/m/Y', strtotime($recepcion['fecha_vencimiento'])) ?></strong></div>
        <?php if ($recepcion['stock_disponible_kg'] > 0): ?>
        <div style="color:#15803d;font-weight:600">
            Stock ingresado: <?= number_format($recepcion['stock_disponible_kg'],3) ?>
            <?= htmlspecialchars($recepcion['unidad_medida']) ?>
        </div>
        <?php endif ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Datos generales -->
    <div class="card">
        <div class="card-header"><span class="card-title">📋 Datos generales</span></div>
        <div class="card-body">
            <?php
            $generales = [
                'Insumo'          => htmlspecialchars($recepcion['insumo_codigo'].' — '.$recepcion['insumo_nombre']),
                'Proveedor'       => htmlspecialchars($recepcion['proveedor_nombre'].($recepcion['proveedor_procedencia'] ? ' ('.$recepcion['proveedor_procedencia'].')' : '')),
                'Cantidad recibida'=> number_format($recepcion['cantidad_recibida'],3).' '.htmlspecialchars($recepcion['unidad_medida']),
                'Presentación'    => htmlspecialchars($recepcion['presentacion'] ?? '—'),
                'Envase primario' => htmlspecialchars($recepcion['envase_primario'] ?? '—'),
                'Reg. sanitario'  => htmlspecialchars($recepcion['nro_registro_sanitario'] ?? '—'),
                'Fecha muestreo'  => date('d/m/Y', strtotime($recepcion['fecha_muestreo'])),
                'Fecha análisis'  => date('d/m/Y', strtotime($recepcion['fecha_analisis'])),
                'Responsable muestreo' => htmlspecialchars($recepcion['muestreo_nombre'].' '.$recepcion['muestreo_apellidos']),
                'Responsable análisis' => htmlspecialchars($recepcion['analisis_nombre'].' '.$recepcion['analisis_apellidos']),
            ];
            ?>
            <table style="width:100%;font-size:12px;border-collapse:collapse">
            <?php foreach ($generales as $label => $valor): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:6px 0;color:#64748b;width:45%"><?= $label ?></td>
                <td style="padding:6px 0;font-weight:500"><?= $valor ?></td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>

    <!-- Análisis organoléptico -->
    <div class="card">
        <div class="card-header"><span class="card-title">👁️ Análisis organoléptico</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:12px;border-collapse:collapse">
            <?php
            $orgRows = [
                ['Sabor / Olor', $recepcion['sabor_olor'], $recepcion['conf_sabor_olor']],
                ['Color',        $recepcion['color'],      $recepcion['conf_color']],
                ['Desc. física', $recepcion['descripcion_fisica'], $recepcion['conf_descripcion_fisica']],
            ];
            foreach ($orgRows as [$label, $valor, $conformidad]):
            ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;width:30%"><?= $label ?></td>
                <td style="padding:7px 0;font-size:11px">
                    <?= htmlspecialchars($valor ?? '—') ?>
                </td>
                <td style="padding:7px 0;text-align:right">
                    <?= $showConf($conformidad) ?>
                </td>
            </tr>
            <?php endforeach ?>
            </table>
            <?php if ($recepcion['obs_organoleptico']): ?>
            <div style="margin-top:8px;font-size:11px;color:#64748b">
                <strong>Obs:</strong> <?= htmlspecialchars($recepcion['obs_organoleptico']) ?>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Análisis fisicoquímico -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">🧪 Análisis fisicoquímico</span>
    </div>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Parámetro</th>
                    <th style="text-align:center">Especificación</th>
                    <th style="text-align:center">Resultado</th>
                    <th style="text-align:center">Conformidad</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $fisicoRows = [
                ['Humedad (%)',     $recepcion['esp_humedad'],        $recepcion['res_humedad'],        $recepcion['conf_humedad']],
                ['pH',             $recepcion['esp_ph'],             $recepcion['res_ph'],             $recepcion['conf_ph']],
                ['Densidad',       $recepcion['esp_densidad'],       $recepcion['res_densidad'],       $recepcion['conf_densidad']],
                ['Gluten (%)',     $recepcion['esp_gluten'],         $recepcion['res_gluten'],         $recepcion['conf_gluten']],
                ['Brix (°)',       $recepcion['esp_brix'],           $recepcion['res_brix'],           $recepcion['conf_brix']],
                ['Activ. agua',    $recepcion['esp_actividad_agua'], $recepcion['res_actividad_agua'], $recepcion['conf_actividad_agua']],
                ['Impurezas (%)',  $recepcion['esp_impurezas'],      $recepcion['res_impurezas'],      $recepcion['conf_impurezas']],
            ];
            foreach ($fisicoRows as [$label, $esp, $res, $conf_val]):
                if (!$esp && !$res) continue;
            ?>
            <tr>
                <td style="font-weight:500"><?= $label ?></td>
                <td style="text-align:center;font-size:12px">
                    <?= htmlspecialchars($esp ?? '—') ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $res !== null ? number_format((float)$res,4) : '—' ?>
                </td>
                <td style="text-align:center"><?= $showConf($conf_val) ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Conclusión -->
<?php if ($recepcion['conclusion']): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">📝 Conclusión</span></div>
    <div class="card-body">
        <p style="font-size:13px;color:#374151;line-height:1.6">
            <?= htmlspecialchars($recepcion['conclusion']) ?>
        </p>
    </div>
</div>
<?php endif ?>