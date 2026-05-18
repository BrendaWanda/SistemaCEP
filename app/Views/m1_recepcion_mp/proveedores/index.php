<?php
/** @var array $proveedores @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🚛 Proveedores</div>
        <div class="page-sub">Catálogo de proveedores de materia prima e insumos.</div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/proveedores/nuevo" class="btn btn-primary">
            + Nuevo proveedor
        </a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Procedencia</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Reg. sanitario</th>
                        <th style="text-align:center">Estado</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($proveedores)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:40px">
                        No hay proveedores registrados.
                        <a href="<?= APP_URL ?>/m1/proveedores/nuevo">Agregar el primero</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($proveedores as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($p['procedencia'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($p['telefono'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($p['email'] ?? '—') ?>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($p['registro_sanitario'] ?? '—') ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $p['activo'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td>
                        <a href="<?= APP_URL ?>/m1/proveedores/<?= $p['id'] ?>/editar"
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