<?php
/** @var array $producto @var array|null $parametro @var array $etapas @var array $tipos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($parametro[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Parámetro' : '➕ Nuevo Parámetro' ?>
    </div>
    <div class="page-sub">
        Producto: <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
    </div>
</div>

<div class="card" style="max-width:700px">
    <div class="card-header"><span class="card-title">Datos del parámetro</span></div>
    <form method="POST"
            action="<?= APP_URL ?>/m0/<?= $esEditar
                ? 'parametros/'.$parametro['id'].'/editar'
                : 'productos/'.$producto['id'].'/parametros/nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Etapa del proceso <span class="form-required">*</span>
                    </label>
                    <select name="etapa" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($etapas as $key => $label): ?>
                        <option value="<?= $key ?>"
                                <?= $v('etapa') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Tipo de dato <span class="form-required">*</span>
                    </label>
                    <select name="tipo_dato" class="form-control" id="tipoDato" required>
                        <?php foreach ($tipos as $key => $label): ?>
                        <option value="<?= $key ?>"
                                <?= $v('tipo_dato','numerico') === $key ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Nombre del parámetro <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                            value="<?= $v('nombre') ?>"
                            placeholder="Ej: Temperatura de horno" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Unidad de medida <span class="form-required">*</span>
                    </label>
                    <input type="text" name="unidad" class="form-control"
                            value="<?= $v('unidad') ?>"
                            placeholder="Ej: °C, min, g, %" required>
                </div>
            </div>

            <!-- Valores numéricos -->
            <div id="seccionNumerica"
                    style="<?= $v('tipo_dato','numerico') !== 'numerico' ? 'display:none' : '' ?>">
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Valor nominal</label>
                        <input type="number" name="valor_nominal" class="form-control"
                                value="<?= $v('valor_nominal') ?>" step="0.0001">
                        <div class="form-hint">Valor esperado del proceso</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor mínimo operativo</label>
                        <input type="number" name="valor_min" class="form-control"
                                value="<?= $v('valor_min') ?>" step="0.0001">
                        <div class="form-hint">Límite inferior de operación</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor máximo operativo</label>
                        <input type="number" name="valor_max" class="form-control"
                                value="<?= $v('valor_max') ?>" step="0.0001">
                        <div class="form-hint">Límite superior de operación</div>
                    </div>
                </div>
            </div>

            <!-- Opciones para tipo selección -->
            <div id="seccionOpciones"
                    style="<?= $v('tipo_dato') !== 'seleccion' ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label class="form-label">Opciones (JSON)</label>
                    <input type="text" name="opciones_json" class="form-control"
                            value="<?= $v('opciones_json') ?>"
                            placeholder='["conforme","no_conforme","na"]'>
                    <div class="form-hint">
                        Lista en formato JSON. Ej: ["conforme","no_conforme","na"]
                    </div>
                </div>
            </div>

            <!-- Configuración SPC -->
            <div id="seccionSPC"
                    style="border:1px solid #e2e8f0;border-radius:8px;padding:14px;
                        margin-bottom:16px;
                        <?= $v('tipo_dato','numerico') !== 'numerico' ? 'display:none' : '' ?>">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <input type="checkbox" name="es_variable_spc" id="esSPC"
                            <?= ($parametro['es_variable_spc'] ?? false) ? 'checked' : '' ?>
                            style="width:16px;height:16px">
                    <label for="esSPC" style="font-weight:600;font-size:13px;cursor:pointer">
                        📊 Esta variable se grafica en el SPC (gráfico X̄-R)
                    </label>
                </div>
                <div id="subgrupoSection"
                        style="<?= ($parametro['es_variable_spc'] ?? false) ? '' : 'display:none' ?>">
                    <div class="form-group" style="max-width:220px;margin-bottom:0">
                        <label class="form-label">Tamaño de subgrupo (n)</label>
                        <input type="number" name="tamanio_subgrupo" class="form-control"
                                value="<?= $v('tamanio_subgrupo','10') ?>" min="2" max="25">
                        <div class="form-hint">
                            El formulario físico usa n=10
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="obligatorio"
                            <?= ($parametro['obligatorio'] ?? 1) ? 'checked' : '' ?>
                            style="width:16px;height:16px">
                    <span style="font-weight:500">
                        Campo obligatorio en el formulario de registro de proceso
                    </span>
                </label>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros"
                class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear parámetro' ?>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('tipoDato').addEventListener('change', function () {
    const esNum = this.value === 'numerico';
    const esSel = this.value === 'seleccion';
    document.getElementById('seccionNumerica').style.display = esNum ? '' : 'none';
    document.getElementById('seccionSPC').style.display      = esNum ? '' : 'none';
    document.getElementById('seccionOpciones').style.display = esSel ? '' : 'none';
});

document.getElementById('esSPC').addEventListener('change', function () {
    document.getElementById('subgrupoSection').style.display =
        this.checked ? '' : 'none';
});
</script>