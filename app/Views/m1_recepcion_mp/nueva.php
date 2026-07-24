<?php
/** @var array $insumos @var array $proveedores @var array $usuarios
 *  @var string $codigo @var string $fecha_hoy
 *  @var array $conf_opciones @var array $decisiones */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div class="page-title"><i class="bi bi-plus-lg"></i> Nueva Recepción de Materia Prima</div>
    <div class="page-sub">Código: <strong><?= $codigo ?></strong> · Fecha: <?= date('d/m/Y') ?></div>
</div>

<form method="POST" action="<?= APP_URL ?>/m1/nueva">
<input type="hidden" name="_token" value="<?= $csrfToken ?>">
<input type="hidden" name="codigo_recepcion" value="<?= $codigo ?>">

<!-- SECCIÓN 1: Datos generales -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-clipboard2-check-fill"></i> 1. Datos generales</span>
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
        <span class="card-title"><i class="bi bi-eye-fill"></i> 2. Análisis organoléptico</span>
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

<!-- SECCIÓN 3: Parámetros del insumo (dinámico) -->
<!-- AGREGADO: reemplaza la tabla fija de 7 parámetros fisicoquímicos
     que se pedía a TODOS los insumos por igual (ej. gluten a un
     empaque). Ahora se cargan según lo que esté configurado en M0
     para el insumo elegido — puede ser 0, 3, o 10 parámetros, lo que
     corresponda a ESE insumo específico. -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title"><i class="bi bi-droplet-fill"></i> 3. Parámetros del insumo</span>
    </div>
    <div class="card-body">
        <div id="parametrosContainer">
            <div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px">
                Seleccione un insumo para ver sus parámetros configurados.
            </div>
        </div>
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
        <span class="card-title"><i class="bi bi-check-circle-fill"></i> 4. Conclusión y decisión final</span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3">
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
            <!-- AGREGADO: visto_bueno_id existía en el modelo y se
                 mostraba en ver.php/imprimir.php (con su propia línea de
                 firma en el documento impreso), pero nunca hubo forma de
                 asignarlo — quedaba siempre en NULL. Es opcional a
                 propósito (no toda recepción requiere un tercer visto
                 bueno de inmediato). -->
            <div class="form-group">
                <label class="form-label">Visto bueno (opcional)</label>
                <select name="visto_bueno_id" class="form-control">
                    <option value="">— Sin visto bueno —</option>
                    <?php foreach ($usuarios as $id => $nombre): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
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
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle-fill"></i> Registrar recepción</button>
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

// AGREGADO: cargar los parámetros configurados para el insumo elegido
// (M0 → Insumos → Parámetros) y armar dinámicamente sus campos de
// captura — reemplaza la tabla fija que antes pedía gluten a todo.
const appUrl = '<?= APP_URL ?>';
const confOpciones = <?= json_encode($conf_opciones, JSON_UNESCAPED_UNICODE) ?>;

document.getElementById('insumoSelect').addEventListener('change', function() {
    const insumoId = this.value;
    const cont = document.getElementById('parametrosContainer');

    if (!insumoId) {
        cont.innerHTML = '<div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px">'
            + 'Seleccione un insumo para ver sus parámetros configurados.</div>';
        return;
    }

    cont.innerHTML = '<div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px">Cargando...</div>';

    fetch(`${appUrl}/api/parametros-insumo?insumo_id=${insumoId}`)
        .then(r => r.json())
        .then(resp => {
            const params = resp.data || [];
            if (params.length === 0) {
                cont.innerHTML = '<div style="color:#94a3b8;font-size:13px;text-align:center;padding:16px">'
                    + 'Este insumo no tiene parámetros configurados en M0.</div>';
                return;
            }

            let html = '<div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;'
                + 'gap:8px;margin-bottom:6px;padding:0 4px">'
                + '<div style="font-size:11px;font-weight:700;color:#64748b">Parámetro</div>'
                + '<div style="font-size:11px;font-weight:700;color:#64748b">Resultado</div>'
                + '<div style="font-size:11px;font-weight:700;color:#64748b">Conformidad</div></div>';

            for (const p of params) {
                const rango = (p.valor_min !== null || p.valor_max !== null)
                    ? ` (${p.valor_min ?? '—'} a ${p.valor_max ?? '—'} ${p.unidad || ''})`
                    : (p.unidad ? ` (${p.unidad})` : '');
                const requerido = p.obligatorio == 1 ? ' required' : '';

                let inputResultado;
                if (p.tipo_dato === 'numerico') {
                    inputResultado = `<input type="number" name="resultado[]" class="form-control"
                        step="0.0001" placeholder="Resultado" style="font-size:12px"${requerido}>`;
                } else if (p.tipo_dato === 'si_no') {
                    inputResultado = `<select name="resultado[]" class="form-control" style="font-size:12px"${requerido}>
                        <option value="">—</option><option value="si">Sí</option><option value="no">No</option></select>`;
                } else if (p.tipo_dato === 'seleccion' && p.opciones) {
                    let opts = '<option value="">—</option>';
                    for (const o of p.opciones) opts += `<option value="${o}">${o}</option>`;
                    inputResultado = `<select name="resultado[]" class="form-control" style="font-size:12px"${requerido}>${opts}</select>`;
                } else {
                    inputResultado = `<input type="text" name="resultado[]" class="form-control"
                        placeholder="Resultado" style="font-size:12px"${requerido}>`;
                }

                let optsConf = '<option value="">—</option>';
                for (const [val, info] of Object.entries(confOpciones)) {
                    optsConf += `<option value="${val}">${info.label}</option>`;
                }

                html += `<div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;
                        gap:8px;margin-bottom:8px;align-items:center">
                    <div style="font-size:13px;font-weight:500;color:#374151">
                        ${p.nombre}${rango}${p.obligatorio == 1 ? ' <span class="form-required">*</span>' : ''}
                    </div>
                    <div>${inputResultado}</div>
                    <div><select name="conformidad[]" class="form-control" style="font-size:12px">${optsConf}</select></div>
                    <input type="hidden" name="parametro_insumo_id[]" value="${p.id}">
                </div>`;
            }

            cont.innerHTML = html;
        })
        .catch(() => {
            cont.innerHTML = '<div style="color:#dc2626;font-size:13px;text-align:center;padding:16px">'
                + 'Error al cargar los parámetros de este insumo.</div>';
        });
});
</script>