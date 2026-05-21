<?php
/** @var array $receta @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title"><?= htmlspecialchars($receta['nombre']) ?></div>
        <div class="page-sub" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="badge badge-muted">v<?= $receta['version'] ?></span>
            <span><?= htmlspecialchars($receta['producto_nombre']) ?></span>
            <?php if ($receta['unidades_por_receta']): ?>
            <span>· <?= $receta['unidades_por_receta'] ?> und/batch</span>
            <?php endif ?>
            <span class="badge <?= $receta['vigente'] ? 'badge-success' : 'badge-muted' ?>">
                <?= $receta['vigente'] ? 'Vigente' : 'Obsoleta' ?>
            </span>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/recetas/<?= $receta['id'] ?>/editar"
            class="btn btn-secondary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <form method="POST"
                action="<?= APP_URL ?>/m0/recetas/<?= $receta['id'] ?>/eliminar"
                style="display:inline">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <button type="submit" class="btn btn-danger"
                    data-confirm="¿Eliminar '<?= htmlspecialchars($receta['nombre']) ?>'?">
                <i class="bi bi-trash"></i> Eliminar
            </button>
        </form>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m0/recetas" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">
            <i class="bi bi-boxes" style="color:#2563eb;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value"><?= count($receta['ingredientes']) ?></div>
            <div class="stat-label">Ingredientes totales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7">
            <i class="bi bi-star-fill" style="color:#d97706;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value">
                <?= count(array_filter($receta['ingredientes'],
                    fn($i) => $i['es_critico'])) ?>
            </div>
            <div class="stat-label">Ingredientes críticos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4">
            <i class="bi bi-tag-fill" style="color:#16a34a;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value">v<?= $receta['version'] ?></div>
            <div class="stat-label">
                <?= $receta['vigente'] ? 'Vigente' : 'Obsoleta' ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb">
            <i class="bi bi-building-fill-gear" style="color:#d97706;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value"><?= $receta['unidades_por_receta'] ?? '—' ?></div>
            <div class="stat-label">Unidades por batch</div>
        </div>
    </div>
</div>

<!-- BOM -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-list-check" style="color:#4f8ef7;margin-right:6px"></i>
            Lista de materiales (BOM)
        </span>
        <span class="badge <?= $receta['vigente'] ? 'badge-success' : 'badge-muted' ?>">
            <?= $receta['vigente'] ? 'Receta vigente' : 'Receta obsoleta' ?>
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="width:100px">Código</th>
                    <th style="text-align:left">Insumo</th>
                    <th style="text-align:center;width:80px">Tipo</th>
                    <th style="text-align:right;width:160px">Cantidad (1 batch)</th>
                    <th style="text-align:center;width:100px">Crítico</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $tipoBadges = [
                'materia_prima'    => ['badge-info',   'MP'],
                'material_empaque' => ['badge-purple', 'ME'],
                'insumo_proceso'   => ['badge-warning','IP'],
            ];
            foreach ($receta['ingredientes'] as $ing):
                $tb = $tipoBadges[$ing['insumo_tipo']] ?? ['badge-muted', '—'];
            ?>
            <tr style="<?= $ing['es_critico'] ? 'background:#fffbeb' : '' ?>">
                <td>
                    <code style="background:#f1f5f9;padding:3px 8px;border-radius:5px;
                                font-size:12px;color:#090e22;font-weight:700">
                        <?= htmlspecialchars($ing['insumo_codigo']) ?>
                    </code>
                </td>
                <td style="text-align:left">
                    <strong><?= htmlspecialchars($ing['insumo_nombre']) ?></strong>
                    <?php if ($ing['es_critico']): ?>
                    <span class="badge badge-warning"
                            style="margin-left:6px;font-size:9px">
                        <i class="bi bi-star-fill" style="font-size:9px"></i> Crítico
                    </span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $tb[0] ?>"><?= $tb[1] ?></span>
                </td>
                <td style="text-align:right;font-weight:700;font-size:15px">
                    <?= number_format((float)$ing['cantidad'], 3) ?>
                    <span style="font-weight:400;font-size:12px;color:#64748b">
                        <?= htmlspecialchars($ing['unidad_medida']) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <?php if ($ing['es_critico']): ?>
                    <span class="badge badge-warning">
                        <i class="bi bi-star-fill" style="font-size:10px"></i> Sí
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php if ($receta['descripcion']): ?>
    <div style="padding:12px 20px;border-top:1px solid #f1f5f9;
                font-size:13px;color:#64748b;display:flex;gap:6px;align-items:flex-start">
        <i class="bi bi-info-circle" style="margin-top:1px;flex-shrink:0"></i>
        <span><?= htmlspecialchars($receta['descripcion']) ?></span>
    </div>
    <?php endif ?>
</div>