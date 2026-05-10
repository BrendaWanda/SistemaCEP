<?php
/** @var array $recetas @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📋 Recetas (BOM)</div>
        <div class="page-sub">
            Lista de materiales por producto. Cada receta define ingredientes
            y cantidades para 1 batch de producción.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/recetas/nueva" class="btn btn-primary">+ Nueva receta</a>
    </div>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Receta / Versión</th>
                        <th>Producto</th>
                        <th>Línea</th>
                        <th style="text-align:center">Ingredientes</th>
                        <th style="text-align:center">Estado</th>
                        <th style="text-align:center">Aprobada por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recetas)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:40px">
                        No hay recetas registradas.
                        <a href="<?= APP_URL ?>/m0/recetas/nueva">Crear la primera</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recetas as $r): ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>"
                            style="font-weight:700;color:#1d4ed8;text-decoration:none">
                            <?= htmlspecialchars($r['nombre']) ?>
                        </a>
                        <span class="badge badge-muted" style="margin-left:6px">
                            v<?= $r['version'] ?>
                        </span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($r['producto_nombre']) ?></strong>
                        <div style="font-size:11px;color:#94a3b8">
                            <?= htmlspecialchars($r['producto_codigo']) ?>
                        </div>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($r['linea_nombre']) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-info">
                            <?= (int)$r['total_ingredientes'] ?> ingredientes
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $r['vigente'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $r['vigente'] ? '✓ Vigente' : 'Obsoleta' ?>
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
                    <td>
                        <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m0/recetas/<?= $r['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>