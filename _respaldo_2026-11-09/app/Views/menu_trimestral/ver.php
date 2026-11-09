<?php
/** @var array $menu @var array $trimestres */
?>

<div class="page-header">
    <div>
        <div class="page-title">📅 <?= htmlspecialchars($menu['nombre']) ?></div>
        <div class="page-sub">
            T<?= $menu['trimestre'] ?> <?= $menu['anio'] ?> ·
            <?= date('d/m/Y', strtotime($menu['fecha_inicio'])) ?> al
            <?= date('d/m/Y', strtotime($menu['fecha_fin'])) ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/menu-trimestral/<?= $menu['id'] ?>/editar"
            class="btn btn-secondary">✏️ Editar</a>
        <a href="<?= APP_URL ?>/menu-trimestral" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<!-- Datos del menú -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <div class="card">
        <div class="card-header"><span class="card-title">📋 Datos del menú</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php foreach ([
                ['Nombre',        htmlspecialchars($menu['nombre'])],
                ['Trimestre',     'T'.$menu['trimestre'].' — '.($trimestres[$menu['trimestre']] ?? '')],
                ['Año',           $menu['anio']],
                ['Fecha inicio',  date('d/m/Y', strtotime($menu['fecha_inicio']))],
                ['Fecha fin',     date('d/m/Y', strtotime($menu['fecha_fin']))],
                ['Nivel',         htmlspecialchars($menu['nivel'] ?? '—')],
                ['Recibido de',   htmlspecialchars($menu['recibido_de'] ?? '—')],
                ['F. recepción',  $menu['fecha_recepcion']
                                    ? date('d/m/Y', strtotime($menu['fecha_recepcion']))
                                    : '—'],
                ['Estado',        $menu['activo'] ? '✅ Activo' : 'Inactivo'],
            ] as [$l,$v]): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;font-size:12px;width:40%">
                    <?= $l ?>
                </td>
                <td style="padding:7px 0;font-weight:500"><?= $v ?></td>
            </tr>
            <?php endforeach ?>
            </table>
            <?php if ($menu['obs']): ?>
            <div style="margin-top:10px;padding:8px;background:#f8fafc;
                        border-radius:6px;font-size:12px;color:#64748b">
                <strong>Obs:</strong> <?= htmlspecialchars($menu['obs']) ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Resumen por producto -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">🍞 Producción por producto</span>
        </div>
        <?php if (empty($menu['por_producto'])): ?>
        <div class="card-body text-muted text-center" style="padding:20px">
            Sin producción registrada en este período.
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table" style="font-size:12px">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:center">Lotes</th>
                        <th style="text-align:right">Und. prod.</th>
                        <th style="text-align:center">Rend.</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($menu['por_producto'] as $p):
                    $rend = (float)($p['rend_promedio'] ?? 0);
                    $colorR = $rend >= 95 ? '#15803d' : ($rend >= 85 ? '#d97706' : '#dc2626');
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($p['producto_nombre']) ?></strong>
                        <div style="font-size:10px;color:#94a3b8">
                            <?= htmlspecialchars($p['producto_codigo']) ?>
                        </div>
                    </td>
                    <td style="text-align:center"><?= (int)$p['total_lotes'] ?></td>
                    <td style="text-align:right;font-weight:700;color:#15803d">
                        <?= number_format((int)$p['und_producidas']) ?>
                    </td>
                    <td style="text-align:center;font-weight:700;color:<?= $colorR ?>">
                        <?= number_format($rend,1) ?>%
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Lotes del período -->
<?php if (!empty($menu['lotes'])): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">🏭 Lotes producidos en el período</span>
        <span class="badge badge-info"><?= count($menu['lotes']) ?> lotes</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="font-size:12px">
            <thead>
                <tr>
                    <th>Código lote</th>
                    <th>Producto</th>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Und. reales</th>
                    <th style="text-align:center">Rendimiento</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($menu['lotes'] as $l):
                $pct = (float)($l['porcentaje_rendimiento'] ?? 0);
                $colorPct = $pct >= 95 ? '#15803d' : ($pct >= 85 ? '#d97706' : '#dc2626');
                $estBadge = match($l['estado']) {
                    'liberado'   => 'badge-success',
                    'cuarentena' => 'badge-warning',
                    'en_proceso' => 'badge-info',
                    default      => 'badge-muted',
                };
            ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>"
                        style="font-family:monospace;font-weight:700;
                                color:#1d4ed8;text-decoration:none">
                        <?= htmlspecialchars($l['codigo_lote']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($l['producto_nombre']) ?></td>
                <td style="text-align:center">
                    <?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= number_format($l['rendimiento_real_total'] ?? 0) ?>
                </td>
                <td style="text-align:center;font-weight:700;color:<?= $colorPct ?>">
                    <?= $pct > 0 ? number_format($pct,1).'%' : '—' ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $estBadge ?>">
                        <?= ucfirst($l['estado']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>