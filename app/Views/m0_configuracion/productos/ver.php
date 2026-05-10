<?php
/** @var array $producto @var array $parametros @var array $etapas @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">🍞 <?= htmlspecialchars($producto['nombre']) ?></div>
        <div class="page-sub">
            <?= htmlspecialchars($producto['linea_nombre']) ?>
            <?php if ($producto['item_dbc']): ?>
            · <span class="badge badge-info"><?= htmlspecialchars($producto['item_dbc']) ?></span>
            <?php endif ?>
            · <code><?= htmlspecialchars($producto['codigo']) ?></code>
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/editar"
            class="btn btn-secondary">✏️ Editar producto</a>
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
            class="btn btn-primary">+ Nuevo parámetro</a>
    </div>
    <?php endif ?>
</div>

<!-- Especificaciones y organolépticos -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <div class="card">
        <div class="card-header">
            <span class="card-title">📏 Especificaciones contractuales (DBC)</span>
        </div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php
            $filas = [
                ['Peso nominal',        'peso_nominal_g',          'g'],
                ['Tolerancia',          'tolerancia_pct',          '%'],
                ['LSE',                 'lse_g',                   'g'],
                ['LIE',                 'lie_g',                   'g'],
                ['Vida útil',           'vida_util_dias',          'días'],
                ['Und. por receta',     'unidades_por_receta',     'und'],
                ['Und. por bolsa',      'unidades_por_bolsa',      'und'],
                ['Bolsas por caja',     'bolsas_por_caja',         'bolsas'],
                ['T° conserv. mín',     'temperatura_conserv_min', '°C'],
                ['T° conserv. máx',     'temperatura_conserv_max', '°C'],
                ['T° entrega máx',      'temperatura_entrega_max', '°C'],
            ];
            foreach ($filas as [$label, $campo, $unidad]):
                if ($producto[$campo] === null) continue;
            ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;font-size:12px"><?= $label ?></td>
                <td style="padding:7px 0;font-weight:600;text-align:right">
                    <?php if (in_array($campo, ['lse_g','lie_g'])): ?>
                    <span style="color:<?= $campo==='lse_g' ? '#15803d' : '#dc2626' ?>">
                        <?= number_format((float)$producto[$campo],3) ?> <?= $unidad ?>
                    </span>
                    <?php else: ?>
                    <?= number_format((float)$producto[$campo],2) ?> <?= $unidad ?>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">👁️ Referencia organoléptica y fisicoquímica</span>
        </div>
        <div class="card-body">
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
            <div style="margin-bottom:10px">
                <div style="font-size:10px;font-weight:700;color:#64748b;
                            text-transform:uppercase;letter-spacing:.05em">
                    <?= $label ?>
                </div>
                <div style="font-size:13px"><?= htmlspecialchars($producto[$campo]) ?></div>
            </div>
            <?php endforeach ?>
            <?php if ($producto['ref_humedad_max_pct'] || $producto['ref_ph_min']): ?>
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9">
                <div style="font-size:10px;font-weight:700;color:#64748b;
                            text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">
                    Fisicoquímico PT
                </div>
                <?php if ($producto['ref_humedad_max_pct']): ?>
                <div style="font-size:12px;margin-bottom:4px">
                    Humedad máx: <strong><?= $producto['ref_humedad_max_pct'] ?>%</strong>
                </div>
                <?php endif ?>
                <?php if ($producto['ref_ph_min'] && $producto['ref_ph_max']): ?>
                <div style="font-size:12px">
                    pH: <strong><?= $producto['ref_ph_min'] ?> – <?= $producto['ref_ph_max'] ?></strong>
                </div>
                <?php endif ?>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Parámetros de proceso agrupados por etapa -->
<div class="card">
    <div class="card-header">
        <span class="card-title">⚙️ Parámetros de control del proceso</span>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge badge-info"><?= (int)$producto['total_parametros'] ?> total</span>
            <span class="badge badge-success"><?= (int)$producto['parametros_spc'] ?> SPC</span>
            <?php if ($canWrite): ?>
            <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo"
                class="btn btn-sm btn-primary">+ Agregar</a>
            <?php endif ?>
        </div>
    </div>

    <?php if (empty($parametros)): ?>
    <div class="card-body text-center text-muted" style="padding:30px">
        Sin parámetros. <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros/nuevo">
        Agregar el primero</a>
    </div>
    <?php else: ?>
    <?php foreach ($etapas as $etapaClave => $etapaNombre): ?>
    <?php if (empty($parametros[$etapaClave])) continue; ?>
    <div style="border-top:1px solid #f1f5f9">
        <div style="padding:8px 20px;background:#f8fafc;font-size:11px;font-weight:700;
                    text-transform:uppercase;letter-spacing:.05em;color:#475569">
            <?= $etapaNombre ?>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:#fafbfd">
                    <th style="padding:8px 16px;text-align:left;font-size:11px;
                                color:#94a3b8;font-weight:600">Parámetro</th>
                    <th style="padding:8px;text-align:center;font-size:11px;
                                color:#94a3b8;font-weight:600">Unidad</th>
                    <th style="padding:8px;text-align:center;font-size:11px;
                                color:#94a3b8;font-weight:600">Nominal</th>
                    <th style="padding:8px;text-align:center;font-size:11px;
                                color:#94a3b8;font-weight:600">Mín / Máx</th>
                    <th style="padding:8px;text-align:center;font-size:11px;
                                color:#94a3b8;font-weight:600">SPC</th>
                    <th style="padding:8px;text-align:center;font-size:11px;
                                color:#94a3b8;font-weight:600">Req.</th>
                    <?php if ($canWrite): ?>
                    <th style="padding:8px"></th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parametros[$etapaClave] as $param): ?>
            <tr style="border-bottom:1px solid #f8fafc">
                <td style="padding:9px 16px;font-weight:500">
                    <?= htmlspecialchars($param['nombre']) ?>
                </td>
                <td style="padding:9px 8px;text-align:center">
                    <code style="font-size:11px"><?= htmlspecialchars($param['unidad']) ?></code>
                </td>
                <td style="padding:9px 8px;text-align:center;font-weight:600">
                    <?= $param['valor_nominal'] !== null
                        ? number_format($param['valor_nominal'],3) : '—' ?>
                </td>
                <td style="padding:9px 8px;text-align:center;font-size:12px">
                    <?php if ($param['valor_min'] !== null || $param['valor_max'] !== null): ?>
                    <span style="color:#dc2626">
                        <?= $param['valor_min'] !== null ? number_format($param['valor_min'],3) : '—' ?>
                    </span>
                    /
                    <span style="color:#15803d">
                        <?= $param['valor_max'] !== null ? number_format($param['valor_max'],3) : '—' ?>
                    </span>
                    <?php else: ?>—<?php endif ?>
                </td>
                <td style="padding:9px 8px;text-align:center">
                    <?php if ($param['es_variable_spc']): ?>
                    <span class="badge badge-success">✓ n=<?= $param['tamanio_subgrupo'] ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif ?>
                </td>
                <td style="padding:9px 8px;text-align:center">
                    <span class="badge <?= $param['obligatorio'] ? 'badge-warning' : 'badge-muted' ?>">
                        <?= $param['obligatorio'] ? 'Sí' : 'No' ?>
                    </span>
                </td>
                <?php if ($canWrite): ?>
                <td style="padding:9px 8px;text-align:right">
                    <a href="<?= APP_URL ?>/m0/parametros/<?= $param['id'] ?>/editar"
                        class="btn btn-sm btn-secondary">Editar</a>
                    <form method="POST"
                            action="<?= APP_URL ?>/m0/parametros/<?= $param['id'] ?>/eliminar"
                            style="display:inline">
                        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                        <button class="btn btn-sm btn-danger"
                                data-confirm="¿Eliminar '<?= htmlspecialchars($param['nombre']) ?>'?">
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
    <?php endforeach ?>
    <?php endif ?>
</div>

<div style="margin-top:8px">
    <a href="<?= APP_URL ?>/m0/productos" class="btn btn-secondary">← Volver a productos</a>
</div>