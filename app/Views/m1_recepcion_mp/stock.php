<?php
/** @var array $stock @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📦 Stock de Materia Prima</div>
        <div class="page-sub">Inventario disponible por insumo con fechas de vencimiento.</div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/alertas-vencimiento"
            class="btn btn-secondary">⚠️ Ver alertas</a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th style="text-align:center">Tipo</th>
                        <th style="text-align:center">Unidad</th>
                        <th style="text-align:right">Stock total</th>
                        <th style="text-align:center">Lotes disp.</th>
                        <th style="text-align:center">Próx. vencimiento</th>
                        <th style="text-align:center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($stock)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:40px">
                        No hay insumos en el catálogo.
                    </td>
                </tr>
                <?php else: ?>
                <?php
                $tipoBadges = [
                    'materia_prima'    => 'badge-info',
                    'material_empaque' => 'badge-purple',
                    'insumo_proceso'   => 'badge-warning',
                ];
                $tipoLabels = [
                    'materia_prima'    => 'MP',
                    'material_empaque' => 'ME',
                    'insumo_proceso'   => 'IP',
                ];
                foreach ($stock as $s):
                    $diasVence = $s['proximo_vencimiento']
                        ? (int)((strtotime($s['proximo_vencimiento']) - time()) / 86400)
                        : null;
                    $estadoStock = $s['stock_total'] > 0 ? 'Con stock' : 'Sin stock';
                    $estadoBadge = $s['stock_total'] > 0 ? 'badge-success' : 'badge-muted';
                ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                    border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($s['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($s['descripcion']) ?></strong>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $tipoBadges[$s['tipo']] ?? 'badge-muted' ?>">
                            <?= $tipoLabels[$s['tipo']] ?? '—' ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <code><?= htmlspecialchars($s['unidad_medida']) ?></code>
                    </td>
                    <td style="text-align:right;font-weight:700;font-size:14px">
                        <?php if ($s['stock_total'] > 0): ?>
                        <span style="color:#15803d">
                            <?= number_format($s['stock_total'],3) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-weight:400;font-size:12px">0.000</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted">
                            <?= (int)$s['lotes_disponibles'] ?> lotes
                        </span>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?php if ($s['proximo_vencimiento'] && $diasVence !== null): ?>
                        <?php
                        $colorV = $diasVence < 0 ? '#dc2626' : ($diasVence < 30 ? '#d97706' : '#374151');
                        $pesoV  = $diasVence < 30 ? '600' : '400';
                        ?>
                        <span style="color:<?= $colorV ?>;font-weight:<?= $pesoV ?>">
                            <?= date('d/m/Y', strtotime($s['proximo_vencimiento'])) ?>
                        </span>
                        <div style="font-size:10px;color:<?= $colorV ?>">
                            <?= $diasVence < 0
                                ? '⛔ Vencido'
                                : ($diasVence < 30 ? '⚠️ En '.$diasVence.'d' : 'En '.$diasVence.'d') ?>
                        </div>
                        <?php else: ?>
                        <span style="color:#94a3b8">—</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $estadoBadge ?>">
                            <?= $estadoStock ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>