<?php
/** @var array $insumos @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Catálogo de Insumos</div>
        <div class="page-sub">
            Materias primas, materiales de empaque e insumos de proceso con stock disponible.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/insumos/nuevo" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo insumo
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Filtro por tipo -->
<div class="card" style="margin-bottom:12px">
    <div class="card-body" style="padding:10px 16px">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span style="font-size:12px;font-weight:600;color:#475569">
                <i class="bi bi-funnel" style="margin-right:4px"></i>
                Filtrar:
            </span>
            <a href="<?= APP_URL ?>/m0/insumos"
                class="btn btn-sm <?= !isset($_GET['tipo']) || $_GET['tipo']===''
                    ? 'btn-primary' : 'btn-secondary' ?>">
                Todos
            </a>
            <?php foreach ($tipos as $key => $label): ?>
            <a href="<?= APP_URL ?>/m0/insumos?tipo=<?= $key ?>"
                class="btn btn-sm <?= ($_GET['tipo'] ?? '') === $key
                    ? 'btn-primary' : 'btn-secondary' ?>">
                <?= $label ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-basket-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Listado de insumos
        </span>
        <span class="badge badge-info"><?= count($insumos) ?> registros</span>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered">
            <thead>
                <tr>
                    <th style="width:100px">Código</th>
                    <th style="text-align:left">Descripción</th>
                    <th style="text-align:center;width:130px">Tipo</th>
                    <th style="text-align:center;width:80px">Unidad</th>
                    <th style="text-align:center;width:140px">Stock disponible</th>
                    <th style="text-align:center;width:140px">Próx. vencimiento</th>
                    <th style="text-align:center;width:80px">Lotes</th>
                    <?php if ($canWrite): ?>
                    <th style="text-align:center;width:150px">Acciones</th>
                    <?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $filtroTipo    = $_GET['tipo'] ?? '';
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
                    <code style="background:#f1f5f9;padding:3px 8px;border-radius:5px;
                                font-size:12px;color:#090e22;font-weight:700">
                        <?= htmlspecialchars($ins['codigo']) ?>
                    </code>
                </td>
                <td style="text-align:left">
                    <strong><?= htmlspecialchars($ins['descripcion']) ?></strong>
                    <?php if ($ins['vida_util_referencia']): ?>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                        <i class="bi bi-clock" style="font-size:10px"></i>
                        Vida útil: <?= htmlspecialchars($ins['vida_util_referencia']) ?>
                    </div>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $tipoBadge ?>">
                        <?= $tipos[$ins['tipo']] ?? $ins['tipo'] ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <code style="font-size:12px;background:#f1f5f9;
                                padding:2px 6px;border-radius:4px">
                        <?= htmlspecialchars($ins['unidad_medida']) ?>
                    </code>
                </td>
                <td style="text-align:center;font-weight:700">
                    <?php if ($ins['stock_total'] > 0): ?>
                    <span style="color:#16a34a">
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
                    $dias  = (int)((strtotime($ins['proximo_vencimiento']) - time()) / 86400);
                    $color = $dias < 0 ? '#dc2626' : ($dias < 30 ? '#d97706' : '#16a34a');
                    ?>
                    <span style="color:<?= $color ?>;font-weight:600">
                        <?= date('d/m/Y', strtotime($ins['proximo_vencimiento'])) ?>
                    </span>
                    <div style="font-size:10px;color:<?= $color ?>;margin-top:2px">
                        <?php if ($dias < 0): ?>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Vencido hace <?= abs($dias) ?>d
                        <?php elseif ($dias < 30): ?>
                        <i class="bi bi-exclamation-circle-fill"></i>
                        En <?= $dias ?>d
                        <?php else: ?>
                        En <?= $dias ?>d
                        <?php endif ?>
                    </div>
                    <?php else: ?>
                    <span style="color:#94a3b8">—</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-muted">
                        <?= (int)$ins['lotes_en_stock'] ?>
                    </span>
                </td>
                <?php if ($canWrite): ?>
                <td style="text-align:center">
                    <div style="display:flex;gap:4px;justify-content:center">
                        <!-- Ver -->
                        <a href="<?= APP_URL ?>/m0/insumos/<?= $ins['id'] ?>"
                            class="btn btn-sm btn-secondary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <!-- Editar -->
                        <a href="<?= APP_URL ?>/m0/insumos/<?= $ins['id'] ?>/editar"
                            class="btn btn-sm btn-secondary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <!-- Eliminar -->
                        <form method="POST"
                                action="<?= APP_URL ?>/m0/insumos/<?= $ins['id'] ?>/eliminar"
                                style="display:inline">
                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                            <button type="submit"
                                    class="btn btn-sm btn-danger" title="Eliminar"
                                    data-confirm="¿Eliminar '<?= htmlspecialchars($ins['descripcion']) ?>'? Esta acción no se puede deshacer.">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
                <?php endif ?>
            </tr>
            <?php endforeach ?>
            <?php if (!$hayResultados): ?>
            <tr>
                <td colspan="<?= $canWrite ? 8 : 7 ?>"
                    style="text-align:center;padding:48px;color:#94a3b8">
                    <i class="bi bi-basket" style="font-size:32px;display:block;
                                margin-bottom:10px;color:#e2e8f0"></i>
                    No hay insumos registrados.
                    <?php if ($canWrite): ?>
                    <div style="margin-top:10px">
                        <a href="<?= APP_URL ?>/m0/insumos/nuevo"
                            class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Agregar el primero
                        </a>
                    </div>
                    <?php endif ?>
                </td>
            </tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>