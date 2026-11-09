<?php
/** @var array $insumo
 *  @var array|null $parametro
 *  @var array $tipos
 *  @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($parametro[$campo] ?? $def); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Parámetro' : 'Nuevo Parámetro' ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($insumo['descripcion']) ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-sliders" style="color:#4f8ef7;margin-right:6px"></i>
            Datos del parámetro
        </span>
    </div>
    <form id="formParametroInsumo" method="POST"
            action="<?= APP_URL ?>/m0/<?= $esEditar
                ? 'parametros-insumo/'.$parametro['id'].'/editar'
                : 'insumos/'.$insumo['id'].'/parametros/nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Nombre <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                            value="<?= $v('nombre') ?>"
                            placeholder="Ej: Gluten, pH, Humedad" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad</label>
                    <input type="text" name="unidad" class="form-control"
                            value="<?= $v('unidad') ?>"
                            placeholder="Ej: %, °Bx (déjelo vacío si no aplica, ej. pH)">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Tipo de dato <span class="form-required">*</span>
                    </label>
                    <select name="tipo_dato" class="form-control" required id="tipoDatoSelect">
                        <option value="">Seleccione...</option>
                        <?php foreach ($tipos as $key => $label): ?>
                        <option value="<?= $key ?>"
                                <?= $v('tipo_dato') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:32px">
                        <input type="checkbox" name="obligatorio"
                                <?= ($parametro['obligatorio'] ?? false) ? 'checked' : '' ?>
                                style="width:16px;height:16px;accent-color:#4f8ef7">
                        <span style="font-weight:500">
                            Obligatorio en el formulario de recepción
                        </span>
                    </label>
                </div>
            </div>

            <div class="form-row cols-2" id="seccionMinMax"
                    style="<?= ($v('tipo_dato') && $v('tipo_dato') !== 'numerico') ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label class="form-label">Valor mínimo</label>
                    <input type="number" name="valor_min" class="form-control"
                            value="<?= $v('valor_min') ?>" step="0.0001"
                            placeholder="Déjelo vacío si no aplica">
                </div>
                <div class="form-group">
                    <label class="form-label">Valor máximo</label>
                    <input type="number" name="valor_max" class="form-control"
                            value="<?= $v('valor_max') ?>" step="0.0001"
                            placeholder="Déjelo vacío si no aplica">
                </div>
            </div>

            <div class="form-group" id="seccionOpciones"
                    style="<?= $v('tipo_dato') === 'seleccion' ? '' : 'display:none' ?>">
                <label class="form-label">Opciones (una por línea)</label>
                <textarea name="opciones_json_raw" id="opcionesRaw" class="form-control" rows="3"
                        placeholder="Ej:&#10;Conforme&#10;No conforme&#10;Observado"><?php
                    $opts = $parametro['opciones_json'] ?? null;
                    if ($opts) {
                        $arr = json_decode($opts, true);
                        echo htmlspecialchars(is_array($arr) ? implode("\n", $arr) : '');
                    }
                ?></textarea>
                <input type="hidden" name="opciones_json" id="opcionesJson">
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros" class="btn btn-secondary">
                <i class="bi bi-x-lg"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <?php if ($esEditar): ?>
                    <i class="bi bi-floppy"></i> Guardar cambios
                <?php else: ?>
                    <i class="bi bi-check-lg"></i> Crear parámetro
                <?php endif ?>
            </button>
        </div>
    </form>
</div>

<script>
const tipoDatoSelect = document.getElementById('tipoDatoSelect');
const seccionMinMax  = document.getElementById('seccionMinMax');
const seccionOpciones= document.getElementById('seccionOpciones');

function actualizarSecciones() {
    const tipo = tipoDatoSelect.value;
    seccionMinMax.style.display   = tipo === 'numerico' ? '' : 'none';
    seccionOpciones.style.display = tipo === 'seleccion' ? '' : 'none';
}
tipoDatoSelect.addEventListener('change', actualizarSecciones);
actualizarSecciones();

// Hallazgo de auditoría reutilizado de M0/productos/parametros: el
// formulario usa fetch() y ANTES el JS redirigía siempre a éxito sin
// revisar la respuesta real del servidor — un error de validación
// quedaba invisible. Aquí se revisa resp.success explícitamente.
document.getElementById('formParametroInsumo').addEventListener('submit', function (e) {
    e.preventDefault();

    // Convertir el textarea de opciones a JSON antes de enviar
    const raw = document.getElementById('opcionesRaw');
    if (raw) {
        const lineas = raw.value.split('\n').map(l => l.trim()).filter(l => l);
        document.getElementById('opcionesJson').value =
            lineas.length ? JSON.stringify(lineas) : '';
    }

    const form = e.target;
    const data = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            window.location.href = '<?= APP_URL ?>/m0/insumos/<?= $insumo['id'] ?>/parametros';
        } else {
            alert(resp.message || 'No se pudo guardar el parámetro.');
        }
    })
    .catch(() => alert('Error de conexión al guardar el parámetro.'));
});
</script>