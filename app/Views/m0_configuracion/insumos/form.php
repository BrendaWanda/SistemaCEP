<?php
/** @var array|null $insumo @var array $tipos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($insumo[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Insumo' : '➕ Nuevo Insumo' ?>
    </div>
    <div class="page-sub">
        Las especificaciones son referencia para el control de recepción (M1).
    </div>
</div>

<form method="POST"
        action="<?= APP_URL ?>/m0/insumos/<?= $esEditar ? $insumo['id'].'/editar' : 'nuevo' ?>">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">

<!-- Identificación -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">📋 Identificación</span></div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Código <span class="form-required">*</span></label>
                <input type="text" name="codigo" class="form-control"
                        value="<?= $v('codigo') ?>"
                        placeholder="Ej: MP0041"
                        style="text-transform:uppercase" required>
                <div class="form-hint">Ej: MP0001 (materia prima), ME0001 (empaque)</div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo <span class="form-required">*</span></label>
                <select name="tipo" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tipos as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $v('tipo') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Unidad de medida <span class="form-required">*</span>
                </label>
                <select name="unidad_medida" class="form-control" required>
                    <?php foreach (['kg','g','l','ml','und','sobre'] as $u): ?>
                    <option value="<?= $u ?>"
                            <?= $v('unidad_medida') === $u ? 'selected' : '' ?>>
                        <?= $u ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Descripción <span class="form-required">*</span>
                </label>
                <input type="text" name="descripcion" class="form-control"
                        value="<?= $v('descripcion') ?>"
                        placeholder="Ej: Harina de Trigo Letizia (Todo Uso)" required>
            </div>
            <div class="form-group">
                <label class="form-label">Vida útil de referencia</label>
                <input type="text" name="vida_util_referencia" class="form-control"
                        value="<?= $v('vida_util_referencia') ?>"
                        placeholder="Ej: 6 Meses, 1 Año, 30 Días">
            </div>
        </div>
    </div>
</div>

<!-- Especificaciones organolépticas -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            👁️ Especificaciones organolépticas de referencia
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Sabor / Olor esperado</label>
                <input type="text" name="esp_sabor_olor" class="form-control"
                        value="<?= $v('esp_sabor_olor') ?>"
                        placeholder="Ej: Característico a trigo, sin olores extraños">
            </div>
            <div class="form-group">
                <label class="form-label">Color esperado</label>
                <input type="text" name="esp_color" class="form-control"
                        value="<?= $v('esp_color') ?>"
                        placeholder="Ej: Blanco cremoso">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción física</label>
                <input type="text" name="esp_descripcion_fisica" class="form-control"
                        value="<?= $v('esp_descripcion_fisica') ?>"
                        placeholder="Ej: Polvo fino sin grumos">
            </div>
        </div>
    </div>
</div>

<!-- Especificaciones fisicoquímicas -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">🧪 Especificaciones fisicoquímicas de referencia</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="form-label">Humedad máx (%)</label>
                <input type="number" name="esp_humedad_max" class="form-control"
                        value="<?= $v('esp_humedad_max') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">Densidad mín</label>
                <input type="number" name="esp_densidad_min" class="form-control"
                        value="<?= $v('esp_densidad_min') ?>" step="0.0001">
            </div>
            <div class="form-group">
                <label class="form-label">Densidad máx</label>
                <input type="number" name="esp_densidad_max" class="form-control"
                        value="<?= $v('esp_densidad_max') ?>" step="0.0001">
            </div>
            <div class="form-group">
                <label class="form-label">pH mín</label>
                <input type="number" name="esp_ph_min" class="form-control"
                        value="<?= $v('esp_ph_min') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">pH máx</label>
                <input type="number" name="esp_ph_max" class="form-control"
                        value="<?= $v('esp_ph_max') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">Gluten mín (%)</label>
                <input type="number" name="esp_gluten_min" class="form-control"
                        value="<?= $v('esp_gluten_min') ?>" step="0.01">
            </div>
            <div class="form-group">
                <label class="form-label">Impurezas máx (%)</label>
                <input type="number" name="esp_impurezas_max" class="form-control"
                        value="<?= $v('esp_impurezas_max') ?>" step="0.001">
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m0/insumos" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <?= $esEditar ? '💾 Guardar cambios' : '✅ Registrar insumo' ?>
    </button>
</div>

</form>