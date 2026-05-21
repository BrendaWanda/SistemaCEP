<?php
/** @var bool $canWrite @var array $lineas */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">Líneas de Producción</div>
        <div class="page-sub">
            Gestiona las líneas productivas de la planta.
            Actualmente: Línea de Panificación.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/lineas/nueva" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva línea
        </a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-diagram-2-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Listado de líneas
        </span>
        <span class="badge badge-info"><?= count($lineas) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="width:110px">Código</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th style="text-align:center;width:130px">Productos</th>
                    <th style="text-align:center;width:100px">Estado</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:160px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lineas)): ?>
                <tr>
                    <td colspan="<?= $canWrite ? 6 : 5 ?>"
                        style="text-align:center;padding:48px;color:#94a3b8">
                        <i class="bi bi-diagram-2" style="font-size:32px;display:block;
                                    margin-bottom:10px;color:#e2e8f0"></i>
                        No hay líneas registradas.
                        <?php if ($canWrite): ?>
                        <div style="margin-top:10px">
                            <a href="<?= APP_URL ?>/m0/lineas/nueva"
                                class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg"></i> Crear la primera
                            </a>
                        </div>
                        <?php endif ?>
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($lineas as $linea): ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:3px 8px;
                                    border-radius:5px;font-size:12px;
                                    color:#090e22;font-weight:700">
                            <?= htmlspecialchars($linea['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong style="color:#0f172a">
                            <?= htmlspecialchars($linea['nombre']) ?>
                        </strong>
                    </td>
                    <td style="color:#64748b;font-size:13px">
                        <?= htmlspecialchars($linea['descripcion'] ?? '—') ?>
                    </td>
                    <td style="text-align:center">
                        <span style="font-weight:700;color:#4f8ef7">
                            <?= $linea['productos_activos'] ?>
                        </span>
                        <span style="color:#94a3b8;font-size:12px">
                            / <?= $linea['total_productos'] ?> total
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $linea['activa'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $linea['activa'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td style="text-align:center">
                        <div style="display:flex;gap:6px;justify-content:center">
                            <a href="<?= APP_URL ?>/m0/lineas/<?= $linea['id'] ?>/editar"
                                class="btn btn-sm btn-secondary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <form method="POST"
                                    action="<?= APP_URL ?>/m0/lineas/<?= $linea['id'] ?>/toggle"
                                    style="display:inline">
                                <input type="hidden" name="_token"
                                        value="<?= \App\Core\Controller::csrfToken() ?>">
                                <button type="submit"
                                        class="btn btn-sm <?= $linea['activa'] ? 'btn-warning' : 'btn-success' ?>"
                                        data-confirm="<?= $linea['activa']
                                            ? '¿Desactivar esta línea?'
                                            : '¿Activar esta línea?' ?>">
                                    <?php if ($linea['activa']): ?>
                                        <i class="bi bi-pause-fill"></i> Desactivar
                                    <?php else: ?>
                                        <i class="bi bi-play-fill"></i> Activar
                                    <?php endif ?>
                                </button>
                            </form>
                        </div>
                    </td>
                    <?php endif ?>
                </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>