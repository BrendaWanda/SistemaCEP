<?php
/** @var array|null $receta @var array $productos @var array $insumos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Receta' : '➕ Nueva Receta' ?>
    </div>
    <div class="page-sub">
        Define los ingredientes y cantidades para 1 batch de producción.
    </div>
</div>

<form method="POST"
        action="<?= APP_URL ?>/m0/recetas/<?= $esEditar ? $receta['id'].'/editar' : 'nueva' ?>">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">

<!-- Encabezado -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-title">📋 Datos generales</span></div>
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
                            <?= ($receta['producto_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['linea_nombre'].' — '.$p['nombre']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <?php if ($esEditar): ?>
                <input type="hidden" name="producto_id" value="<?= $receta['producto_id'] ?>">
                <div class="form-hint">No se puede cambiar el producto al editar.</div>
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
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="vigente"
                        <?= ($receta['vigente'] ?? 1) ? 'checked' : '' ?>
                        style="width:16px;height:16px">
                <span style="font-weight:500">Receta vigente (activa para producción)</span>
            </label>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Ingredientes -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">🧾 Ingredientes (BOM)</span>
        <button type="button" class="btn btn-sm btn-primary"
                onclick="agregarFila()">+ Agregar ingrediente</button>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="tablaIngredientes">
            <thead>
                <tr>
                    <th style="width:40%">Insumo</th>
                    <th style="width:15%;text-align:center">Cantidad</th>
                    <th style="width:12%;text-align:center">Unidad</th>
                    <th style="width:13%;text-align:center">Crítico</th>
                    <th style="width:10%;text-align:center">Quitar</th>
                </tr>
            </thead>
            <tbody id="cuerpoIngredientes">
            <?php if ($esEditar && !empty($receta['ingredientes'])): ?>
            <?php foreach ($receta['ingredientes'] as $ing): ?>
            <tr>
                <td>
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
                            style="width:16px;height:16px">
                </td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="this.closest('tr').remove()">✕</button>
                </td>
            </tr>
            <?php endforeach ?>
            <?php else: ?>
            <!-- Fila vacía inicial para nueva receta -->
            <tr>
                <td>
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
                            style="width:16px;height:16px">
                </td>
                <td style="text-align:center">
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="this.closest('tr').remove()">✕</button>
                </td>
            </tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div style="padding:12px 16px;border-top:1px solid #f1f5f9;
                font-size:12px;color:#64748b">
        💡 Marque como <strong>crítico</strong> los ingredientes que el DBC o la UNACE
        consideran esenciales para el producto (harina, levadura, ingrediente diferenciador).
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m0/recetas" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">
        <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear receta' ?>
    </button>
</div>

</form>

<script>
// Opciones de insumos para filas nuevas
const insumos = <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>;
const unidades = ['kg','g','l','ml','und','sobre'];
let filaIndex = <?= $esEditar ? count($receta['ingredientes'] ?? []) : 1 ?>;

function agregarFila() {
    const tbody = document.getElementById('cuerpoIngredientes');
    const tr    = document.createElement('tr');

    let optsInsumo = '<option value="">Seleccione insumo...</option>';
    for (const [id, nombre] of Object.entries(insumos)) {
        optsInsumo += `<option value="${id}">${nombre}</option>`;
    }

    let optsUnidad = '';
    for (const u of unidades) {
        optsUnidad += `<option value="${u}">${u}</option>`;
    }

    tr.innerHTML = `
        <td>
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
                    style="width:16px;height:16px">
        </td>
        <td style="text-align:center">
            <button type="button" class="btn btn-sm btn-danger"
                    onclick="this.closest('tr').remove()">✕</button>
        </td>`;

    tbody.appendChild(tr);
    filaIndex++;
}
</script>