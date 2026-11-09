<?php
/** @var array $lotes @var array|false $insumo @var string $codigo */
?>

<div class="page-header">
    <div>
        <div class="page-title">📦 Trazabilidad inversa — <?= htmlspecialchars($codigo) ?></div>
        <div class="page-sub">
            <?php if ($insumo): ?>
            <?= htmlspecialchars($insumo['descripcion']) ?> ·
            Lotes de PT que usaron este insumo
            <?php endif ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m5" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<?php if (empty($lotes)): ?>
<div class="card">
    <div class="card-body text-center text-muted" style="padding:40px">
        No se encontraron lotes que hayan usado el insumo
        <strong><?= htmlspecialchars($codigo) ?></strong>.
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Lotes que consumieron este insumo</span>
        <span class="badge badge-info"><?= count($lotes) ?> lotes</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código lote</th>
                    <th>Producto</th>
                    <th>Lote proveedor</th>
                    <th>Proveedor</th>
                    <th style="text-align:center">Vencimiento</th>
                    <th style="text-align:right">Cantidad usada</th>
                    <th style="text-align:center">Fecha producción</th>
                    <th style="text-align:center">Estado lote</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lotes as $l):
                $badge = match($l['estado']) {
                    'liberado'   => 'badge-success',
                    'cuarentena' => 'badge-warning',
                    'rechazado'  => 'badge-danger',
                    'en_proceso' => 'badge-info',
                    default      => 'badge-muted',
                };
            ?>
            <tr>
                <td>
                    <span style="font-family:monospace;font-weight:700;
                                    color:#1d4ed8;font-size:14px">
                        <?= htmlspecialchars($l['codigo_lote']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($l['producto_nombre']) ?></td>
                <td>
                    <code style="background:#f1f5f9;padding:1px 6px;
                                    border-radius:3px;font-size:11px">
                        <?= htmlspecialchars($l['lote_proveedor']) ?>
                    </code>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($l['proveedor_nombre']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?= date('d/m/Y', strtotime($l['fecha_vencimiento'])) ?>
                </td>
                <td style="text-align:right;font-weight:700">
                    <?= number_format((float)$l['cantidad_usada'],3) ?>
                    <?= htmlspecialchars($l['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $badge ?>">
                        <?= ucfirst($l['estado']) ?>
                    </span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($l['codigo_lote']) ?>"
                        class="btn btn-sm btn-secondary">🔗 Ver</a>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>