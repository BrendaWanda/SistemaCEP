<?php
/** @var array $productos
 *  @var array $usuarios
 *  @var bool  $canWrite */
$csrfToken = \App\Core\Controller::csrfToken();
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="bi bi-exclamation-diamond" style="color:#dc2626;margin-right:10px"></i>
            Gestión de Señales SPC
        </h1>
        <p class="page-sub">Investigación y cierre de señales detectadas por las reglas de Nelson en M2</p>
    </div>
</div>

<!-- ══ FILTROS ══ -->
<div class="card mb-4">
    <div class="card-header" style="background:#090e22;color:#fff">
        <span style="font-weight:600;font-size:15px">
            <i class="bi bi-funnel me-2"></i> Filtros
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3" style="align-items:flex-end">
            <div class="form-group mb-0">
                <label class="form-label">Estado</label>
                <select id="fil_estado" class="form-control" onchange="cargarSenales()">
                    <option value="pendientes" selected>Pendientes (nueva + investigando)</option>
                    <option value="nueva">Solo nuevas</option>
                    <option value="investigando">Solo en investigación</option>
                    <option value="resuelta">Resueltas</option>
                    <option value="falsa_alarma">Falsas alarmas</option>
                    <option value="todos">Todos los estados</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Producto</label>
                <select id="fil_producto_senal" class="form-control" onchange="cargarSenales()">
                    <option value="">-- Todos --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <button class="btn btn-secondary" onclick="cargarSenales()">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ TABLA ══ -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Producto</th>
                        <th>Parámetro</th>
                        <th>Regla</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <?php if ($canWrite): ?><th>Acciones</th><?php endif ?>
                    </tr>
                </thead>
                <tbody id="tbody-senales">
                    <tr><td colspan="8" class="text-center text-muted" style="padding:30px">
                        Cargando señales...
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const appUrl    = '<?= APP_URL ?>';
const csrfToken = '<?= $csrfToken ?>';
const canWrite  = <?= $canWrite ? 'true' : 'false' ?>;
const usuarios  = <?= json_encode($usuarios, JSON_UNESCAPED_UNICODE) ?>; // {id: nombre}

const ESTADO_BADGE = {
    nueva:         { label:'Nueva',          bg:'#dc2626' },
    investigando:  { label:'Investigando',   bg:'#d97706' },
    resuelta:      { label:'Resuelta',       bg:'#16a34a' },
    falsa_alarma:  { label:'Falsa alarma',   bg:'#64748b' },
};

