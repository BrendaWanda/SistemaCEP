<?php
/** @var array $insumo
 *  @var array $parametros
 *  @var array $tipos
 *  @var bool  $canWrite */
?>
<?php require __DIR__ . '/../../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            Parámetros — <?= htmlspecialchars($insumo['descripcion']) ?>
        </div>
        <div class="page-sub">
            Estos parámetros son los que se pedirán al registrar una recepción de este insumo en M1.
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros/nuevo"
            class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo parámetro
        </a>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al insumo
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
            Configure los parámetros para habilitar la captura dinámica en Recepción de MP (M1).
        </div>
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros/nuevo"
            class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Agregar primer parámetro
        </a>
        <?php endif ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-sliders" style="color:#4f8ef7;margin-right:6px"></i>
            Parámetros configurados
        </span>
        <span class="badge badge-info"><?= count($parametros) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="text-align:left">Nombre</th>
                    <th style="text-align:center;width:100px">Tipo</th>
                    <th style="text-align:center;width:80px">Unidad</th>
                    <th style="text-align:center;width:90px">Mín</th>
                    <th style="text-align:center;width:90px">Máx</th>
                    <th style="text-align:center;width:80px">Req.</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:120px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parametros as $p): ?>
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
                        <?= htmlspecialchars($p['unidad']) ?: '—' ?>
                    </code>
                </td>
                <td style="text-align:center;color:#2563eb;font-weight:600">
                    <?= $p['valor_min'] !== null
                        ? number_format($p['valor_min'],3) : '—' ?>
                </td>
                <td style="text-align:center;color:#1d4ed8;font-weight:600">
                    <?= $p['valor_max'] !== null
                        ? number_format($p['valor_max'],3) : '—' ?>
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
                        <a href="<?= APP_URL ?>/m0/parametros-insumo/<?= $p['id'] ?>/editar"
                            class="btn btn-sm btn-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/parametros-insumo/<?= $p['id'] ?>/eliminar"
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
<?php endif ?>