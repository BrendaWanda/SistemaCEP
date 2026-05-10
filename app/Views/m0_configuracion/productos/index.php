<?php
/** @var bool $canWrite @var array $productos @var array $lineas */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🍞 Productos</div>
        <div class="page-sub">Productos del contrato DBC-GAMLP con sus especificaciones técnicas.</div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/productos/nuevo" class="btn btn-primary">+ Nuevo producto</a>
    </div>
    <?php endif ?>
</div>

<!-- Filtro por línea -->
<div class="card" style="margin-bottom:12px">
    <div class="card-body" style="padding:12px 16px">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <span style="font-size:12px;font-weight:600;color:#475569">Filtrar por línea:</span>
            <a href="<?= APP_URL ?>/m0/productos" class="btn btn-sm btn-secondary">Todas</a>
            <?php foreach ($lineas as $id => $nombre): ?>
            <a href="<?= APP_URL ?>/m0/productos?linea_id=<?= $id ?>"
                class="btn btn-sm btn-outline"><?= htmlspecialchars($nombre) ?></a>
            <?php endforeach ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Línea / Ítem DBC</th>
                        <th style="text-align:center">Peso nominal</th>
                        <th style="text-align:center">LSE / LIE</th>
                        <th style="text-align:center">Parámetros SPC</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:40px">
                        No hay productos.
                        <a href="<?= APP_URL ?>/m0/productos/nuevo">Crear el primero</a>
                    </td></tr>
                <?php else: ?>
                <?php
                $lineaFiltro = $_GET['linea_id'] ?? null;
                foreach ($productos as $p):
                    if ($lineaFiltro && $p['linea_id'] != $lineaFiltro) continue;
                ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($p['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>"
                            style="font-weight:600;color:#1d4ed8;text-decoration:none">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </a>
                        <?php if ($p['lote_contrato']): ?>
                        <div style="font-size:10px;color:#94a3b8">
                            <?= htmlspecialchars($p['lote_contrato']) ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td>
                        <div style="font-size:12px"><?= htmlspecialchars($p['linea_nombre']) ?></div>
                        <?php if ($p['item_dbc']): ?>
                        <span class="badge badge-info"><?= htmlspecialchars($p['item_dbc']) ?></span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center;font-weight:700">
                        <?= $p['peso_nominal_g'] ? number_format($p['peso_nominal_g'],1).'g' : '—' ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?php if ($p['lse_g'] && $p['lie_g']): ?>
                        <span style="color:#15803d;font-weight:600"><?= number_format($p['lse_g'],2) ?></span>
                        /
                        <span style="color:#dc2626;font-weight:600"><?= number_format($p['lie_g'],2) ?></span>
                        <?php else: ?>—<?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $p['parametros_spc'] > 0 ? 'badge-success' : 'badge-muted' ?>">
                            <?= (int)$p['parametros_spc'] ?> SPC
                            / <?= (int)$p['total_parametros'] ?> total
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $p['activo'] ? 'badge-success' : 'badge-muted' ?>">
                            <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m0/productos/<?= $p['id'] ?>/editar"
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