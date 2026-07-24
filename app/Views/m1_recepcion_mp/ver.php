<?php
/** @var array $recepcion
 *  @var array $decisiones
 *  @var array $conf
 *  @var bool  $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php
$dec = $decisiones[$recepcion['decision']] ?? ['label'=>$recepcion['decision'],'badge'=>'badge-muted'];
$esPendiente = in_array($recepcion['decision'], ['cuarentena', 'observado'], true);

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
            <i class="bi bi-clipboard2-check-fill"></i> <?= htmlspecialchars($recepcion['codigo_recepcion']) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($recepcion['insumo_nombre']) ?> ·
            <?= htmlspecialchars($recepcion['proveedor_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($recepcion['fecha_analisis'])) ?>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite && $esPendiente): ?>
        <a href="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>/resolver"
            class="btn btn-warning"><i class="bi bi-question-diamond-fill"></i> Resolver decisión</a>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>/imprimir"
            class="btn btn-secondary" target="_blank"><i class="bi bi-printer-fill"></i> Imprimir</a>
        <a href="<?= APP_URL ?>/m1" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<!-- Encabezado con decisión -->
<div style="background:<?= $recepcion['decision']==='aprobado' ? '#f0fdf4' : ($recepcion['decision']==='rechazado' ? '#fef2f2' : '#fff7ed') ?>;
            border:1px solid <?= $recepcion['decision']==='aprobado' ? '#86efac' : ($recepcion['decision']==='rechazado' ? '#fca5a5' : '#fed7aa') ?>;
            border-radius:10px;padding:16px 20px;margin-bottom:16px;
            display:flex;justify-content:space-between;align-items:center">
    <div>
        <div style="font-size:var(--fs-dense-xs);font-weight:700;color:#64748b;text-transform:uppercase;
                    letter-spacing:.05em;margin-bottom:4px">DECISIÓN FINAL</div>
        <span class="badge <?= $dec['badge'] ?>" style="font-size:14px;padding:4px 12px">
            <?= $dec['label'] ?>
        </span>
        <?php if ($esPendiente): ?>
        <div style="font-size:var(--fs-dense-xs);color:#92400e;margin-top:6px">
            <i class="bi bi-info-circle"></i> Pendiente de resolución — el stock está reservado, no disponible.
        </div>
        <?php endif ?>
    </div>
    <div style="text-align:right;font-size:var(--fs-dense-sm);color:#64748b">
        <div>Lote proveedor: <strong><?= htmlspecialchars($recepcion['lote_proveedor']) ?></strong></div>
        <div>Vencimiento: <strong><?= date('d/m/Y', strtotime($recepcion['fecha_vencimiento'])) ?></strong></div>
        <?php if ($recepcion['stock_disponible_kg'] > 0): ?>
        <div style="color:<?= $esPendiente ? '#d97706' : '#15803d' ?>;font-weight:600">
            Stock <?= $esPendiente ? 'reservado' : 'ingresado' ?>: <?= number_format($recepcion['stock_disponible_kg'],3) ?>
            <?= htmlspecialchars($recepcion['unidad_medida']) ?>
        </div>
        <?php endif ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Datos generales -->
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="bi bi-clipboard2-check-fill"></i> Datos generales</span></div>
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
            <table style="width:100%;font-size:var(--fs-dense-sm);border-collapse:collapse">
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
        <div class="card-header"><span class="card-title"><i class="bi bi-eye-fill"></i> Análisis organoléptico</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:var(--fs-dense-sm);border-collapse:collapse">
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
                <td style="padding:7px 0;font-size:var(--fs-dense-xs)">
                    <?= htmlspecialchars($valor ?? '—') ?>
                </td>
                <td style="padding:7px 0;text-align:right">
                    <?= $showConf($conformidad) ?>
                </td>
            </tr>
            <?php endforeach ?>
            </table>
            <?php if ($recepcion['obs_organoleptico']): ?>
            <div style="margin-top:8px;font-size:var(--fs-dense-xs);color:#64748b">
                <strong>Obs:</strong> <?= htmlspecialchars($recepcion['obs_organoleptico']) ?>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- AGREGADO: reemplaza la tabla fija de 7 parámetros — ahora muestra
     solo los que realmente se configuraron y capturaron para este
     insumo específico, vía recepcion_resultados. -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-droplet-fill"></i> Parámetros del insumo</span>
    </div>
    <?php if (empty($recepcion['resultados'])): ?>
    <div class="card-body text-center" style="padding:24px;color:#94a3b8;font-size:var(--fs-dense-sm)">
        Este insumo no tenía parámetros configurados al momento de la recepción.
    </div>
    <?php else: ?>
    <div class="card-body" style="padding:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Parámetro</th>
                    <th style="text-align:center">Rango esperado</th>
                    <th style="text-align:center">Resultado</th>
                    <th style="text-align:center">Conformidad</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recepcion['resultados'] as $r):
                $rango = ($r['valor_min'] !== null || $r['valor_max'] !== null)
                    ? ($r['valor_min'] ?? '—') . ' a ' . ($r['valor_max'] ?? '—') . ' ' . $r['unidad']
                    : '—';
            ?>
            <tr>
                <td style="font-weight:500"><?= htmlspecialchars($r['parametro_nombre']) ?></td>
                <td style="text-align:center;font-size:var(--fs-dense-xs)">
                    <?= htmlspecialchars($rango) ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $r['resultado'] !== null && $r['resultado'] !== ''
                        ? htmlspecialchars($r['resultado']) : '—' ?>
                </td>
                <td style="text-align:center"><?= $showConf($r['conformidad']) ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- Conclusión -->
<?php if ($recepcion['conclusion']): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title"><i class="bi bi-file-earmark-text-fill"></i> Conclusión</span></div>
    <div class="card-body">
        <p style="font-size:var(--fs-dense-sm);color:#374151;line-height:1.6">
            <?= htmlspecialchars($recepcion['conclusion']) ?>
        </p>
    </div>
</div>
<?php endif ?>

<!-- Observaciones generales (incluye historial de resolución si aplica) -->
<?php if ($recepcion['obs_generales']): ?>
<div class="card">
    <div class="card-header"><span class="card-title"><i class="bi bi-chat-left-text-fill"></i> Observaciones generales</span></div>
    <div class="card-body">
        <p style="font-size:var(--fs-dense-sm);color:#374151;line-height:1.6;white-space:pre-line">
            <?= htmlspecialchars($recepcion['obs_generales']) ?>
        </p>
    </div>
</div>
<?php endif ?>