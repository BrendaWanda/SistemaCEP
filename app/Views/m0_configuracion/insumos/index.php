<?php
/** @var array $insumos @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📦 Catálogo de Insumos</div>
        <div class="page-sub">
            Materias primas, materiales de empaque e insumos de proceso con stock disponible.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/insumos/nuevo" class="btn btn-primary">+ Nuevo insumo</a>
    </div>
    <?php endif ?>
</div>

<!-- Filtro por tipo -->
<div class="card" style="margin-bottom:12px">
    <div class="card-body" style="padding:12px 16px">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span style="font-size:12px;font-weight:600;color:#475569">Filtrar:</span>
            <a href="<?= APP_URL ?>/m0/insumos"
                class="btn btn-sm btn-secondary">Todos</a>
            <?php foreach ($tipos as $key => $label): ?>
            <a href="<?= APP_URL ?>/m0/insumos?tipo=<?= $key ?>"
                class="btn btn-sm <?= ($_GET['tipo'] ?? '') === $key
                    ? 'btn-primary' : 'btn-outline' ?>">
                <?= $label ?>
            </a>
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
                        <th>Descripción</th>
                        <th style="text-align:center">Tipo</th>
                        <th style="text-align:center">Unidad</th>
                        <th style="text-align:center">Stock disponible</th>
                        <th style="text-align:center">Próx. vencimiento</th>
                        <th style="text-align:center">Lotes</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $filtroTipo = $_GET['tipo'] ?? '';
                $hayResultados = false;
                foreach ($insumos as $ins):
                    if ($filtroTipo && $ins['tipo'] !== $filtroTipo) continue;
                    $hayResultados = true;
                    $tipoBadge = match($ins['tipo']) {
                        'materia_prima'    => 'badge-info',
                        'material_empaque' => 'badge-purple',
                        'insumo_proceso'   => 'badge-warning',
                        default            => 'badge-muted',
                    };
                ?>
                <tr>
                    <td>
                        <code style="background:#f1f5f9;padding:2px 6px;
                                        border-radius:4px;font-size:11px">
                            <?= htmlspecialchars($ins['codigo']) ?>
                        </code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($ins['descripcion']) ?></strong>
                        <?php if ($ins['vida_util_referencia']): ?>
                        <div style="font-size:11px;color:#94a3b8">
                            Vida útil ref: <?= htmlspecialchars($ins['vida_util_referencia']) ?>
                        </div>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $tipoBadge ?>">
                            <?= $tipos[$ins['tipo']] ?? $ins['tipo'] ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <code><?= htmlspecialchars($ins['unidad_medida']) ?></code>
                    </td>
                    <td style="text-align:center;font-weight:700">
                        <?php if ($ins['stock_total'] > 0): ?>
                        <span style="color:#15803d">
                            <?= number_format($ins['stock_total'],2) ?>
                            <?= htmlspecialchars($ins['unidad_medida']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:12px;font-weight:400">
                            Sin stock
                        </span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?php if ($ins['proximo_vencimiento']): ?>
                        <?php
                        $dias = (int)((strtotime($ins['proximo_vencimiento']) - time()) / 86400);
                        $color = $dias < 0 ? '#dc2626' : ($dias < 30 ? '#d97706' : '#15803d');
                        ?>
                        <span style="color:<?= $color ?>;font-weight:600">
                            <?= date('d/m/Y', strtotime($ins['proximo_vencimiento'])) ?>
                        </span>
                        <div style="font-size:10px;color:<?= $color ?>">
                            <?= $dias < 0
                                ? '⚠️ Vencido hace '.abs($dias).'d'
                                : ($dias < 30 ? '⚠️ En '.$dias.'d' : 'En '.$dias.'d') ?>
                        </div>
                        <?php else: ?>
                        <span style="color:#94a3b8">—</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted">
                            <?= (int)$ins['lotes_en_stock'] ?> lotes
                        </span>
                    </td>
                    <?php if ($canWrite): ?>
                    <td>
                        <a href="<?= APP_URL ?>/m0/insumos/<?= $ins['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                    </td>
                    <?php endif ?>
                </tr>
                <?php endforeach ?>
                <?php if (!$hayResultados): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px">
                    No hay insumos registrados.
                    <a href="<?= APP_URL ?>/m0/insumos/nuevo">Agregar el primero</a>
                </td></tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>