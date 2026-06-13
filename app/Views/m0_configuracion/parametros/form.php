<?php
/** @var array $producto @var array|null $parametro @var array $etapas @var array $tipos @var string $accion */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php
// Decodificamos antes de re-escapar: el valor guardado en BD ya pasó por
// htmlspecialchars() al momento de input() en el Controller. Si volvemos a
// escapar aquí sin decodificar primero, cada edición acumula entidades HTML
// (&amp;quot; → &amp;amp;quot; → ...), corrompiendo progresivamente el dato
// (crítico para opciones_json, que contiene comillas dobles).
$v = fn($campo, $def='') => htmlspecialchars(
    html_entity_decode((string)($parametro[$campo] ?? $def), ENT_QUOTES, 'UTF-8'),
    ENT_QUOTES, 'UTF-8'
);
?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Parámetro' : 'Nuevo Parámetro' ?>
        </div>
        <div class="page-sub">
            Producto: <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros"
            class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a parámetros
        </a>
    </div>
</div>

<div class="card" style="max-width:700px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-sliders" style="color:#4f8ef7;margin-right:6px"></i>
            Datos del parámetro
        </span>
    </div>
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
                            placeholder="Ej: °C, min, g, u" required>
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
                        <div class="form-hint">
                            <i class="bi bi-info-circle"></i> Valor esperado del proceso
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor mínimo operativo</label>
                        <input type="number" name="valor_min" class="form-control"
                                value="<?= $v('valor_min') ?>" step="0.0001">
                        <div class="form-hint">
                            <i class="bi bi-info-circle"></i> Límite inferior
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor máximo operativo</label>
                        <input type="number" name="valor_max" class="form-control"
                                value="<?= $v('valor_max') ?>" step="0.0001">
                        <div class="form-hint">
                            <i class="bi bi-info-circle"></i> Límite superior
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opciones selección -->
            <div id="seccionOpciones"
                style="<?= $v('tipo_dato') !== 'seleccion' ? 'display:none' : '' ?>">
                <div class="form-group">
                    <label class="form-label">Opciones (JSON)</label>
                    <input type="text" name="opciones_json" class="form-control"
                            value="<?= $v('opciones_json') ?>"
                            placeholder='["conforme","no_conforme","na"]'>
                    <div class="form-hint">
                        <i class="bi bi-info-circle"></i>
                        Lista en formato JSON. Ej: ["conforme","no_conforme","na"]
                    </div>
                </div>
            </div>

            <!-- Configuración SPC — visible para numérico, selección y sí/no -->
            <?php
            $tipoDatoActual = $v('tipo_dato','numerico');
            $mostrarSPC     = in_array($tipoDatoActual, ['numerico','seleccion','si_no']);
            $esAtributo     = in_array($tipoDatoActual, ['seleccion','si_no']);
            ?>
            <div id="seccionSPC"
                    style="border:1px solid #e2e8f0;border-radius:8px;
                        padding:16px;margin-bottom:16px;background:#fafbfc;
                        <?= !$mostrarSPC ? 'display:none' : '' ?>">

                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <input type="checkbox" name="es_variable_spc" id="esSPC"
                            <?= ($parametro['es_variable_spc'] ?? false) ? 'checked' : '' ?>
                            style="width:16px;height:16px;accent-color:#4f8ef7;cursor:pointer">
                    <label for="esSPC"
                            style="font-weight:600;font-size:14px;cursor:pointer;
                                display:flex;align-items:center;gap:7px;color:#0f172a">
                        <i class="bi bi-graph-up-arrow" style="color:#4f8ef7"></i>
                        Esta variable se grafica en el SPC
                        <span id="labelTipoCarta" style="
                            background:<?= $esAtributo ? '#dcfce7' : '#dbeafe' ?>;
                            color:<?= $esAtributo ? '#14532d' : '#1e40af' ?>;
                            padding:2px 8px;border-radius:6px;
                            font-size:12px;font-weight:700">
                            <?= $esAtributo ? 'Carta p' : 'X̄-R / X-MR' ?>
                        </span>
                    </label>
                </div>

                <div id="subgrupoSection"
                        style="<?= ($parametro['es_variable_spc'] ?? false) ? '' : 'display:none' ?>">

                    <!-- Para numérico: tamaño de subgrupo -->
                    <div id="campoSubgrupoNum"
                         style="<?= $esAtributo ? 'display:none' : '' ?>">
                        <div class="form-group" style="max-width:220px;margin-bottom:0">
                            <label class="form-label">Tamaño de subgrupo (n)</label>
                            <input type="number" name="tamanio_subgrupo_num"
                                   id="inputSubgrupoNum"
                                   class="form-control"
                                   value="<?= $v('tamanio_subgrupo','5') ?>"
                                   min="1" max="25">
                            <div class="form-hint">n=1 → X-MR · n≥2 → X̄-R</div>
                        </div>
                    </div>

                    <!-- Para atributos: tamaño de muestra carta p -->
                    <div id="campoSubgrupoAtrib"
                         style="<?= !$esAtributo ? 'display:none' : '' ?>">
                        <div class="form-group" style="max-width:220px;margin-bottom:0">
                            <label class="form-label">Tamaño de muestra (n)</label>
                            <input type="number" name="tamanio_subgrupo_atrib"
                                   id="inputSubgrupoAtrib"
                                   class="form-control"
                                   value="<?= $v('tamanio_subgrupo','50') ?>"
                                   min="10" max="200">
                            <div class="form-hint">
                                Unidades inspeccionadas por lote (Carta p)
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Obligatorio -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="obligatorio"
                            <?= ($parametro['obligatorio'] ?? 1) ? 'checked' : '' ?>
                            style="width:16px;height:16px;accent-color:#4f8ef7">
                    <span style="font-weight:500;font-size:14px">
                        Campo obligatorio en el formulario de registro de proceso
                    </span>
                </label>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/productos/<?= $producto['id'] ?>/parametros"
                class="btn btn-secondary">
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
document.getElementById('tipoDato').addEventListener('change', function () {
    const esNum   = this.value === 'numerico';
    const esSel   = this.value === 'seleccion';
    const esSiNo  = this.value === 'si_no';
    const esAtrib = esSel || esSiNo;

    document.getElementById('seccionNumerica').style.display    = esNum   ? '' : 'none';
    document.getElementById('seccionOpciones').style.display    = esSel   ? '' : 'none';
    document.getElementById('seccionSPC').style.display         = (esNum || esAtrib) ? '' : 'none';
    document.getElementById('campoSubgrupoNum').style.display   = esNum   ? '' : 'none';
    document.getElementById('campoSubgrupoAtrib').style.display = esAtrib ? '' : 'none';

    // Actualizar badge tipo carta
    const badge = document.getElementById('labelTipoCarta');
    badge.textContent      = esNum ? 'X̄-R / X-MR' : 'Carta p';
    badge.style.background = esNum ? '#dbeafe' : '#dcfce7';
    badge.style.color      = esNum ? '#1e40af' : '#14532d';

    // Sincronizar valores por defecto
    if (esNum)   document.getElementById('inputSubgrupoNum').value   = '5';
    if (esAtrib) document.getElementById('inputSubgrupoAtrib').value = '50';
});

document.getElementById('esSPC').addEventListener('change', function () {
    document.getElementById('subgrupoSection').style.display =
        this.checked ? '' : 'none';
});
</script>