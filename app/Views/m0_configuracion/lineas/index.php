<?php
/** @var bool $canWrite @var array $lineas */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🏗️ Líneas de Producción</div>
        <div class="page-sub">Gestiona las líneas productivas de la planta. Actualmente: Línea de Panificación.</div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/lineas/nueva" class="btn btn-primary">+ Nueva línea</a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($lineas)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding:40px">
                        No hay líneas registradas.
                        <a href="<?= APP_URL ?>/m0/lineas/nueva">Crear la primera</a>
                    </td></tr>
                <?php else: ?>
                <?php foreach ($lineas as $linea): ?>
                    <tr>
                        <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px">
                            <?= htmlspecialchars($linea['codigo']) ?>
                        </code></td>
                        <td><strong><?= htmlspecialchars($linea['nombre']) ?></strong></td>
                        <td class="text-muted"><?= htmlspecialchars($linea['descripcion'] ?? '—') ?></td>
                        <td>
                            <span style="font-weight:600"><?= $linea['productos_activos'] ?></span>
                            <span class="text-muted"> / <?= $linea['total_productos'] ?> total</span>
                        </td>
                        <td>
                            <span class="badge <?= $linea['activa'] ? 'badge-success' : 'badge-muted' ?>">
                                <?= $linea['activa'] ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                        <?php if ($canWrite): ?>
                        <td>
                            <a href="<?= APP_URL ?>/m0/lineas/<?= $linea['id'] ?>/editar"
                                class="btn btn-sm btn-secondary">Editar</a>
                            <form method="POST"
                                    action="<?= APP_URL ?>/m0/lineas/<?= $linea['id'] ?>/toggle"
                                    style="display:inline">
                                <input type="hidden" name="_token"
                                        value="<?= \App\Core\Controller::csrfToken() ?>">
                                <button type="submit"
                                        class="btn btn-sm <?= $linea['activa'] ? 'btn-warning' : 'btn-success' ?>"
                                        data-confirm="<?= $linea['activa'] ? '¿Desactivar esta línea?' : '¿Activar esta línea?' ?>">
                                    <?= $linea['activa'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
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