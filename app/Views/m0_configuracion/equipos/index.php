<?php
/** @var array $equipos @var array $alertas @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">Equipos y Maquinaria</div>
        <div class="page-sub">
            Registro de equipos con estado de calibración y mantenimiento.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/equipos/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo equipo
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Alertas de calibración -->
<?php if (!empty($alertas)): ?>
<div style="background:#fffbeb;border:1px solid #c9af49;border-radius:8px;
            padding:12px 16px;margin-bottom:16px;
            border-left:4px solid #c79d07">
    <div style="font-weight:700;color:#92400e;margin-bottom:8px;
                display:flex;align-items:center;gap:8px">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b"></i>
        <?= count($alertas) ?> equipo(s) requieren atención en calibración
    </div>
    <?php foreach ($alertas as $a): ?>
    <div style="font-size:13px;color:#78350f;margin-bottom:4px;
                display:flex;align-items:center;gap:6px">
        <i class="bi bi-dot"></i>
        <strong><?= htmlspecialchars($a['codigo']) ?></strong>
        — <?= htmlspecialchars($a['nombre']) ?>
        <?php if ($a['fecha_prox_calibr']): ?>
        <span style="color:#b45309">
            — Vence: <?= date('d/m/Y', strtotime($a['fecha_prox_calibr'])) ?>
            (<?= $a['dias_restantes'] >= 0
                ? 'en '.$a['dias_restantes'].' días'
                : abs($a['dias_restantes']).' días vencida' ?>)
        </span>
        <?php else: ?>
        <span style="color:#b45309">— Sin fecha de calibración registrada</span>
        <?php endif ?>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-cpu-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Listado de equipos
        </span>
        <span class="badge badge-info"><?= count($equipos) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="width:110px">Código</th>
                    <th>Equipo</th>
                    <th style="text-align:center;width:110px">Tipo</th>
                    <th style="width:130px">Línea</th>
                    <th style="text-align:center;width:130px">Calibración</th>
                    <th style="text-align:center;width:130px">Mantenimiento</th>
                    <th style="text-align:center;width:90px">Estado</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:90px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($equipos)): ?>
            <tr>
                <td colspan="<?= $canWrite ? 8 : 7 ?>"
                    style="text-align:center;padding:48px;color:#94a3b8">
                    <i class="bi bi-cpu" style="font-size:32px;display:block;margin-bottom:10px;color:#e2e8f0"></i>
                    No hay equipos registrados.
                    <?php if ($canWrite): ?>
                    <div style="margin-top:10px">
                        <a href="<?= APP_URL ?>/m0/equipos/nuevo" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Agregar el primero
                        </a>
                    </div>
                    <?php endif ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($equipos as $e):
                $calibBadge = match($e['estado_calibracion']) {
                    'vencida'      => ['badge-danger',  'Vencida'],
                    'proxima'      => ['badge-warning', 'Próxima'],
                    'vigente'      => ['badge-success', 'Vigente'],
                    'sin_registro' => ['badge-muted',   'Sin registro'],
                    default        => ['badge-muted',   'No aplica'],
                };
                $calibIcon = match($e['estado_calibracion']) {
                    'vencida' => 'bi-x-circle-fill',
                    'proxima' => 'bi-exclamation-triangle-fill',
                    'vigente' => 'bi-check-circle-fill',
                    default   => 'bi-dash-circle',
                };
                $mantBadge = match($e['estado_mantenimiento']) {
                    'vencido'      => ['badge-danger',  'Vencido'],
                    'proximo'      => ['badge-warning', 'Próximo'],
                    'vigente'      => ['badge-success', 'Vigente'],
                    default        => ['badge-muted',   'Sin registro'],
                };
                $mantIcon = match($e['estado_mantenimiento']) {
                    'vencido' => 'bi-x-circle-fill',
                    'proximo' => 'bi-exclamation-triangle-fill',
                    'vigente' => 'bi-check-circle-fill',
                    default   => 'bi-dash-circle',
                };
            ?>
            <tr>
                <td>
                    <code style="background:#f1f5f9;padding:3px 8px;
                                border-radius:5px;font-size:12px;
                                color:#1a2035;font-weight:700">
                        <?= htmlspecialchars($e['codigo']) ?>
                    </code>
                </td>
                <td>
                    <div style="font-weight:600;color:#0f172a">
                        <?= htmlspecialchars($e['nombre']) ?>
                    </div>
                    <?php if ($e['marca']): ?>
                    <div style="font-size:12px;color:#94a3b8;margin-top:2px">
                        <i class="bi bi-tag" style="font-size:10px"></i>
                        <?= htmlspecialchars($e['marca']) ?>
                        <?= $e['modelo'] ? ' · '.htmlspecialchars($e['modelo']) : '' ?>
                    </div>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-info">
                        <?= $tipos[$e['tipo']] ?? $e['tipo'] ?>
                    </span>
                </td>
                <td style="font-size:13px;color:#475569">
                    <?= htmlspecialchars($e['linea_nombre']) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $calibBadge[0] ?>"
                            style="display:inline-flex;align-items:center;gap:4px">
                        <i class="bi <?= $calibIcon ?>" style="font-size:11px"></i>
                        <?= $calibBadge[1] ?>
                    </span>
                    <?php if ($e['fecha_prox_calibr'] && $e['requiere_calibracion']): ?>
                    <div style="font-size:11px;color:#94a3b8;margin-top:3px">
                        <?= date('d/m/Y', strtotime($e['fecha_prox_calibr'])) ?>
                    </div>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $mantBadge[0] ?>"
                            style="display:inline-flex;align-items:center;gap:4px">
                        <i class="bi <?= $mantIcon ?>" style="font-size:11px"></i>
                        <?= $mantBadge[1] ?>
                    </span>
                    <?php if ($e['fecha_prox_mant']): ?>
                    <div style="font-size:11px;color:#94a3b8;margin-top:3px">
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
                <td style="text-align:center">
                    <a href="<?= APP_URL ?>/m0/equipos/<?= $e['id'] ?>/editar"
                        class="btn btn-sm btn-secondary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                </td>
                <?php endif ?>
            </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>