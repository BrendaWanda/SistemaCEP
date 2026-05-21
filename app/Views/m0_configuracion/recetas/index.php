<?php
/** @var array $recetas @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Recetas (BOM)</div>
        <div class="page-sub">
            Lista de materiales por producto. Cada receta define ingredientes
            y cantidades para 1 batch de producción.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/recetas/nueva" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva receta
        </a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-journal-text" style="color:#4f8ef7;margin-right:6px"></i>
            Listado de recetas
        </span>
        <span class="badge badge-info"><?= count($recetas) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="text-align:left">Receta / Versión</th>
                    <th style="text-align:left">Producto</th>
                    <th style="text-align:left;width:130px">Línea</th>
                    <th style="text-align:center;width:120px">Ingredientes</th>
                    <th style="text-align:center;width:100px">Estado</th>
                    <th style="text-align:center;width:130px">Aprobada por</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:160px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recetas)): ?>
            <tr>
                <td colspan="<?= $canWrite ? 7 : 6 ?>"
                    style="text-align:center;padding:48px;color:#94a3b8">
                    <i class="bi bi-journal-text" style="font-size:32px;display:block;
                                margin-bottom:10px;color:#e2e8f0"></i>
                    No hay recetas registradas.
                    <?php if ($canWrite): ?>
                    <div style="margin-top:10px">
                        <a href="<?= APP_URL ?>/m0/recetas/nueva"
                            class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Crear la primera
                        </a>
                    </div>
                    <?php endif ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($recetas as $r): ?>
            <tr>
                <td style="text-align:left">
                    <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>"
                        style="font-weight:700;color:#4f8ef7;text-decoration:none">
                        <?= htmlspecialchars($r['nombre']) ?>
                    </a>
                    <span class="badge badge-muted" style="margin-left:6px">
                        v<?= $r['version'] ?>
                    </span>
                </td>
                <td style="text-align:left">
                    <strong><?= htmlspecialchars($r['producto_nombre']) ?></strong>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                        <?= htmlspecialchars($r['producto_codigo']) ?>
                    </div>
                </td>
                <td style="text-align:left;font-size:13px;color:#475569">
                    <?= htmlspecialchars($r['linea_nombre']) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-info">
                        <?= (int)$r['total_ingredientes'] ?>
                        <span style="font-weight:400;font-size:10px">ingred.</span>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $r['vigente'] ? 'badge-success' : 'badge-muted' ?>">
                        <?= $r['vigente'] ? 'Vigente' : 'Obsoleta' ?>
                    </span>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?= $r['aprobada_por_nombre']
                        ? htmlspecialchars($r['aprobada_por_nombre']) : '—' ?>
                    <?php if ($r['aprobada_en']): ?>
                    <div style="font-size:10px;color:#94a3b8">
                        <?= date('d/m/Y', strtotime($r['aprobada_en'])) ?>
                    </div>
                    <?php endif ?>
                </td>
                <?php if ($canWrite): ?>
                <td style="text-align:center">
                    <div style="display:flex;gap:4px;justify-content:center">
                        <!-- Ver -->
                        <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>"
                            class="btn btn-sm btn-secondary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <!-- Editar -->
                        <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>/editar"
                            class="btn btn-sm btn-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <!-- Eliminar -->
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>/eliminar"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="btn btn-sm btn-danger" title="Eliminar"
                                    data-confirm="¿Eliminar '<?= htmlspecialchars($r['nombre']) ?>'? Esta acción no se puede deshacer.">
                                <i class="bi bi-trash"></i>
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