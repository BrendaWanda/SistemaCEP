<?php
/** @var array $insumo
 *  @var array $stock
 *  @var array $parametros
 *  @var array $tipos
 *  @var array $tiposParam
 *  @var bool  $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title"><?= htmlspecialchars($insumo['descripcion']) ?></div>
        <div class="page-sub" style="display:flex;align-items:center;gap:8px">
            <code style="background:#f1f5f9;padding:2px 8px;border-radius:5px;
                        font-size:var(--fs-dense-xs);color:#090e22;font-weight:700">
                <?= htmlspecialchars($insumo['codigo']) ?>
            </code>
            <span class="badge badge-info">
                <?= $tipos[$insumo['tipo']] ?? $insumo['tipo'] ?>
            </span>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/editar"
            class="btn btn-secondary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros"
            class="btn btn-primary">
            <i class="bi bi-sliders"></i> Parámetros
        </a>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m0/insumos" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Datos generales -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-card-text" style="color:#4f8ef7;margin-right:6px"></i>
                Datos generales
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
                <tbody>
                <?php foreach ([
                    ['Código',       htmlspecialchars($insumo['codigo'])],
                    ['Tipo',         $tipos[$insumo['tipo']] ?? $insumo['tipo']],
                    ['Unidad',       htmlspecialchars($insumo['unidad_medida'])],
                    ['Vida útil ref',htmlspecialchars($insumo['vida_util_referencia'] ?? '—')],
                ] as [$lbl,$val]): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:var(--fs-dense-xs);
                                font-weight:600;width:45%"><?= $lbl ?></td>
                    <td style="text-align:left;font-weight:600"><?= $val ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Especificaciones organolépticas -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-eye-fill" style="color:#4f8ef7;margin-right:6px"></i>
                Especificaciones organolépticas
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
                <tbody>
                <?php foreach ([
                    ['Sabor / Olor', $insumo['esp_sabor_olor'] ?? '—'],
                    ['Color',        $insumo['esp_color'] ?? '—'],
                    ['Descripción',  $insumo['esp_descripcion_fisica'] ?? '—'],
                ] as [$lbl,$val]): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:var(--fs-dense-xs);
                                font-weight:600;width:40%"><?= $lbl ?></td>
                    <td style="text-align:left"><?= htmlspecialchars($val) ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- AGREGADO: reemplaza la sección fija de fisicoquímicas — ahora
     muestra los parámetros configurados dinámicamente por insumo,
     igual que Producto muestra sus parámetros de proceso. -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-sliders" style="color:#4f8ef7;margin-right:6px"></i>
            Parámetros fisicoquímicos configurados
        </span>
        <div style="display:flex;gap:6px;align-items:center">
            <span class="badge badge-info"><?= count($parametros) ?> parámetros</span>
            <?php if ($canWrite): ?>
            <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros/nuevo"
                class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Agregar
            </a>
            <?php endif ?>
        </div>
    </div>
    <?php if (empty($parametros)): ?>
    <div class="card-body text-center" style="padding:30px;color:#94a3b8">
        <i class="bi bi-sliders" style="font-size:28px;display:block;
                    margin-bottom:8px;color:#e2e8f0"></i>
        Sin parámetros configurados para este insumo.
        <?php if ($canWrite): ?>
        <div style="margin-top:10px">
            <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros/nuevo"
                class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Agregar el primero
            </a>
        </div>
        <?php endif ?>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
            <thead>
                <tr>
                    <th style="text-align:left">Parámetro</th>
                    <th style="text-align:center">Tipo</th>
                    <th style="text-align:center">Unidad</th>
                    <th style="text-align:center">Mín / Máx</th>
                    <th style="text-align:center">Obligatorio</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parametros as $p): ?>
            <tr>
                <td style="text-align:left;font-weight:600"><?= htmlspecialchars($p['nombre']) ?></td>
                <td style="text-align:center">
                    <span class="badge badge-muted"><?= $tiposParam[$p['tipo_dato']] ?? $p['tipo_dato'] ?></span>
                </td>
                <td style="text-align:center">
                    <code style="font-size:11px;background:#f1f5f9;padding:1px 6px;border-radius:4px">
                        <?= htmlspecialchars($p['unidad']) ?: '—' ?>
                    </code>
                </td>
                <td style="text-align:center;font-size:var(--fs-dense-xs)">
                    <?php if ($p['valor_min'] !== null || $p['valor_max'] !== null): ?>
                    <span style="color:#2563eb;font-weight:600">
                        <?= $p['valor_min'] !== null ? number_format($p['valor_min'],3) : '—' ?>
                    </span>
                    <span style="color:#94a3b8"> / </span>
                    <span style="color:#1d4ed8;font-weight:600">
                        <?= $p['valor_max'] !== null ? number_format($p['valor_max'],3) : '—' ?>
                    </span>
                    <?php else: ?>—<?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $p['obligatorio'] ? 'badge-warning' : 'badge-muted' ?>">
                        <?= $p['obligatorio'] ? 'Sí' : 'No' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- Stock disponible -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-boxes" style="color:#4f8ef7;margin-right:6px"></i>
            Stock disponible
        </span>
        <span class="badge badge-info"><?= count($stock) ?> lotes</span>
    </div>
    <?php if (empty($stock)): ?>
    <div class="card-body text-center" style="padding:30px;color:#94a3b8">
        <i class="bi bi-inbox" style="font-size:28px;display:block;
                    margin-bottom:8px;color:#e2e8f0"></i>
        Sin stock disponible actualmente.
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
            <thead>
                <tr>
                    <th style="text-align:left">Lote proveedor</th>
                    <th style="text-align:left">Proveedor</th>
                    <th style="text-align:center">Cantidad</th>
                    <th style="text-align:center">Vencimiento</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stock as $s):
                $dias  = $s['fecha_vencimiento']
                    ? (int)((strtotime($s['fecha_vencimiento']) - time()) / 86400)
                    : null;
                $color = $dias === null ? '#94a3b8'
                    : ($dias < 0 ? '#dc2626' : ($dias < 30 ? '#d97706' : '#16a34a'));
            ?>
            <tr>
                <td style="text-align:left;font-family:monospace;font-size:var(--fs-dense-xs);font-weight:700">
                    <?= htmlspecialchars($s['lote_proveedor'] ?? '—') ?>
                </td>
                <td style="text-align:left;font-size:var(--fs-dense-sm)">
                    <?= htmlspecialchars($s['proveedor_nombre'] ?? '—') ?>
                </td>
                <td style="text-align:center;font-weight:700;color:#16a34a">
                    <?= number_format($s['cantidad_disponible'],3) ?>
                    <?= htmlspecialchars($insumo['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:var(--fs-dense-xs)">
                    <?php if ($s['fecha_vencimiento']): ?>
                    <span style="color:<?= $color ?>;font-weight:600">
                        <?= date('d/m/Y', strtotime($s['fecha_vencimiento'])) ?>
                    </span>
                    <?php if ($dias !== null && $dias < 30): ?>
                    <div style="font-size:10px;color:<?= $color ?>">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?= $dias < 0 ? 'Vencido' : 'En '.$dias.'d' ?>
                    </div>
                    <?php endif ?>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-success">Disponible</span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>