<?php
/** @var bool $canWrite @var array $productos @var array $lineas */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">Productos</div>
        <div class="page-sub">
            Productos del contrato DBC-GAMLP con sus especificaciones técnicas.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/productos/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo producto
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Filtro por línea -->
<div class="card" style="margin-bottom:12px">
    <div class="card-body" style="padding:10px 16px">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span style="font-size:12px;font-weight:600;color:#475569">
                <i class="bi bi-funnel" style="margin-right:4px"></i>
                Filtrar:
            </span>
            <a href="<?= APP_URL ?>/m0/productos"
                class="btn btn-sm <?= !isset($_GET['linea_id']) ? 'btn-primary' : 'btn-secondary' ?>">
                Todos
            </a>
            <?php foreach ($lineas as $id => $nombre): ?>
            <a href="<?= APP_URL ?>/m0/productos?linea_id=<?= $id ?>"
                class="btn btn-sm <?= ($_GET['linea_id'] ?? '') == $id
                    ? 'btn-primary' : 'btn-secondary' ?>">
                <?= htmlspecialchars($nombre) ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-box-seam-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Listado de productos
        </span>
        <span class="badge badge-info"><?= count($productos) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="width:110px">Código</th>
                    <th>Producto</th>
                    <th>Línea / Ítem DBC</th>
                    <th style="text-align:center;width:110px">Peso nominal</th>
                    <th style="text-align:center;width:130px">LSE / LIE</th>
                    <th style="text-align:center;width:100px">Parámetros</th>
                    <th style="text-align:center;width:90px">Estado</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:190px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($productos)): ?>
            <tr>
                <td colspan="<?= $canWrite ? 8 : 7 ?>"
                    style="text-align:center;padding:48px;color:#94a3b8">
                    <i class="bi bi-box-seam" style="font-size:32px;display:block;
                            margin-bottom:10px;color:#e2e8f0"></i>
                    No hay productos registrados.
                    <?php if ($canWrite): ?>
                    <div style="margin-top:10px">
                        <a href="<?= APP_URL ?>/m0/productos/nuevo"
                            class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Crear el primero
                        </a>
                    </div>
                    <?php endif ?>
                </td>
            </tr>
            <?php else: ?>
            <?php
            $csrfToken   = \App\Core\Controller::csrfToken();
            $lineaFiltro = $_GET['linea_id'] ?? null;
            foreach ($productos as $p):
                if ($lineaFiltro && $p['linea_id'] != $lineaFiltro) continue;
            ?>
            <tr>
                <td>
                    <code style="background:#f1f5f9;padding:3px 8px;border-radius:5px;
                                font-size:12px;color:#090e22;font-weight:700">
                        <?= htmlspecialchars($p['codigo']) ?>
                    </code>
                </td>
                <td style="text-align:left">
                    <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>"
                        style="font-weight:600;color:#4f8ef7;text-decoration:none">
                        <?= htmlspecialchars($p['nombre']) ?>
                    </a>
                    <?php if ($p['lote_contrato']): ?>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                        <i class="bi bi-file-text" style="font-size:10px"></i>
                        <?= htmlspecialchars($p['lote_contrato']) ?>
                    </div>
                    <?php endif ?>
                </td>
                <td style="text-align:left">
                    <div style="font-size:13px;color:#475569">
                        <?= htmlspecialchars($p['linea_nombre']) ?>
                    </div>
                    <?php if ($p['item_dbc']): ?>
                    <span class="badge badge-info" style="margin-top:3px">
                        <?= htmlspecialchars($p['item_dbc']) ?>
                    </span>
                    <?php endif ?>
                </td>
                <td style="text-align:center;font-weight:700;font-size:15px">
                    <?= $p['peso_nominal_g']
                        ? number_format($p['peso_nominal_g'],1).' g' : '—' ?>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?php if ($p['lse_g'] && $p['lie_g']): ?>
                    <span style="color:#16a34a;font-weight:700">
                        <?= number_format($p['lse_g'],2) ?>
                    </span>
                    <span style="color:#94a3b8;font-size:11px"> / </span>
                    <span style="color:#dc2626;font-weight:700">
                        <?= number_format($p['lie_g'],2) ?>
                    </span>
                    <div style="font-size:10px;color:#94a3b8">LSE / LIE</div>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $p['parametros_spc'] > 0
                        ? 'badge-success' : 'badge-muted' ?>">
                        <?= (int)$p['parametros_spc'] ?> SPC
                    </span>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                        <?= (int)$p['total_parametros'] ?> total
                    </div>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $p['activo']
                        ? 'badge-success' : 'badge-muted' ?>">
                        <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <?php if ($canWrite): ?>
                <td style="text-align:center">
                    <div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap">

                        <!-- Ver -->
                        <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>"
                            class="btn btn-sm btn-secondary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>

                        <!-- Editar -->
                        <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>/editar"
                            class="btn btn-sm btn-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>

                        <!-- Activar / Desactivar -->
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>/toggle"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="btn btn-sm <?= $p['activo']
                                        ? 'btn-warning' : 'btn-success' ?>"
                                    title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"
                                    data-confirm="<?= $p['activo']
                                        ? '¿Desactivar '.$p['nombre'].'?'
                                        : '¿Activar '.$p['nombre'].'?' ?>">
                                <i class="bi <?= $p['activo']
                                    ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                            </button>
                        </form>

                        <!-- Eliminar -->
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>/eliminar"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="btn btn-sm btn-danger"
                                    title="Eliminar"
                                    data-confirm="¿Eliminar '<?= htmlspecialchars($p['nombre']) ?>'? Esta acción no se puede deshacer.">
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