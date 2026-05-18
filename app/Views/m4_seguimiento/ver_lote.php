<?php
/** @var array $lote @var array $consumoMP @var array $resumenMP
 *  @var array $estados @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<?php
$est    = $estados[$lote['estado']] ?? ['label'=>$lote['estado'],'badge'=>'badge-muted'];
$pct    = (float)($lote['porcentaje_rendimiento'] ?? 0);
$colorR = $pct >= 95 ? '#15803d' : ($pct >= 85 ? '#d97706' : ($pct > 0 ? '#dc2626' : '#94a3b8'));
?>

<div class="page-header">
    <div>
        <div class="page-title" style="font-family:monospace;font-size:22px">
            🏭 Lote <?= htmlspecialchars($lote['codigo_lote']) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($lote['producto_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($lote['fecha_produccion'])) ?> ·
            Turno <?= ucfirst($lote['turno']) ?> ·
            <span class="badge <?= $est['badge'] ?>"><?= $est['label'] ?></span>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite && $lote['estado'] === 'en_proceso'): ?>
        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/rendimiento"
            class="btn btn-secondary">📊 Rendimiento</a>
        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/mp"
            class="btn btn-secondary">📦 MP consumida</a>
        <form method="POST"
            action="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/cerrar"
            style="display:inline">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <button type="submit" class="btn btn-warning"
                    data-confirm="¿Cerrar el lote <?= $lote['codigo_lote'] ?>?">
                🔒 Cerrar lote
            </button>
        </form>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/imprimir"
            class="btn btn-secondary" target="_blank">🖨️</a>
    </div>
</div>

<!-- Info general + Rendimiento -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <div class="card">
        <div class="card-header"><span class="card-title">📋 Datos del lote</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $filas = [
                ['Código lote',    '<strong style="font-family:monospace;font-size:15px">'
                                    .htmlspecialchars($lote['codigo_lote']).'</strong>'],
                ['Producto',       htmlspecialchars($lote['producto_nombre'])],
                ['Línea',          htmlspecialchars($lote['linea_nombre'])],
                ['Receta',         htmlspecialchars($lote['receta_nombre']
                                    .' v'.$lote['receta_version'])],
                ['N° recetas',     number_format((float)$lote['numero_recetas'],1)],
                ['Fecha produc.',  date('d/m/Y', strtotime($lote['fecha_produccion']))],
                ['Turno',          ucfirst($lote['turno'])],
                ['Supervisor',     htmlspecialchars($lote['supervisor_nombre']
                                    .' '.$lote['supervisor_apellidos'])],
                ['Nivel/destino',  htmlspecialchars($lote['nivel'] ?? '—')],
                ['F. elaboración', date('d/m/Y', strtotime($lote['fecha_elaboracion']))],
                ['F. vencimiento', date('d/m/Y', strtotime($lote['fecha_vencimiento']))],
            ];
            foreach ($filas as [$label, $valor]):
            ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;font-size:12px;width:45%">
                    <?= $label ?>
                </td>
                <td style="padding:7px 0;font-weight:500"><?= $valor ?></td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">📊 Rendimiento</span></div>
        <div class="card-body">

            <!-- Barra de rendimiento -->
            <div style="margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;
                            margin-bottom:6px">
                    <span style="font-size:12px;color:#64748b">Rendimiento real</span>
                    <span style="font-size:20px;font-weight:800;color:<?= $colorR ?>">
                        <?= $pct > 0 ? number_format($pct,1).'%' : 'Sin datos' ?>
                    </span>
                </div>
                <div style="height:14px;background:#f1f5f9;border-radius:8px;overflow:hidden">
                    <?php
                    $barraStyle = 'height:100%;width:'.min(100,$pct).'%;'
                        . 'background:'.$colorR.';border-radius:8px;'
                        . 'transition:width .3s';
                    ?>
                    <div style="<?= $barraStyle ?>"></div>
                </div>
            </div>

            <!-- Unidades -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;
                        gap:10px;margin-bottom:16px">
                <div style="text-align:center;padding:10px;background:#f8fafc;
                            border-radius:8px">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px">Teórico</div>
                    <div style="font-size:18px;font-weight:700">
                        <?= number_format($lote['rendimiento_teorico_total'] ?? 0) ?>
                    </div>
                    <div style="font-size:10px;color:#94a3b8">unidades</div>
                </div>
                <div style="text-align:center;padding:10px;
                            background:<?= $pct > 0 ? '#f0fdf4' : '#f8fafc' ?>;
                            border-radius:8px">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px">Real</div>
                    <div style="font-size:18px;font-weight:700;color:<?= $colorR ?>">
                        <?= number_format($lote['rendimiento_real_total'] ?? 0) ?>
                    </div>
                    <div style="font-size:10px;color:#94a3b8">unidades</div>
                </div>
                <div style="text-align:center;padding:10px;background:#f8fafc;
                            border-radius:8px">
                    <div style="font-size:11px;color:#64748b;margin-bottom:4px">
                        Diferencia
                    </div>
                    <div style="font-size:18px;font-weight:700;
                                color:<?= ($lote['diferencia_unidades'] ?? 0) >= 0
                                    ? '#15803d' : '#dc2626' ?>">
                        <?= ($lote['diferencia_unidades'] ?? 0) >= 0 ? '+' : '' ?>
                        <?= number_format($lote['diferencia_unidades'] ?? 0) ?>
                    </div>
                    <div style="font-size:10px;color:#94a3b8">unidades</div>
                </div>
            </div>

            <!-- Mermas -->
            <div style="font-size:11px;font-weight:700;color:#475569;
                        text-transform:uppercase;letter-spacing:.05em;
                        margin-bottom:8px">Desglose de mermas (kg)</div>
            <?php
            $mermas = [
                ['Producto',     'merma_producto_kg',    '#dc2626'],
                ['Envase/bobina','merma_envase_kg',      '#d97706'],
                ['Reproceso',    'merma_reproceso_kg',   '#7c3aed'],
                ['No conforme',  'merma_no_conforme_kg', '#dc2626'],
                ['Quemado',      'merma_quemado_kg',     '#92400e'],
            ];
            $totalMerma = 0;
            foreach ($mermas as [$label, $campo, $color]):
                $val = (float)($lote[$campo] ?? 0);
                $totalMerma += $val;
                if ($val <= 0) continue;
            ?>
            <div style="display:flex;justify-content:space-between;
                        align-items:center;margin-bottom:4px;
                        padding:4px 8px;background:#f8fafc;border-radius:4px">
                <span style="font-size:12px;color:#374151"><?= $label ?></span>
                <span style="font-size:12px;font-weight:700;color:<?= $color ?>">
                    <?= number_format($val,3) ?> kg
                </span>
            </div>
            <?php endforeach ?>
            <?php if ($totalMerma > 0): ?>
            <div style="display:flex;justify-content:space-between;
                        padding:6px 8px;border-top:1px solid #e2e8f0;
                        margin-top:4px;font-weight:700">
                <span style="font-size:12px">Total merma</span>
                <span style="color:#dc2626">
                    <?= number_format($totalMerma,3) ?> kg
                </span>
            </div>
            <?php elseif ($lote['estado'] === 'en_proceso'): ?>
            <div style="font-size:12px;color:#94a3b8;text-align:center;
                        padding:8px">
                Sin mermas registradas aún
            </div>
            <?php endif ?>

            <?php if ($canWrite && $lote['estado'] === 'en_proceso'): ?>
            <div style="margin-top:12px">
                <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/rendimiento"
                    class="btn btn-primary btn-full">
                    <?= $pct > 0 ? '✏️ Actualizar rendimiento' : '📊 Registrar rendimiento' ?>
                </a>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- MP Consumida -->
<div class="card">
    <div class="card-header">
        <span class="card-title">📦 Materia Prima consumida</span>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge badge-info">
                <?= count($resumenMP) ?> insumos
            </span>
            <?php if ($canWrite && $lote['estado'] === 'en_proceso'): ?>
            <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/mp"
                class="btn btn-sm btn-primary">+ Agregar MP</a>
            <?php endif ?>
        </div>
    </div>
    <?php if (empty($resumenMP)): ?>
    <div class="card-body text-center text-muted" style="padding:30px">
        No se ha registrado consumo de MP para este lote.
        <?php if ($canWrite && $lote['estado'] === 'en_proceso'): ?>
        <br><a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/mp"
            class="btn btn-sm btn-primary" style="margin-top:8px">
            Registrar MP consumida
        </a>
        <?php endif ?>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th style="text-align:right">Total usado</th>
                    <th style="text-align:center">Lotes de stock</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resumenMP as $r): ?>
            <tr>
                <td>
                    <code style="background:#f1f5f9;padding:2px 6px;
                            border-radius:4px;font-size:11px">
                        <?= htmlspecialchars($r['codigo']) ?>
                    </code>
                </td>
                <td><strong><?= htmlspecialchars($r['descripcion']) ?></strong></td>
                <td style="text-align:right;font-weight:700">
                    <?= number_format($r['total_usado'],3) ?>
                    <?= htmlspecialchars($r['unidad_medida']) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-muted">
                        <?= $r['lotes_usados'] ?> lotes
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<div style="margin-top:8px">
    <a href="<?= APP_URL ?>/m4" class="btn btn-secondary">← Volver</a>
    <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($lote['codigo_lote']) ?>"
        class="btn btn-secondary">🔗 Ver trazabilidad</a>
</div>