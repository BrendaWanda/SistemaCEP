<?php
/** @var array $insumo @var array $stock @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title"><?= htmlspecialchars($insumo['descripcion']) ?></div>
        <div class="page-sub" style="display:flex;align-items:center;gap:8px">
            <code style="background:#f1f5f9;padding:2px 8px;border-radius:5px;
                        font-size:12px;color:#090e22;font-weight:700">
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
            <table class="data-table-bordered" style="font-size:13px">
                <tbody>
                <?php foreach ([
                    ['Código',       htmlspecialchars($insumo['codigo'])],
                    ['Tipo',         $tipos[$insumo['tipo']] ?? $insumo['tipo']],
                    ['Unidad',       htmlspecialchars($insumo['unidad_medida'])],
                    ['Vida útil ref',htmlspecialchars($insumo['vida_util_referencia'] ?? '—')],
                ] as [$lbl,$val]): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;
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
            <table class="data-table-bordered" style="font-size:13px">
                <tbody>
                <?php foreach ([
                    ['Sabor / Olor', $insumo['esp_sabor_olor'] ?? '—'],
                    ['Color',        $insumo['esp_color'] ?? '—'],
                    ['Descripción',  $insumo['esp_descripcion_fisica'] ?? '—'],
                ] as [$lbl,$val]): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;
                                font-weight:600;width:40%"><?= $lbl ?></td>
                    <td style="text-align:left"><?= htmlspecialchars($val) ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Fisicoquímicas -->
<?php if ($insumo['esp_humedad_max'] || $insumo['esp_ph_min']): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-droplet-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Especificaciones fisicoquímicas
        </span>
    </div>
    <div class="card-body" style="padding:0">
        <table class="data-table-bordered" style="font-size:13px">
            <thead>
                <tr>
                    <th>Parámetro</th>
                    <th style="text-align:center">Valor</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ([
                ['Humedad máx',   $insumo['esp_humedad_max'],   '%'],
                ['Densidad mín',  $insumo['esp_densidad_min'],  ''],
                ['Densidad máx',  $insumo['esp_densidad_max'],  ''],
                ['pH mín',        $insumo['esp_ph_min'],        ''],
                ['pH máx',        $insumo['esp_ph_max'],        ''],
                ['Gluten mín',    $insumo['esp_gluten_min'],    '%'],
                ['Impurezas máx', $insumo['esp_impurezas_max'], '%'],
            ] as [$lbl,$val,$uni]):
                if ($val === null) continue;
            ?>
            <tr>
                <td style="text-align:left;color:#64748b;font-size:12px;font-weight:600">
                    <?= $lbl ?>
                </td>
                <td style="text-align:center;font-weight:700">
                    <?= number_format((float)$val, 3) ?><?= $uni ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

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
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="text-align:left">Código recepción</th>
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
                <td style="text-align:left;font-family:monospace;font-size:12px;font-weight:700">
                    <?= htmlspecialchars($s['codigo_recepcion'] ?? '—') ?>
                </td>
                <td style="text-align:left;font-size:13px">
                    <?= htmlspecialchars($s['proveedor_nombre'] ?? '—') ?>
                </td>
                <td style="text-align:center;font-weight:700;color:#16a34a">
                    <?= number_format($s['cantidad_disponible'],3) ?>
                    <?= htmlspecialchars($insumo['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
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