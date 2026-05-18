<?php
/** @var array $lote @var array $consumos @var array $insumos @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            📦 MP Consumida — Lote <?= htmlspecialchars($lote['codigo_lote']) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($lote['producto_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($lote['fecha_produccion'])) ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>"
            class="btn btn-secondary">← Volver al lote</a>
    </div>
</div>

<!-- Formulario para agregar MP -->
<?php if ($canWrite): ?>
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">➕ Registrar consumo de MP</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/mp/agregar">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">
                    Insumo <span class="form-required">*</span>
                </label>
                <select name="insumo_id_sel" class="form-control"
                        id="insumoSel" required>
                    <option value="">Seleccione el insumo...</option>
                    <?php foreach ($insumos as $id => $nombre): ?>
                    <option value="<?= $id ?>">
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Lote de stock a usar <span class="form-required">*</span>
                </label>
                <select name="stock_mp_id" class="form-control"
                        id="stockSel" required>
                    <option value="">Primero seleccione el insumo</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Cantidad usada <span class="form-required">*</span>
                </label>
                <input type="number" name="cantidad_usada" class="form-control"
                        step="0.001" min="0.001"
                        placeholder="Cantidad consumida" required>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            ✅ Registrar consumo
        </button>
    </div>
    </form>
</div>
<?php endif ?>

<!-- Tabla de consumos registrados -->
<div class="card">
    <div class="card-header">
        <span class="card-title">MP registrada para este lote</span>
        <span class="badge badge-info"><?= count($consumos) ?> registros</span>
    </div>
    <?php if (empty($consumos)): ?>
    <div class="card-body text-center text-muted" style="padding:30px">
        No hay consumo de MP registrado para este lote.
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Lote proveedor</th>
                    <th style="text-align:center">Vencimiento stock</th>
                    <th style="text-align:right">Cantidad usada</th>
                    <?php if ($canWrite): ?><th>Acción</th><?php endif ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($consumos as $c): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($c['insumo_nombre']) ?></strong>
                    <div style="font-size:11px;color:#94a3b8">
                        <?= htmlspecialchars($c['insumo_codigo']) ?>
                    </div>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($c['stock_lote']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
                    <?= date('d/m/Y', strtotime($c['fecha_vencimiento'])) ?>
                </td>
                <td style="text-align:right;font-weight:700">
                    <?= number_format((float)$c['cantidad_usada'],3) ?>
                    <?= htmlspecialchars($c['unidad_medida']) ?>
                </td>
                <?php if ($canWrite): ?>
                <td>
                    <form method="POST"
                            action="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/mp/<?= $c['id'] ?>/eliminar"
                            style="display:inline">
                        <input type="hidden" name="_token"
                            value="<?= $csrfToken ?>">
                        <button class="btn btn-sm btn-danger"
                                data-confirm="¿Eliminar este consumo y devolver al stock?">
                            ✕ Quitar
                        </button>
                    </form>
                </td>
                <?php endif ?>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<script>
const appUrl = '<?= APP_URL ?>';

document.getElementById('insumoSel').addEventListener('change', function() {
    const insumoId = this.value;
    const stockSel = document.getElementById('stockSel');

    stockSel.innerHTML = '<option value="">Cargando...</option>';
    if (!insumoId) {
        stockSel.innerHTML =
            '<option value="">Primero seleccione el insumo</option>';
        return;
    }

    fetch(`${appUrl}/api/stock-disponible?insumo_id=${insumoId}`)
        .then(r => r.json())
        .then(data => {
            stockSel.innerHTML =
                '<option value="">Seleccione lote de stock...</option>';
            if (data.data && Object.keys(data.data).length > 0) {
                for (const [id, label] of Object.entries(data.data)) {
                    const opt = document.createElement('option');
                    opt.value = id;
                    opt.textContent = label;
                    stockSel.appendChild(opt);
                }
            } else {
                stockSel.innerHTML =
                    '<option value="">Sin stock disponible para este insumo</option>';
            }
        })
        .catch(() => {
            stockSel.innerHTML =
                '<option value="">Error al cargar stock</option>';
        });
});
</script>