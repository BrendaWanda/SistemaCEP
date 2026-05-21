<?php
/** @var array|null $receta @var array $productos @var array $insumos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Receta' : 'Nueva Receta' ?>
        </div>
        <div class="page-sub">
            Define los ingredientes y cantidades para 1 batch de producción.
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/recetas" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<form method="POST"
        action="<?= APP_URL ?>/m0/recetas/<?= $esEditar
            ? $receta['id'].'/editar' : 'nueva' ?>">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">

<!-- Datos generales -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-card-text" style="color:#4f8ef7;margin-right:6px"></i>
            Datos generales
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Producto <span class="form-required">*</span>
                </label>
                <select name="producto_id" class="form-control"
                        <?= $esEditar ? 'disabled' : 'required' ?>>
                    <option value="">Seleccione...</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id'] ?>"
                            <?= ($receta['producto_id'] ?? '') == $p['id']
                                ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['linea_nombre'].' — '.$p['nombre']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <?php if ($esEditar): ?>
                <input type="hidden" name="producto_id"
                        value="<?= $receta['producto_id'] ?>">
                <div class="form-hint">
                    <i class="bi bi-info-circle"></i>
                    No se puede cambiar el producto al editar.
                </div>
                <?php endif ?>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Nombre de la receta <span class="form-required">*</span>
                </label>
                <input type="text" name="nombre" class="form-control"
                        value="<?= htmlspecialchars($receta['nombre'] ?? '') ?>"
                        placeholder="Ej: Pan con Coco v1" required>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Notas / descripción</label>
            <textarea name="descripcion" class="form-control" rows="2"
                        placeholder="Instrucciones especiales, observaciones..."><?=
                htmlspecialchars($receta['descripcion'] ?? '')
            ?></textarea>
        </div>
        <?php if ($esEditar): ?>
        <div class="form-group" style="margin-bottom:0">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="vigente"
                        <?= ($receta['vigente'] ?? 1) ? 'checked' : '' ?>
                        style="width:16px;height:16px;accent-color:#4f8ef7">
                <span style="font-weight:500">
                    Receta vigente (activa para producción)
                </span>
            </label>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Ingredientes BOM -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-list-check" style="color:#4f8ef7;margin-right:6px"></i>
            Ingredientes (BOM)
        </span>
        <button type="button" class="btn btn-sm btn-primary"
                onclick="agregarFila()">
            <i class="bi bi-plus-lg"></i> Agregar ingrediente
        </button>
    </div>
    <div class="table-wrap">
        <table class="data-table-bordered" id="tablaIngredientes">
            <thead>
                <tr>
                    <th style="text-align:left;width:40%">Insumo</th>
                    <th style="text-align:center;width:15%">Cantidad</th>
                    <th style="text-align:center;width:12%">Unidad</th>
                    <th style="text-align:center;width:13%">Crítico</th>
                    <th style="text-align:center;width:10%">Quitar</th>
                </tr>
            </thead>
            <tbody id="cuerpoIngredientes">
            <?php if ($esEditar && !empty($receta['ingredientes'])): ?>
            <?php foreach ($receta['ingredientes'] as $ing): ?>
            <tr>
                <td style="text-align:left">
                    <select name="insumo_id[]" class="form-control" required>
                        <option value="">Seleccione insumo...</option>
                        <?php foreach ($insumos as $insId => $insNombre): ?>
                        <option value="<?= $insId ?>"
                                <?= $ing['insumo_id'] == $insId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($insNombre) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="cantidad[]" class="form-control"
                            value="<?= number_format((float)$ing['cantidad'],3,'.','') ?>"
                            step="0.001" min="0" required
                            style="text-align:right">
                </td>
                <td>
                    <select name="unidad[]" class="form-control">
                        <?php foreach (['kg','g','l','ml','und','sobre'] as $u): ?>
                        <option value="<?= $u ?>"
                                <?= $ing['unidad_medida'] === $u ? 'selected' : '' ?>>
                            <?= $u ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </td>
                <td style="text-align:center">
                    <input type="checkbox" name="es_critico[<?= $loop ?? 0 ?>]"
                            <?= $ing['es_critico'] ? 'checked' : '' ?>
                            style="width:16px;height:16px;accent-color:#4f8ef7">
                </td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="this.closest('tr').remove()"
                            title="Quitar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach ?>
            <?php else: ?>
            <tr>
                <td style="text-align:left">
                    <select name="insumo_id[]" class="form-control" required>
                        <option value="">Seleccione insumo...</option>
                        <?php foreach ($insumos as $insId => $insNombre): ?>
                        <option value="<?= $insId ?>">
                            <?= htmlspecialchars($insNombre) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="cantidad[]" class="form-control"
                            step="0.001" min="0" required style="text-align:right">
                </td>
                <td>
                    <select name="unidad[]" class="form-control">
                        <?php foreach (['kg','g','l','ml','und','sobre'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach ?>
                    </select>
                </td>
                <td style="text-align:center">
                    <input type="checkbox" name="es_critico[0]"
                            style="width:16px;height:16px;accent-color:#4f8ef7">
                </td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="this.closest('tr').remove()" title="Quitar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;
                font-size:12px;color:#64748b;display:flex;gap:6px;align-items:flex-start">
        <i class="bi bi-info-circle" style="margin-top:1px;flex-shrink:0"></i>
        Marque como <strong>crítico</strong> los ingredientes esenciales para el
        producto según el DBC o UNACE (harina, levadura, ingrediente diferenciador).
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m0/recetas" class="btn btn-secondary">
        <i class="bi bi-x-lg"></i> Cancelar
    </a>
    <button type="submit" class="btn btn-primary">
        <?php if ($esEditar): ?>
            <i class="bi bi-floppy"></i> Guardar cambios
        <?php else: ?>
            <i class="bi bi-check-lg"></i> Crear receta
        <?php endif ?>
    </button>
</div>

</form>

<script>
const insumos  = <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>;
const unidades = ['kg','g','l','ml','und','sobre'];
let filaIndex  = <?= $esEditar ? count($receta['ingredientes'] ?? []) : 1 ?>;

function agregarFila() {
    const tbody = document.getElementById('cuerpoIngredientes');
    const tr    = document.createElement('tr');

    let optsInsumo = '<option value="">Seleccione insumo...</option>';
    for (const [id, nombre] of Object.entries(insumos)) {
        optsInsumo += `<option value="${id}">${nombre}</option>`;
    }
    let optsUnidad = unidades.map(u => `<option value="${u}">${u}</option>`).join('');

    tr.innerHTML = `
        <td style="text-align:left">
            <select name="insumo_id[]" class="form-control" required>
                ${optsInsumo}
            </select>
        </td>
        <td>
            <input type="number" name="cantidad[]" class="form-control"
                    step="0.001" min="0" required style="text-align:right">
        </td>
        <td>
            <select name="unidad[]" class="form-control">${optsUnidad}</select>
        </td>
        <td style="text-align:center">
            <input type="checkbox" name="es_critico[${filaIndex}]"
                    style="width:16px;height:16px;accent-color:#4f8ef7">
        </td>
        <td style="text-align:center">
            <button type="button" class="btn btn-sm btn-danger"
                    onclick="this.closest('tr').remove()" title="Quitar">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;

    tbody.appendChild(tr);
    filaIndex++;
}
</script>