async function cargarSenales() {
    const estado  = document.getElementById('fil_estado').value;
    const prodId  = document.getElementById('fil_producto_senal').value;
    const tbody   = document.getElementById('tbody-senales');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted" style="padding:30px">Cargando...</td></tr>`;

    const params = new URLSearchParams({ estado });
    if (prodId) params.append('producto_id', prodId);

    try {
        const res  = await fetch(`${appUrl}/m6/senales/listar?` + params);
        const resp = await res.json();
        if (!resp.success) { tbody.innerHTML = `<tr><td colspan="8">${resp.message}</td></tr>`; return; }

        const senales = resp.data.senales;
        if (senales.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted" style="padding:30px">
                No hay señales con estos filtros.</td></tr>`;
            return;
        }

        tbody.innerHTML = senales.map(s => filaSenal(s)).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="8">Error de conexión: ${e.message}</td></tr>`;
    }
}

function filaSenal(s) {
    const badge = ESTADO_BADGE[s.estado] || { label:s.estado, bg:'#64748b' };
    const fecha = new Date(s.creado_en.replace(' ', 'T')).toLocaleString('es-BO', {
        day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'
    });

    let accionesHtml = '—';
    if (canWrite) {
        if (s.estado === 'nueva') {
            accionesHtml = `<button class="btn btn-sm btn-secondary" onclick="mostrarAsignar(${s.id})">
                <i class="bi bi-person-plus"></i> Asignar</button>`;
        } else if (s.estado === 'investigando') {
            accionesHtml = `<button class="btn btn-sm btn-secondary" onclick="mostrarResolver(${s.id})">
                <i class="bi bi-check2-square"></i> Resolver</button>`;
        }
    }

    const detalle = (s.estado === 'resuelta' || s.estado === 'falsa_alarma')
        ? `<div style="font-size:var(--fs-dense-xs);color:#64748b;margin-top:4px">
             <strong>Causa:</strong> ${s.causa_identificada || '—'}<br>
             ${s.accion_tomada ? '<strong>Acción:</strong> ' + s.accion_tomada + '<br>' : ''}
             Resuelto por ${s.resuelta_por_nombre || '—'}
             ${s.resuelta_en ? ' el ' + new Date(s.resuelta_en.replace(' ','T')).toLocaleDateString('es-BO') : ''}
           </div>`
        : (s.asignado_nombre ? `<div style="font-size:var(--fs-dense-xs);color:#64748b;margin-top:4px">
             Asignado a: <strong>${s.asignado_nombre}</strong></div>` : '');

    return `
    <tr id="fila-senal-${s.id}">
        <td style="font-family:monospace;font-weight:700">${s.codigo_lote}</td>
        <td>${s.producto_nombre}</td>
        <td>${s.parametro_nombre || '—'}</td>
        <td style="text-align:left">${s.descripcion_regla}</td>
        <td>${s.valor_detectado !== null ? parseFloat(s.valor_detectado).toFixed(4) : '—'}</td>
        <td>
            <span style="background:${badge.bg};color:#fff;padding:2px 8px;border-radius:10px;
                         font-size:var(--fs-dense-xs);font-weight:700">${badge.label}</span>
            ${detalle}
        </td>
        <td>${fecha}</td>
        ${canWrite ? `<td id="acciones-senal-${s.id}">${accionesHtml}</td>` : ''}
    </tr>
    <tr id="form-senal-${s.id}" style="display:none">
        <td colspan="8" style="background:#f8fafc;padding:14px 18px" id="form-contenido-${s.id}"></td>
    </tr>`;
}

function mostrarAsignar(id) {
    const opciones = Object.entries(usuarios).map(([uid, nombre]) =>
        `<option value="${uid}">${nombre}</option>`).join('');
    document.getElementById(`form-contenido-${id}`).innerHTML = `
        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group mb-0" style="min-width:220px">
                <label class="form-label">Asignar investigación a</label>
                <select id="asignar-usuario-${id}" class="form-control">
                    <option value="">-- Seleccione --</option>
                    ${opciones}
                </select>
            </div>
            <button class="btn btn-primary" onclick="confirmarAsignar(${id})">
                <i class="bi bi-check-lg"></i> Confirmar
            </button>
            <button class="btn btn-secondary" onclick="cerrarForm(${id})">Cancelar</button>
        </div>`;
    document.getElementById(`form-senal-${id}`).style.display = 'table-row';
}

async function confirmarAsignar(id) {
    const usuarioId = document.getElementById(`asignar-usuario-${id}`).value;
    if (!usuarioId) { alert('Seleccione a quién asignar.'); return; }

    const fd = new FormData();
    fd.append('_token', csrfToken);
    fd.append('asignado_a_id', usuarioId);

    try {
        const res  = await fetch(`${appUrl}/m6/senales/${id}/asignar`, { method:'POST', body: fd });
        const resp = await res.json();
        if (resp.success) { cargarSenales(); } else { alert(resp.message); }
    } catch (e) { alert('Error de conexión: ' + e.message); }
}

function mostrarResolver(id) {
    document.getElementById(`form-contenido-${id}`).innerHTML = `
        <div style="display:flex;flex-direction:column;gap:10px">
            <div class="form-group mb-0">
                <label class="form-label">Causa identificada <span class="form-required">*</span></label>
                <textarea id="resolver-causa-${id}" class="form-control" rows="2"
                    placeholder="¿Qué originó la señal?"></textarea>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Acción tomada</label>
                <textarea id="resolver-accion-${id}" class="form-control" rows="2"
                    placeholder="¿Qué se hizo para corregirlo?"></textarea>
            </div>
            <div style="display:flex;gap:16px;align-items:center">
                <label style="display:flex;align-items:center;gap:6px;font-size:var(--fs-dense-sm)">
                    <input type="radio" name="estado-final-${id}" value="resuelta" checked> Resuelta (causa real)
                </label>
                <label style="display:flex;align-items:center;gap:6px;font-size:var(--fs-dense-sm)">
                    <input type="radio" name="estado-final-${id}" value="falsa_alarma"> Falsa alarma
                </label>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-primary" onclick="confirmarResolver(${id})">
                    <i class="bi bi-check-lg"></i> Guardar resolución
                </button>
                <button class="btn btn-secondary" onclick="cerrarForm(${id})">Cancelar</button>
            </div>
        </div>`;
    document.getElementById(`form-senal-${id}`).style.display = 'table-row';
}

async function confirmarResolver(id) {
    const causa  = document.getElementById(`resolver-causa-${id}`).value.trim();
    const accion = document.getElementById(`resolver-accion-${id}`).value.trim();
    const estadoFinal = document.querySelector(`input[name="estado-final-${id}"]:checked`).value;

    if (!causa) { alert('Indique la causa identificada.'); return; }

    const fd = new FormData();
    fd.append('_token', csrfToken);
    fd.append('causa_identificada', causa);
    fd.append('accion_tomada', accion);
    fd.append('estado_final', estadoFinal);

    try {
        const res  = await fetch(`${appUrl}/m6/senales/${id}/resolver`, { method:'POST', body: fd });
        const resp = await res.json();
        if (resp.success) { cargarSenales(); } else { alert(resp.message); }
    } catch (e) { alert('Error de conexión: ' + e.message); }
}

function cerrarForm(id) {
    document.getElementById(`form-senal-${id}`).style.display = 'none';
}

document.addEventListener('DOMContentLoaded', cargarSenales);
</script>