<?php
/** @var array $usuarios @var bool $canWrite */
?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">👥 Usuarios del sistema</div>
        <div class="page-sub">
            Gestión de accesos y roles. Solo el Administrador puede modificar usuarios.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/usuarios/nuevo" class="btn btn-primary">+ Nuevo usuario</a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Cargo</th>
                        <th>Correo</th>
                        <th style="text-align:center">Rol</th>
                        <th style="text-align:center">Estado</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding:40px">
                        No hay usuarios registrados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                <?php
                $rolBadge = match((int)$u['rol_id']) {
                    1 => 'badge-danger',
                    2 => 'badge-warning',
                    3 => 'badge-info',
                    4 => 'badge-success',
                    default => 'badge-muted',
                };
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;
                                        background:#2563eb;color:#fff;display:flex;
                                        align-items:center;justify-content:center;
                                        font-weight:700;font-size:13px;flex-shrink:0">
                                <?= strtoupper(substr($u['nombre'],0,1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:600">
                                    <?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px;color:#64748b">
                        <?= htmlspecialchars($u['cargo'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($u['email']) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $rolBadge ?>">
                            <?= htmlspecialchars($u['rol_nombre']) ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td>
                        <a href="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                        <form method="POST"
                                action="<?= APP_URL ?>/usuarios/<?= $u['id'] ?>/toggle"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="btn btn-sm <?= $u['activo'] ? 'btn-warning' : 'btn-success' ?>"
                                    data-confirm="<?= $u['activo']
                                        ? '¿Desactivar a '.$u['nombre'].'?'
                                        : '¿Activar a '.$u['nombre'].'?' ?>">
                                <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
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