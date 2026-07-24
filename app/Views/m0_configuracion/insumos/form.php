<?php
/** @var array|null $insumo
 *  @var array $tipos
 *  @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($insumo[$campo] ?? $def); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Insumo' : 'Nuevo Insumo' ?>
        </div>
        <div class="page-sub">
            Las especificaciones organolépticas son referencia para el control de recepción (M1).
            Los parámetros fisicoquímicos (humedad, pH, gluten, etc.) se configuran aparte,
            solo para los insumos donde aplican.
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/insumos" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<form method="POST"
        action="<?= APP_URL ?>/m0/insumos/<?= $esEditar
        ? $insumo['id'].'/editar' : 'nuevo' ?>">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">

<!-- Identificación -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-card-text" style="color:#4f8ef7;margin-right:6px"></i>
            Identificación
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">
                    Código <span class="form-required">*</span>
                </label>
                <input type="text" name="codigo" class="form-control"
                        value="<?= $v('codigo') ?>"
                        placeholder="Ej: MP0041"
                        style="text-transform:uppercase;font-family:monospace"
                        required>
                <div class="form-hint">
                    <i class="bi bi-info-circle"></i>
                    MP0001 = materia prima · ME0001 = empaque
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Tipo <span class="form-required">*</span>
                </label>
                <select name="tipo" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($tipos as $key => $label): ?>
                    <option value="<?= $key ?>"
                            <?= $v('tipo') === $key ? 'selected' : '' ?>>
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
                        placeholder="Ej: Harina de Trigo Letizia (Todo Uso)"
                        required>
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

<!-- Organolépticas -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-eye-fill" style="color:#4f8ef7;margin-right:6px"></i>
            Especificaciones organolépticas de referencia
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

<!-- AGREGADO: antes aquí iba una sección fija de "Especificaciones
     fisicoquímicas" (humedad, densidad, pH, Brix, gluten, impurezas)
     que se pedía a TODOS los insumos por igual — un saborizante o un
     empaque terminaban mostrando "Gluten mín" sin que aplicara nunca.
     Ahora esos parámetros se configuran por insumo, después de
     crearlo, en su propia sección — mismo patrón que Productos usa
     para sus parámetros de proceso. -->
<?php if ($esEditar): ?>
<div class="card" style="margin-bottom:16px;background:#f8fafc">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px">
                <i class="bi bi-sliders" style="color:#4f8ef7"></i>
                Parámetros fisicoquímicos
            </div>
            <div style="font-size:var(--fs-dense-sm);color:#64748b;margin-top:4px">
                Humedad, pH, densidad, Brix, gluten, impurezas — solo los que apliquen a este insumo.
            </div>
        </div>
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros"
            class="btn btn-secondary">
            <i class="bi bi-sliders"></i> Configurar parámetros
        </a>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:16px;background:#f8fafc">
    <div class="card-body" style="color:#64748b;font-size:var(--fs-dense-sm)">
        <i class="bi bi-info-circle"></i>
        Los parámetros fisicoquímicos (humedad, pH, gluten, etc.) se configuran
        después de crear el insumo, desde su ficha de detalle.
    </div>
</div>
<?php endif ?>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m0/insumos" class="btn btn-secondary">
        <i class="bi bi-x-lg"></i> Cancelar
    </a>
    <button type="submit" class="btn btn-primary">
        <?php if ($esEditar): ?>
            <i class="bi bi-floppy"></i> Guardar cambios
        <?php else: ?>
            <i class="bi bi-check-lg"></i> Registrar insumo
        <?php endif ?>
    </button>
</div>

</form>