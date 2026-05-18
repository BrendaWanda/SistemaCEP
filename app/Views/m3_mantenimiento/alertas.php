<?php
/** @var array $alertas @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">⚠️ Alertas de Mantenimiento</div>
        <div class="page-sub">
            Equipos con mantenimiento vencido o próximo en los siguientes 30 días.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m3/nuevo" class="btn btn-primary">
            + Registrar mantenimiento
        </a>
    </div>
    <?php endif ?>
</div>

<?php if (empty($alertas)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:60px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">✅</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px;
                    color:#15803d">
            Sin alertas de mantenimiento
        </div>
        <div style="font-size:13px">
            Todos los equipos tienen sus mantenimientos al día.
        </div>
    </div>
</div>
<?php else: ?>

<?php
$vencidos = array_filter($alertas, fn($a) => (int)$a['dias_restantes'] < 0);
$proximos = array_filter($alertas, fn($a) => (int)$a['dias_restantes'] >= 0);
?>

<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⛔</div>
        <div>
            <div class="stat-value" style="color:#dc2626">
                <?= count($vencidos) ?>
            </div>
            <div class="stat-label">Mantenimientos vencidos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">⚠️</div>
        <div>
            <div class="stat-value" style="color:#d97706">
                <?= count($proximos) ?>
            </div>
            <div class="stat-label">Próximos a vencer (30d)</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Equipo</th>
                        <th>Línea</th>
                        <th style="text-align:center">Próx. mantenimiento</th>
                        <th style="text-align:center">Días restantes</th>
                        <th style="text-align:center">Estado</th>
                        <?php if ($canWrite): ?><th>Acción</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alertas as $a):
                    $dias  = (int)$a['dias_restantes'];
                    if ($dias < 0) {
                        $color = '#dc2626';
                        $badge = 'badge-danger';
                        $label = 'Vencido';
                    } elseif ($dias < 7) {
                        $color = '#c2410c';
                        $badge = 'badge-danger';
                        $label = 'Urgente';
                    } else {
                        $color = '#d97706';
                        $badge = 'badge-warning';
                        $label = 'Próximo';
                    }
                ?>
                <tr style="background:<?= $dias < 0 ? '#fef2f2' : '#fffbeb' ?>">
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                        border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($a['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($a['equipo_nombre']) ?></strong>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($a['linea_nombre']) ?>
                    </td>
                    <td style="text-align:center;font-weight:600;
                                color:<?= $color ?>">
                        <?= date('d/m/Y', strtotime($a['fecha_prox_mant'])) ?>
                    </td>
                    <td style="text-align:center">
                        <span style="font-size:16px;font-weight:800;
                                        color:<?= $color ?>">
                            <?= $dias < 0
                                ? abs($dias).'d vencido'
                                : $dias.'d' ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $badge ?>"><?= $label ?></span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td>
                        <a href="<?= APP_URL ?>/m3/nuevo?equipo_id=<?= $a['id'] ?>"
                            class="btn btn-sm btn-primary">
                            + Registrar
                        </a>
                    </td>
                    <?php endif ?>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif ?>