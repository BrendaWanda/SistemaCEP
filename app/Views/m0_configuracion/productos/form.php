<?php
/** @var array|null $producto @var array $lineas @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($producto[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Producto' : '➕ Nuevo Producto' ?>
    </div>
    <div class="page-sub">El LSE y LIE se calculan automáticamente desde el peso nominal y tolerancia.</div>
</div>

<form method="POST"
        action="<?= APP_URL ?>/m0/productos/<?= $esEditar ? $producto['id'].'/editar' : 'nuevo' ?>">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">

<!-- Identificación -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">📋 Identificación</span></div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Línea de producción <span class="form-required">*</span></label>
                <select name="linea_id" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($lineas as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                            <?= ($producto['linea_id'] ?? '') == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Código <span class="form-required">*</span></label>
                <input type="text" name="codigo" class="form-control"
                        value="<?= $v('codigo') ?>"
                        placeholder="Ej: PT-PAN-COCO"
                        style="text-transform:uppercase" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre <span class="form-required">*</span></label>
                <input type="text" name="nombre" class="form-control"
                        value="<?= $v('nombre') ?>"
                        placeholder="Ej: Pan con Coco" required>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Lote contrato</label>
                <input type="text" name="lote_contrato" class="form-control"
                        value="<?= $v('lote_contrato') ?>"
                        placeholder="Ej: Lote N°2 Secundaria 2026">
            </div>
            <div class="form-group">
                <label class="form-label">Ítem DBC</label>
                <input type="text" name="item_dbc" class="form-control"
                        value="<?= $v('item_dbc') ?>"
                        placeholder="Ej: Ítem 1">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="2">
<?= $v('descripcion') ?></textarea>
        </div>
    </div>
</div>

<!-- Especificaciones DBC -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">📏 Especificaciones contractuales (DBC)</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Peso nominal (g)</label>
                <input type="number" name="peso_nominal_g" id="pesoNominal"
                        class="form-control"
                        value="<?= $v('peso_nominal_g') ?>"
                        step="0.001" min="0" placeholder="Ej: 80">
            </div>
            <div class="form-group">
                <label class="form-label">Tolerancia (%)</label>
                <input type="number" name="tolerancia_pct" id="tolerancia"
                        class="form-control"
                        value="<?= $v('tolerancia_pct','1.00') ?>"
                        step="0.01" min="0">
                <div class="form-hint">DBC establece ±1%</div>
            </div>
            <div class="form-group">
                <label class="form-label">LSE / LIE (calculados automáticamente)</label>
                <div style="display:flex;gap:8px">
                    <input type="text" id="lsePreview" class="form-control" readonly
                            style="background:#f0fdf4;color:#15803d;font-weight:600"
                            placeholder="LSE">
                    <input type="text" id="liePreview" class="form-control" readonly
                            style="background:#fef2f2;color:#dc2626;font-weight:600"
                            placeholder="LIE">
                </div>
            </div>
        </div>
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="form-label">Und. por receta</label>
                <input type="number" name="unidades_por_receta" class="form-control"
                        value="<?= $v('unidades_por_receta') ?>" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Und. por bolsa</label>
                <input type="number" name="unidades_por_bolsa" class="form-control"
                        value="<?= $v('unidades_por_bolsa') ?>" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Bolsas por caja</label>
                <input type="number" name="bolsas_por_caja" class="form-control"
                        value="<?= $v('bolsas_por_caja') ?>" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Vida útil (días)</label>
                <input type="number" name="vida_util_dias" class="form-control"
                        value="<?= $v('vida_util_dias') ?>" min="0">
            </div>
        </div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">T° conserv. mín (°C)</label>
                <input type="number" name="temperatura_conserv_min" class="form-control"
                        value="<?= $v('temperatura_conserv_min') ?>" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">T° conserv. máx (°C)</label>
                <input type="number" name="temperatura_conserv_max" class="form-control"
                        value="<?= $v('temperatura_conserv_max') ?>" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">T° entrega máx (°C)</label>
                <input type="number" name="temperatura_entrega_max" class="form-control"
                        value="<?= $v('temperatura_entrega_max') ?>" step="0.1">
            </div>
        </div>
    </div>
</div>

<!-- Organolépticos y Fisicoquímico -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">👁️ Características de referencia del producto terminado</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-2">
            <?php foreach ([
                'ref_color'     => 'Color',
                'ref_olor'      => 'Olor',
                'ref_sabor'     => 'Sabor',
                'ref_textura'   => 'Textura',
                'ref_apariencia'=> 'Apariencia',
            ] as $campo => $label): ?>
            <div class="form-group">
                <label class="form-label"><?= $label ?></label>
                <input type="text" name="<?= $campo ?>" class="form-control"
                        value="<?= $v($campo) ?>">
            </div>
            <?php endforeach ?>
        </div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Humedad máx PT (%)</label>
                <input type="number" name="ref_humedad_max_pct" class="form-control"
                        value="<?= $v('ref_humedad_max_pct') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">pH mín PT</label>
                <input type="number" name="ref_ph_min" class="form-control"
                        value="<?= $v('ref_ph_min') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">pH máx PT</label>
                <input type="number" name="ref_ph_max" class="form-control"
                        value="<?= $v('ref_ph_max') ?>" step="0.01">
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m0/productos" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear producto' ?>
    </button>
</div>

</form>

<script>
function calcularLimites() {
    const peso = parseFloat(document.getElementById('pesoNominal').value) || 0;
    const tol  = parseFloat(document.getElementById('tolerancia').value) || 1;
    if (peso > 0) {
        document.getElementById('lsePreview').value =
            (peso * (1 + tol / 100)).toFixed(3) + ' g';
        document.getElementById('liePreview').value =
            (peso * (1 - tol / 100)).toFixed(3) + ' g';
    } else {
        document.getElementById('lsePreview').value = '';
        document.getElementById('liePreview').value = '';
    }
}
document.getElementById('pesoNominal').addEventListener('input', calcularLimites);
document.getElementById('tolerancia').addEventListener('input', calcularLimites);
calcularLimites();
</script>