<?php
/** @var array $producto @var array $parametros @var array $etapas @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= htmlspecialchars($producto['nombre']) ?>
        </div>
        <div class="page-sub" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span><?= htmlspecialchars($producto['linea_nombre']) ?></span>
            <?php if ($producto['item_dbc']): ?>
            <span class="badge badge-info">
                <?= htmlspecialchars($producto['item_dbc']) ?>
            </span>
            <?php endif ?>
            <code style="background:#f1f5f9;padding:2px 8px;border-radius:5px;
                        font-size:12px;color:#090e22;font-weight:700">
                <?= htmlspecialchars($producto['codigo']) ?>
            </code>
        </div>
    </div>
    <div class="page-actions">
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/editar"
            class="btn btn-secondary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo parámetro
        </a>
        <?php endif ?>
        <a href="<?= APP_URL ?>/m0/productos" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Especificaciones y organolépticos -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Especificaciones DBC -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-rulers" style="color:#4f8ef7;margin-right:6px"></i>
                Especificaciones contractuales (DBC)
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data-table-bordered" style="font-size:13px">
                <tbody>
                <?php
                $filas = [
                    ['Peso nominal',    'peso_nominal_g',          'g'],
                    ['Tolerancia',      'tolerancia_pct',          '%'],
                    ['LSE',             'lse_g',                   'g'],
                    ['LIE',             'lie_g',                   'g'],
                    ['Vida útil',       'vida_util_dias',          'días'],
                    ['Und. por receta', 'unidades_por_receta',     'und'],
                    ['Und. por bolsa',  'unidades_por_bolsa',      'und'],
                    ['Bolsas por caja', 'bolsas_por_caja',         'bolsas'],
                    ['T° conserv. mín', 'temperatura_conserv_min', '°C'],
                    ['T° conserv. máx', 'temperatura_conserv_max', '°C'],
                    ['T° entrega máx',  'temperatura_entrega_max', '°C'],
                ];
                foreach ($filas as [$label, $campo, $unidad]):
                    if ($producto[$campo] === null) continue;
                ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;
                            font-weight:600;width:50%">
                        <?= $label ?>
                    </td>
                    <td style="text-align:right;font-weight:700">
                        <?php if ($campo === 'lse_g'): ?>
                        <span style="color:#16a34a">
                            <?= number_format((float)$producto[$campo],3) ?> <?= $unidad ?>
                        </span>
                        <?php elseif ($campo === 'lie_g'): ?>
                        <span style="color:#dc2626">
                            <?= number_format((float)$producto[$campo],3) ?> <?= $unidad ?>
                        </span>
                        <?php else: ?>
                        <?= number_format((float)$producto[$campo],2) ?> <?= $unidad ?>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Organolépticos -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-eye-fill" style="color:#4f8ef7;margin-right:6px"></i>
                Referencia organoléptica y fisicoquímica
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <table class="data-table-bordered" style="font-size:13px">
                <tbody>
                <?php
                $org = [
                    'Color'      => 'ref_color',
                    'Olor'       => 'ref_olor',
                    'Sabor'      => 'ref_sabor',
                    'Textura'    => 'ref_textura',
                    'Apariencia' => 'ref_apariencia',
                ];
                foreach ($org as $label => $campo):
                    if (!$producto[$campo]) continue;
                ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;
                            font-weight:600;width:40%">
                        <?= $label ?>
                    </td>
                    <td style="text-align:left;font-weight:500">
                        <?= htmlspecialchars($producto[$campo]) ?>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php if ($producto['ref_humedad_max_pct']): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;font-weight:600">
                        Humedad máx
                    </td>
                    <td style="text-align:left;font-weight:500">
                        <?= $producto['ref_humedad_max_pct'] ?>%
                    </td>
                </tr>
                <?php endif ?>
                <?php if ($producto['ref_ph_min'] && $producto['ref_ph_max']): ?>
                <tr>
                    <td style="text-align:left;color:#64748b;font-size:12px;font-weight:600">
                        pH
                    </td>
                    <td style="text-align:left;font-weight:500">
                        <?= $producto['ref_ph_min'] ?> – <?= $producto['ref_ph_max'] ?>
                    </td>
                </tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Parámetros de proceso -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-sliders" style="color:#4f8ef7;margin-right:6px"></i>
            Parámetros de control del proceso
        </span>
        <div style="display:flex;gap:6px;align-items:center">
            <span class="badge badge-info">
                <?= (int)$producto['total_parametros'] ?> total
            </span>
            <span class="badge badge-success">
                <?= (int)$producto['parametros_spc'] ?> SPC
            </span>
            <?php if ($canWrite): ?>
            <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
                class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Agregar
            </a>
            <?php endif ?>
        </div>
    </div>

    <?php if (empty($parametros)): ?>
    <div class="card-body text-center" style="padding:40px;color:#94a3b8">
        <i class="bi bi-sliders" style="font-size:32px;display:block;
                margin-bottom:10px;color:#e2e8f0"></i>
        Sin parámetros registrados.
        <?php if ($canWrite): ?>
        <div style="margin-top:10px">
            <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
                class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Agregar el primero
            </a>
        </div>
        <?php endif ?>
    </div>
    <?php else: ?>

    <?php foreach ($etapas as $etapaClave => $etapaNombre): ?>
    <?php if (empty($parametros[$etapaClave])) continue; ?>
    <div style="border-top:1px solid #f1f5f9">
        <!-- Encabezado de etapa -->
        <div style="padding:8px 20px;background:#f0f2f8;
                    display:flex;align-items:center;gap:8px">
            <i class="bi bi-chevron-right" style="font-size:11px;color:#64748b"></i>
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;
                        letter-spacing:.06em;color:#475569">
                <?= $etapaNombre ?>
            </span>
            <span class="badge badge-muted" style="font-size:10px">
                <?= count($parametros[$etapaClave]) ?>
            </span>
        </div>
        <div class="table-wrap">
            <table class="data-table-bordered" style="font-size:13px">
                <thead>
                    <tr>
                        <th style="text-align:left">Parámetro</th>
                        <th style="text-align:center;width:70px">Unidad</th>
                        <th style="text-align:center;width:90px">Nominal</th>
                        <th style="text-align:center;width:120px">Mín / Máx</th>
                        <th style="text-align:center;width:100px">SPC</th>
                        <th style="text-align:center;width:80px">Req.</th>
                        <?php if ($canWrite): ?>
                        <th style="text-align:center;width:120px">Acciones</th>
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($parametros[$etapaClave] as $param): ?>
                <tr>
                    <td style="text-align:left;font-weight:600">
                        <?= htmlspecialchars($param['nombre']) ?>
                    </td>
                    <td style="text-align:center">
                        <code style="font-size:11px;background:#f1f5f9;
                                    padding:1px 6px;border-radius:4px">
                            <?= htmlspecialchars($param['unidad']) ?>
                        </code>
                    </td>
                    <td style="text-align:center;font-weight:700">
                        <?= $param['valor_nominal'] !== null
                            ? number_format($param['valor_nominal'],3) : '—' ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?php if ($param['valor_min'] !== null || $param['valor_max'] !== null): ?>
                        <span style="color:#dc2626;font-weight:600">
                            <?= $param['valor_min'] !== null
                                ? number_format($param['valor_min'],3) : '—' ?>
                        </span>
                        <span style="color:#94a3b8"> / </span>
                        <span style="color:#16a34a;font-weight:600">
                            <?= $param['valor_max'] !== null
                                ? number_format($param['valor_max'],3) : '—' ?>
                        </span>
                        <?php else: ?>—<?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <?php if ($param['es_variable_spc']): ?>
                        <span class="badge badge-success">
                            <i class="bi bi-check-circle-fill" style="font-size:10px"></i>
                            n=<?= $param['tamanio_subgrupo'] ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8">—</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $param['obligatorio']
                            ? 'badge-warning' : 'badge-muted' ?>">
                            <?= $param['obligatorio'] ? 'Sí' : 'No' ?>
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td style="text-align:center">
                        <div style="display:flex;gap:4px;justify-content:center">
                            <a href="<?= APP_URL ?>/m0/parametros/<?= $param['id'] ?>/editar"
                                class="btn btn-sm btn-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST"
                                    action="<?= APP_URL ?>/m0/parametros/<?= $param['id'] ?>/eliminar"
                                    style="display:inline">
                                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                <button class="btn btn-sm btn-danger"
                                        data-confirm="¿Eliminar '<?= htmlspecialchars($param['nombre']) ?>'?">
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
</div>