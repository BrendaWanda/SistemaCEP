<?php
/** @var array $producto @var array $parametros @var array $etapas @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            Parámetros — <?= htmlspecialchars($producto['nombre']) ?>
        </div>
        <div class="page-sub" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span>
                Peso nominal:
                <strong><?= number_format($producto['peso_nominal_g'],1) ?>g</strong>
            </span>
            <span style="color:#16a34a;font-weight:600">
                <i class="bi bi-arrow-up-circle-fill" style="font-size:11px"></i>
                LSE <?= number_format($producto['lse_g'],3) ?>g
            </span>
            <span style="color:#dc2626;font-weight:600">
                <i class="bi bi-arrow-down-circle-fill" style="font-size:11px"></i>
                LIE <?= number_format($producto['lie_g'],3) ?>g
            </span>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo parámetro
        </a>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>"
            class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al producto
        </a>
    </div>
</div>

<?php if (empty($parametros)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:50px;color:#94a3b8">
        <i class="bi bi-sliders" style="font-size:40px;display:block;
                margin-bottom:12px;color:#e2e8f0"></i>
        <div style="font-size:15px;font-weight:600;margin-bottom:6px">
            Sin parámetros configurados
        </div>
        <div style="font-size:13px;margin-bottom:16px">
            Configure los parámetros para habilitar el registro de proceso y el SPC.
        </div>
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Agregar primer parámetro
        </a>
        <?php endif ?>
    </div>
</div>
<?php else: ?>

<?php foreach ($etapas as $etapaClave => $etapaNombre): ?>
<?php if (empty($parametros[$etapaClave])) continue; ?>
<div class="card" style="margin-bottom:14px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-chevron-right" style="font-size:12px;color:#4f8ef7;margin-right:6px"></i>
            <?= $etapaNombre ?>
        </span>
        <span class="badge badge-muted">
            <?= count($parametros[$etapaClave]) ?> parámetros
        </span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="text-align:left">Nombre</th>
                    <th style="text-align:center;width:100px">Tipo</th>
                    <th style="text-align:center;width:80px">Unidad</th>
                    <th style="text-align:center;width:90px">Nominal</th>
                    <th style="text-align:center;width:90px">Mín</th>
                    <th style="text-align:center;width:90px">Máx</th>
                    <th style="text-align:center;width:100px">SPC</th>
                    <th style="text-align:center;width:70px">Req.</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:120px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parametros[$etapaClave] as $p): ?>
            <tr>
                <td style="text-align:left;font-weight:600">
                    <?= htmlspecialchars($p['nombre']) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-muted">
                        <?= $tipos[$p['tipo_dato']] ?? $p['tipo_dato'] ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <code style="background:#f1f5f9;padding:2px 7px;
                                border-radius:4px;font-size:12px">
                        <?= htmlspecialchars($p['unidad']) ?>
                    </code>
                </td>
                <td style="text-align:center;font-weight:700">
                    <?= $p['valor_nominal'] !== null
                        ? number_format($p['valor_nominal'],3) : '—' ?>
                </td>
                <td style="text-align:center;color:#dc2626;font-weight:600">
                    <?= $p['valor_min'] !== null
                        ? number_format($p['valor_min'],3) : '—' ?>
                </td>
                <td style="text-align:center;color:#16a34a;font-weight:600">
                    <?= $p['valor_max'] !== null
                        ? number_format($p['valor_max'],3) : '—' ?>
                </td>
                <td style="text-align:center">
                    <?php if ($p['es_variable_spc']): ?>
                    <span class="badge badge-success"
                            style="display:inline-flex;align-items:center;gap:4px">
                        <i class="bi bi-check-circle-fill" style="font-size:10px"></i>
                        n=<?= $p['tamanio_subgrupo'] ?>
                    </span>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $p['obligatorio']
                        ? 'badge-warning' : 'badge-muted' ?>">
                        <?= $p['obligatorio'] ? 'Sí' : 'No' ?>
                    </span>
                </td>
                <?php if ($canWrite): ?>
                <td style="text-align:center">
                    <div style="display:flex;gap:4px;justify-content:center">
                        <a href="<?= APP_URL ?>/m0/parametros/<?= $p['id'] ?>/editar"
                            class="btn btn-sm btn-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/parametros/<?= $p['id'] ?>/eliminar"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button class="btn btn-sm btn-danger" title="Eliminar"
                                    data-confirm="¿Eliminar '<?= htmlspecialchars($p['nombre']) ?>'?">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
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