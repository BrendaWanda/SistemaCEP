<?php
/** @var array $receta @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📋 <?= htmlspecialchars($receta['nombre']) ?></div>
        <div class="page-sub">
            Versión <?= $receta['version'] ?> ·
            <?= htmlspecialchars($receta['producto_nombre']) ?>
            <?php if ($receta['unidades_por_receta']): ?>
            · <?= $receta['unidades_por_receta'] ?> und/batch
            <?php endif ?>
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/recetas/<?= $receta['id'] ?>/editar"
           class="btn btn-secondary">✏️ Editar</a>
    </div>
    <?php endif ?>
</div>

<!-- Resumen -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">📦</div>
        <div>
            <div class="stat-value"><?= count($receta['ingredientes']) ?></div>
            <div class="stat-label">Ingredientes totales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⭐</div>
        <div>
            <div class="stat-value">
                <?= count(array_filter($receta['ingredientes'], fn($i) => $i['es_critico'])) ?>
            </div>
            <div class="stat-label">Ingredientes críticos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">🏷️</div>
        <div>
            <div class="stat-value">v<?= $receta['version'] ?></div>
            <div class="stat-label">
                Versión <?= $receta['vigente'] ? '(vigente)' : '(obsoleta)' ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">🍞</div>
        <div>
            <div class="stat-value"><?= $receta['unidades_por_receta'] ?? '—' ?></div>
            <div class="stat-label">Unidades por batch</div>
        </div>
    </div>
</div>

<!-- Tabla BOM -->
<div class="card">
    <div class="card-header">
        <span class="card-title">🧾 Lista de materiales (BOM)</span>
        <span class="badge <?= $receta['vigente'] ? 'badge-success' : 'badge-muted' ?>">
            <?= $receta['vigente'] ? '✓ Receta vigente' : 'Receta obsoleta' ?>
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th style="text-align:center">Tipo</th>
                    <th style="text-align:right">Cantidad (1 batch)</th>
                    <th style="text-align:center">Crítico</th>
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
                    <code style="background:#f1f5f9;padding:2px 6px;
                                 border-radius:4px;font-size:11px">
                        <?= htmlspecialchars($ing['insumo_codigo']) ?>
                    </code>
                </td>
                <td>
                    <strong><?= htmlspecialchars($ing['insumo_nombre']) ?></strong>
                    <?php if ($ing['es_critico']): ?>
                    <span class="badge badge-warning" style="margin-left:6px;font-size:9px">
                        ⭐ CRÍTICO
                    </span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $tb[0] ?>"><?= $tb[1] ?></span>
                </td>
                <td style="text-align:right;font-weight:700;font-size:14px">
                    <?= number_format((float)$ing['cantidad'], 3) ?>
                    <span style="font-weight:400;font-size:12px;color:#64748b">
                        <?= htmlspecialchars($ing['unidad_medida']) ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <?= $ing['es_critico']
                        ? '<span class="badge badge-warning">⭐ Sí</span>'
                        : '<span style="color:#94a3b8;font-size:12px">—</span>' ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php if ($receta['descripcion']): ?>
    <div style="padding:12px 20px;border-top:1px solid #f1f5f9;
                font-size:12px;color:#64748b">
        <strong>Notas:</strong> <?= htmlspecialchars($receta['descripcion']) ?>
    </div>
    <?php endif ?>
</div>

<div style="margin-top:8px">
    <a href="<?= APP_URL ?>/m0/recetas" class="btn btn-secondary">← Volver a recetas</a>
</div>