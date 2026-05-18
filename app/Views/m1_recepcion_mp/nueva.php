<?php
/** @var array $insumos @var array $proveedores @var array $usuarios
 *  @var string $codigo @var string $fecha_hoy
 *  @var array $conf_opciones @var array $decisiones */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div class="page-title">➕ Nueva Recepción de Materia Prima</div>
    <div class="page-sub">Código: <strong><?= $codigo ?></strong> · Fecha: <?= date('d/m/Y') ?></div>
</div>

<form method="POST" action="<?= APP_URL ?>/m1/nueva">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">
<input type="hidden" name="codigo_recepcion" value="<?= $codigo ?>">

<!-- SECCIÓN 1: Datos generales -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">📋 1. Datos generales</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Insumo / Materia Prima <span class="form-required">*</span>
                </label>
                <select name="insumo_id" class="form-control" required id="insumoSelect">
                    <option value="">Seleccione el insumo...</option>
                    <?php foreach ($insumos as $id => $nombre): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">
                    Proveedor <span class="form-required">*</span>
                </label>
                <select name="proveedor_id" class="form-control" required>
                    <option value="">Seleccione el proveedor...</option>
                    <?php foreach ($proveedores as $id => $nombre): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">
                    Lote del proveedor <span class="form-required">*</span>
                </label>
                <input type="text" name="lote_proveedor" class="form-control"
                        placeholder="Nro. de lote del proveedor" required>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de fabricación</label>
                <input type="date" name="fecha_fabricacion" class="form-control"
                        max="<?= $fecha_hoy ?>">
            </div>
            <div class="form-group">
                <label class="form-label">
                    Fecha de vencimiento <span class="form-required">*</span>
                </label>
                <input type="date" name="fecha_vencimiento" class="form-control"
                        min="<?= $fecha_hoy ?>" required>
            </div>
        </div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">
                    Cantidad recibida <span class="form-required">*</span>
                </label>
                <div class="input-group">
                    <input type="number" name="cantidad_recibida" class="form-control"
                            step="0.001" min="0" placeholder="0.000" required>
                    <select name="unidad_medida" class="form-control"
                            style="max-width:80px;border-radius:0 4px 4px 0">
                        <?php foreach (['kg','g','l','ml','und'] as $u): ?>
                        <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tamaño de muestra</label>
                <input type="number" name="tamanio_muestra" class="form-control"
                        step="0.001" min="0" placeholder="0.000">
            </div>
            <div class="form-group">
                <label class="form-label">N° Registro sanitario</label>
                <input type="text" name="nro_registro_sanitario" class="form-control"
                        placeholder="Nro. registro sanitario">
            </div>
        </div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Presentación</label>
                <input type="text" name="presentacion" class="form-control"
                        placeholder="Ej: Bolsa 25kg">
            </div>
            <div class="form-group">
                <label class="form-label">Envase primario</label>
                <input type="text" name="envase_primario" class="form-control"
                        placeholder="Ej: Bolsa polietileno">
            </div>
            <div class="form-group">
                <label class="form-label">Envase secundario</label>
                <input type="text" name="envase_secundario" class="form-control"
                        placeholder="Ej: Saco de papel kraft">
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Fecha de muestreo</label>
                <input type="date" name="fecha_muestreo" class="form-control"
                        value="<?= $fecha_hoy ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de análisis</label>
                <input type="date" name="fecha_analisis" class="form-control"
                        value="<?= $fecha_hoy ?>">
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: Análisis organoléptico -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">👁️ 2. Análisis organoléptico</span>
    </div>
    <div class="card-body">
        <?php
        $orgFields = [
            ['sabor_olor',        'conf_sabor_olor',        'Sabor / Olor'],
            ['color',             'conf_color',             'Color'],
            ['descripcion_fisica','conf_descripcion_fisica', 'Descripción física'],
        ];
        foreach ($orgFields as [$campo, $confCampo, $label]):
        ?>
        <div class="form-row cols-2" style="margin-bottom:8px;align-items:end">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label"><?= $label ?> observado</label>
                <input type="text" name="<?= $campo ?>" class="form-control"
                        placeholder="Describe lo observado...">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Conformidad</label>
                <select name="<?= $confCampo ?>" class="form-control">
                    <option value="">— Seleccione —</option>
                    <?php foreach ($conf_opciones as $val => $info): ?>
                    <option value="<?= $val ?>"><?= $info['label'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <?php endforeach ?>
        <div class="form-group" style="margin-top:8px">
            <label class="form-label">Observaciones organolépticas</label>
            <textarea name="obs_organoleptico" class="form-control" rows="2"
                        placeholder="Observaciones adicionales..."></textarea>
        </div>
    </div>
</div>

<!-- SECCIÓN 3: Análisis fisicoquímico -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">🧪 3. Análisis fisicoquímico</span>
    </div>
    <div class="card-body">
        <!-- Encabezado de columnas -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;
                    gap:8px;margin-bottom:6px;padding:0 4px">
            <div style="font-size:11px;font-weight:700;color:#64748b">Parámetro</div>
            <div style="font-size:11px;font-weight:700;color:#64748b">Especificación</div>
            <div style="font-size:11px;font-weight:700;color:#64748b">Resultado</div>
            <div style="font-size:11px;font-weight:700;color:#64748b">Conformidad</div>
        </div>
        <?php
        $fisicoFields = [
            ['Humedad (%)',       'esp_humedad',        'res_humedad',        'conf_humedad'],
            ['pH',               'esp_ph',             'res_ph',             'conf_ph'],
            ['Densidad',         'esp_densidad',       'res_densidad',       'conf_densidad'],
            ['Gluten (%)',       'esp_gluten',         'res_gluten',         'conf_gluten'],
            ['Brix (°)',         'esp_brix',           'res_brix',           'conf_brix'],
            ['Actividad agua',   'esp_actividad_agua', 'res_actividad_agua', 'conf_actividad_agua'],
            ['Impurezas (%)',    'esp_impurezas',      'res_impurezas',      'conf_impurezas'],
        ];
        foreach ($fisicoFields as [$label, $espCampo, $resCampo, $confCampo]):
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;
                    gap:8px;margin-bottom:8px;align-items:center">
            <div style="font-size:13px;font-weight:500;color:#374151">
                <?= $label ?>
            </div>
            <div>
                <input type="text" name="<?= $espCampo ?>" class="form-control"
                        placeholder="Especif. de referencia"
                        style="font-size:12px">
            </div>
            <div>
                <input type="number" name="<?= $resCampo ?>" class="form-control"
                        step="0.0001" placeholder="Resultado"
                        style="font-size:12px">
            </div>
            <div>
                <select name="<?= $confCampo ?>" class="form-control" style="font-size:12px">
                    <option value="">—</option>
                    <?php foreach ($conf_opciones as $val => $info): ?>
                    <option value="<?= $val ?>"><?= $info['label'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <?php endforeach ?>
        <div class="form-group" style="margin-top:8px">
            <label class="form-label">Observaciones fisicoquímicas</label>
            <textarea name="obs_fisicoquimico" class="form-control" rows="2"
                        placeholder="Observaciones adicionales..."></textarea>
        </div>
    </div>
</div>

<!-- SECCIÓN 4: Conclusión y decisión -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title">✅ 4. Conclusión y decisión final</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Responsable de muestreo</label>
                <select name="responsable_muestreo_id" class="form-control">
                    <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                            <?= $id == \App\Core\Auth::id() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable de análisis</label>
                <select name="responsable_analisis_id" class="form-control">
                    <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                            <?= $id == \App\Core\Auth::id() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Conclusión del análisis</label>
            <textarea name="conclusion" class="form-control" rows="3"
                        placeholder="Conclusión general del análisis de la muestra...">
            </textarea>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Decisión final <span class="form-required">*</span>
                </label>
                <select name="decision" class="form-control" required id="decisionSelect">
                    <option value="">— Seleccione la decisión —</option>
                    <?php foreach ($decisiones as $val => $info): ?>
                    <option value="<?= $val ?>"><?= $info['label'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group" id="stockSection">
                <label class="form-label">Cantidad aprobada para stock (kg/l/und)</label>
                <input type="number" name="stock_disponible_kg" class="form-control"
                        step="0.001" min="0" id="stockInput"
                        placeholder="Cantidad que ingresa al inventario">
                <div class="form-hint">
                    Solo se activa si la decisión es "Aprobado"
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Observaciones generales</label>
            <textarea name="obs_generales" class="form-control" rows="2"
                        placeholder="Observaciones adicionales..."></textarea>
        </div>
    </div>
</div>

<div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:24px">
    <a href="<?= APP_URL ?>/m1" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">✅ Registrar recepción</button>
</div>

</form>

<script>
// Activar/desactivar campo de stock según decisión
document.getElementById('decisionSelect').addEventListener('change', function() {
    const stockInput = document.getElementById('stockInput');
    const esAprobado = this.value === 'aprobado';
    stockInput.disabled = !esAprobado;
    stockInput.style.background = esAprobado ? '#fff' : '#f8fafc';
    if (!esAprobado) stockInput.value = '';
});
// Inicializar deshabilitado
document.getElementById('stockInput').disabled = true;
document.getElementById('stockInput').style.background = '#f8fafc';
</script>