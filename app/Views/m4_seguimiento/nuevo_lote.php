<?php
/** @var array $productos @var array $supervisores @var array $turnos
 *  @var string $fecha_hoy @var string $codigo_preview */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div class="page-title">➕ Abrir Nuevo Lote de Producción</div>
    <div class="page-sub">
        Código generado: <strong id="codigoPreview"><?= $codigo_preview ?></strong>
    </div>
</div>

<div class="card" style="max-width:700px">
    <div class="card-header">
        <span class="card-title">Datos del lote</span>
    </div>
    <form method="POST" action="<?= APP_URL ?>/m4/nuevo-lote">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Producto <span class="form-required">*</span>
                </label>
                <select name="producto_id" class="form-control"
                        required id="productoSelect">
                    <option value="">Seleccione el producto...</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id'] ?>"
                            data-und="<?= $p['unidades_por_receta'] ?>">
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Receta <span class="form-required">*</span>
                </label>
                <select name="receta_id" class="form-control"
                        required id="recetaSelect">
                    <option value="">Primero seleccione el producto</option>
                </select>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Fecha de producción <span class="form-required">*</span>
                </label>
                <input type="date" name="fecha_produccion" class="form-control"
                    value="<?= $fecha_hoy ?>" max="<?= $fecha_hoy ?>"
                    required id="fechaProd">
            </div>
            <div class="form-group">
                <label class="form-label">
                    Turno <span class="form-required">*</span>
                </label>
                <select name="turno" class="form-control" required>
                    <option value="">Seleccione turno...</option>
                    <?php foreach ($turnos as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Número de recetas <span class="form-required">*</span>
                </label>
                <input type="number" name="numero_recetas" class="form-control"
                    step="0.5" min="0.5" placeholder="Ej: 16.5"
                    required id="numRecetas">
                <div class="form-hint">
                    Puede ser decimal. Ej: 16.5 recetas
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Rendimiento teórico (calculado)</label>
                <div class="form-control"
                    style="background:#f8fafc;font-weight:700;
                            font-size:16px;color:#1d4ed8"
                    id="rendTeoricoDisplay">
                    — und
                </div>
                <div class="form-hint">
                    Número de recetas × unidades por receta
                </div>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Supervisor responsable</label>
                <select name="supervisor_id" class="form-control">
                    <?php foreach ($supervisores as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= $id == \App\Core\Auth::id() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nivel / Destino</label>
                <input type="text" name="nivel" class="form-control"
                    value="Secundaria y Ed. Especial"
                    placeholder="Ej: Secundaria y Ed. Especial">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Vida útil del producto (días)</label>
            <input type="number" name="vida_util_dias" class="form-control"
                value="3" min="1" max="30">
            <div class="form-hint">
                Se usará para calcular la fecha de vencimiento del lote.
            </div>
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m4" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            🏭 Abrir lote de producción
        </button>
    </div>
    </form>
</div>

<script>
const appUrl = '<?= APP_URL ?>';

// Cargar recetas cuando cambia el producto
document.getElementById('productoSelect').addEventListener('change', function() {
    const productoId = this.value;
    const recetaSel  = document.getElementById('recetaSelect');
    const undPorReceta = this.options[this.selectedIndex]?.dataset?.und || 0;

    recetaSel.innerHTML = '<option value="">Cargando...</option>';
    recalcularTeorico(0);

    if (!productoId) {
        recetaSel.innerHTML = '<option value="">Primero seleccione el producto</option>';
        return;
    }

    fetch(`${appUrl}/api/recetas-por-producto?producto_id=${productoId}`)
        .then(r => r.json())
        .then(data => {
            recetaSel.innerHTML = '<option value="">Seleccione la receta...</option>';
            if (data.data && Object.keys(data.data).length > 0) {
                for (const [id, nombre] of Object.entries(data.data)) {
                    const opt = document.createElement('option');
                    opt.value = id;
                    opt.textContent = nombre;
                    opt.dataset.und = undPorReceta;
                    recetaSel.appendChild(opt);
                }
            } else {
                recetaSel.innerHTML =
                    '<option value="">Sin recetas vigentes para este producto</option>';
            }
        })
        .catch(() => {
            recetaSel.innerHTML =
                '<option value="">Error al cargar recetas</option>';
        });
});

// Calcular rendimiento teórico
function recalcularTeorico(undPorReceta) {
    const numRecetas = parseFloat(
        document.getElementById('numRecetas').value
    ) || 0;
    const display = document.getElementById('rendTeoricoDisplay');
    if (numRecetas > 0 && undPorReceta > 0) {
        const teorico = Math.round(numRecetas * undPorReceta);
        display.textContent = teorico.toLocaleString() + ' unidades';
        display.style.color = '#15803d';
    } else {
        display.textContent = '— und';
        display.style.color = '#1d4ed8';
    }
}

document.getElementById('numRecetas').addEventListener('input', function() {
    const sel = document.getElementById('productoSelect');
    const und = parseInt(sel.options[sel.selectedIndex]?.dataset?.und) || 0;
    recalcularTeorico(und);
});
</script>