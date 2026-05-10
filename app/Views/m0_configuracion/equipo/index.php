<?php
/** @var array $equipos @var array $alertas @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">⚙️ Equipos y Maquinaria</div>
        <div class="page-sub">
            Registro de equipos con estado de calibración y mantenimiento.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/equipos/nuevo" class="btn btn-primary">+ Nuevo equipo</a>
    </div>
    <?php endif ?>
</div>

<!-- Alertas de calibración -->
<?php if (!empty($alertas)): ?>
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;
            padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;color:#c2410c;margin-bottom:6px">
        ⚠️ <?= count($alertas) ?> equipo(s) requieren atención en calibración
    </div>
    <?php foreach ($alertas as $a): ?>
    <div style="font-size:12px;color:#92400e;margin-bottom:2px">
        · <strong><?= htmlspecialchars($a['codigo']) ?></strong>
        — <?= htmlspecialchars($a['nombre']) ?>
        <?php if ($a['fecha_prox_calibr']): ?>
        — Vence: <?= date('d/m/Y', strtotime($a['fecha_prox_calibr'])) ?>
        (<?= $a['dias_restantes'] >= 0
            ? 'en '.$a['dias_restantes'].' días'
            : abs($a['dias_restantes']).' días vencida' ?>)
        <?php else: ?>
        — <em>Sin fecha de calibración registrada</em>
        <?php endif ?>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Equipo</th>
                        <th style="text-align:center">Tipo</th>
                        <th>Línea</th>
                        <th style="text-align:center">Calibración</th>
                        <th style="text-align:center">Mantenimiento</th>
                        <th style="text-align:center">Estado</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($equipos)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px">
                    No hay equipos registrados.
                    <a href="<?= APP_URL ?>/m0/equipos/nuevo">Agregar el primero</a>
                </td></tr>
                <?php else: ?>
                <?php foreach ($equipos as $e):
                    $calibBadge = match($e['estado_calibracion']) {
                        'vencida'      => ['badge-danger',  '❌ Vencida'],
                        'proxima'      => ['badge-warning', '⚠️ Próxima'],
                        'vigente'      => ['badge-success', '✓ Vigente'],
                        'sin_registro' => ['badge-muted',   '— Sin registro'],
                        default        => ['badge-muted',   'No aplica'],
                    };
                    $mantBadge = match($e['estado_mantenimiento']) {
                        'vencido'      => ['badge-danger',  '❌ Vencido'],
                        'proximo'      => ['badge-warning', '⚠️ Próximo'],
                        'vigente'      => ['badge-success', '✓ Vigente'],
                        default        => ['badge-muted',   '— Sin registro'],
                    };
                ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                        border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($e['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($e['nombre']) ?></strong>
                        <?php if ($e['marca']): ?>
                        <div style="font-size:11px;color:#94a3b8">
                            <?= htmlspecialchars($e['marca']) ?>
                            <?= $e['modelo'] ? ' · '.$e['modelo'] : '' ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-info">
                            <?= $tipos[$e['tipo']] ?? $e['tipo'] ?>
                        </span>
                    </td>
                    <td style="font-size:12px"><?= htmlspecialchars($e['linea_nombre']) ?></td>
                    <td style="text-align:center">
                        <span class="badge <?= $calibBadge[0] ?>"><?= $calibBadge[1] ?></span>
                        <?php if ($e['fecha_prox_calibr'] && $e['requiere_calibracion']): ?>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px">
                            <?= date('d/m/Y', strtotime($e['fecha_prox_calibr'])) ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $mantBadge[0] ?>"><?= $mantBadge[1] ?></span>
                        <?php if ($e['fecha_prox_mant']): ?>
                        <div style="font-size:10px;color:#94a3b8;margin-top:2px">
                            <?= date('d/m/Y', strtotime($e['fecha_prox_mant'])) ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $e['activo'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $e['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td>
                        <a href="<?= APP_URL ?>/m0/equipos/<?= $e['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                    </td>
                    <?php endif ?>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>