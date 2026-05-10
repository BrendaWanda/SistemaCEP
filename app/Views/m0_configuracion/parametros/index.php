<?php
/** @var array $producto @var array $parametros @var array $etapas @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">⚙️ Parámetros — <?= htmlspecialchars($producto['nombre']) ?></div>
        <div class="page-sub">
            Peso nominal:
            <strong><?= number_format($producto['peso_nominal_g'],1) ?>g</strong> ·
            LSE: <span style="color:#15803d;font-weight:600">
                <?= number_format($producto['lse_g'],3) ?>g
            </span> ·
            LIE: <span style="color:#dc2626;font-weight:600">
                <?= number_format($producto['lie_g'],3) ?>g
            </span>
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">+ Nuevo parámetro</a>
    </div>
    <?php endif ?>
</div>

<?php if (empty($parametros)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:50px;color:#94a3b8">
        <div style="font-size:40px;margin-bottom:12px">⚙️</div>
        <div style="font-size:15px;font-weight:600;margin-bottom:6px">Sin parámetros configurados</div>
        <div style="font-size:13px;margin-bottom:16px">
            Configure los parámetros para habilitar el registro de proceso y el SPC.
        </div>
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">Agregar primer parámetro</a>
        <?php endif ?>
    </div>
</div>
<?php else: ?>

<?php foreach ($etapas as $etapaClave => $etapaNombre): ?>
<?php if (empty($parametros[$etapaClave])) continue; ?>
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#f8fafc">
        <span class="card-title">📍 <?= $etapaNombre ?></span>
        <span class="badge badge-muted"><?= count($parametros[$etapaClave]) ?> parámetros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th style="text-align:center">Tipo</th>
                    <th style="text-align:center">Unidad</th>
                    <th style="text-align:center">Nominal</th>
                    <th style="text-align:center">Mín</th>
                    <th style="text-align:center">Máx</th>
                    <th style="text-align:center">SPC</th>
                    <th style="text-align:center">Req.</th>
                    <?php if ($canWrite): ?><th></th><?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parametros[$etapaClave] as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                <td style="text-align:center">
                    <span class="badge badge-muted">
                        <?= $tipos[$p['tipo_dato']] ?? $p['tipo_dato'] ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <code style="background:#f1f5f9;padding:1px 6px;border-radius:4px">
                        <?= htmlspecialchars($p['unidad']) ?>
                    </code>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $p['valor_nominal'] !== null ? number_format($p['valor_nominal'],3) : '—' ?>
                </td>
                <td style="text-align:center;color:#dc2626;font-weight:500">
                    <?= $p['valor_min'] !== null ? number_format($p['valor_min'],3) : '—' ?>
                </td>
                <td style="text-align:center;color:#15803d;font-weight:500">
                    <?= $p['valor_max'] !== null ? number_format($p['valor_max'],3) : '—' ?>
                </td>
                <td style="text-align:center">
                    <?php if ($p['es_variable_spc']): ?>
                    <span class="badge badge-success">✓ n=<?= $p['tamanio_subgrupo'] ?></span>
                    <?php else: ?>
                    <span style="color:#94a3b8;font-size:12px">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $p['obligatorio'] ? 'badge-warning' : 'badge-muted' ?>">
                        <?= $p['obligatorio'] ? 'Sí' : 'No' ?>
                    </span>
                </td>
                <?php if ($canWrite): ?>
                <td style="text-align:right">
                    <a href="<?= APP_URL ?>/m0/parametros/<?= $p['id'] ?>/editar"
                        class="btn btn-sm btn-secondary">Editar</a>
                    <form method="POST"
                            action="<?= APP_URL ?>/m0/parametros/<?= $p['id'] ?>/eliminar"
                            style="display:inline">
                        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                        <button class="btn btn-sm btn-danger"
                                data-confirm="¿Eliminar '<?= htmlspecialchars($p['nombre']) ?>'?">
                            ✕
                        </button>
                    </form>
                </td>
                <?php endif ?>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach ?>
<?php endif ?>

<div style="margin-top:8px">
    <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>"
        class="btn btn-secondary">← Volver al producto</a>
</div>