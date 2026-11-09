<?php
/** @var array $alertas @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">⚠️ Alertas de Vencimiento</div>
        <div class="page-sub">
            Lotes de materia prima próximos a vencer o vencidos en el inventario.
        </div>
    </div>
</div>

<?php if (empty($alertas)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:60px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">✅</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px;color:#15803d">
            Sin alertas de vencimiento
        </div>
        <div style="font-size:13px">
            No hay materia prima próxima a vencer en los próximos 60 días.
        </div>
    </div>
</div>
<?php else: ?>

<!-- Resumen -->
<?php
$vencidos = array_filter($alertas, fn($a) => (int)$a['dias_restantes'] < 0);
$criticos = array_filter($alertas, fn($a) => (int)$a['dias_restantes'] >= 0 && (int)$a['dias_restantes'] < 15);
$proximos = array_filter($alertas, fn($a) => (int)$a['dias_restantes'] >= 15);
?>
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⛔</div>
        <div>
            <div class="stat-value" style="color:#dc2626"><?= count($vencidos) ?></div>
            <div class="stat-label">Lotes vencidos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">🚨</div>
        <div>
            <div class="stat-value" style="color:#c2410c"><?= count($criticos) ?></div>
            <div class="stat-label">Críticos (menos de 15 días)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">⚠️</div>
        <div>
            <div class="stat-value" style="color:#d97706"><?= count($proximos) ?></div>
            <div class="stat-label">Próximos a vencer</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código insumo</th>
                        <th>Insumo</th>
                        <th>Lote proveedor</th>
                        <th style="text-align:center">Vencimiento</th>
                        <th style="text-align:center">Días restantes</th>
                        <th style="text-align:right">Stock disponible</th>
                        <th style="text-align:center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alertas as $a):
                    $dias = (int)$a['dias_restantes'];
                    if ($dias < 0) {
                        $color = '#dc2626'; $badgeClass = 'badge-danger'; $label = 'Vencido';
                    } elseif ($dias < 15) {
                        $color = '#c2410c'; $badgeClass = 'badge-danger'; $label = 'Crítico';
                    } else {
                        $color = '#d97706'; $badgeClass = 'badge-warning'; $label = 'Próximo';
                    }
                ?>
                <tr style="background:<?= $dias < 0 ? '#fef2f2' : ($dias < 15 ? '#fff7ed' : '#fffbeb') ?>">
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                    border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($a['insumo_codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($a['insumo_nombre']) ?></strong>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($a['lote_proveedor']) ?>
                    </td>
                    <td style="text-align:center;font-weight:600;color:<?= $color ?>">
                        <?= date('d/m/Y', strtotime($a['fecha_vencimiento'])) ?>
                    </td>
                    <td style="text-align:center">
                        <span style="font-size:14px;font-weight:800;color:<?= $color ?>">
                            <?= $dias < 0 ? abs($dias).'d vencido' : $dias.'d' ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600">
                        <?= number_format($a['cantidad_disponible'],3) ?>
                        <?= htmlspecialchars($a['unidad_medida']) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $badgeClass ?>"><?= $label ?></span>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>