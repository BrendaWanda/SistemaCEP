<?php
/** @var array $productos
 *  @var array $parametros */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="bi bi-graph-up" style="color:#4f8ef7;margin-right:10px"></i>
            Diagrama de Dispersión
        </h1>
        <p class="page-sub">Correlación entre dos variables del proceso (herramienta básica de calidad)</p>
    </div>
</div>

<!-- ══ FILTROS ══ -->
<div class="card mb-4">
    <div class="card-header" style="background:#090e22;color:#fff">
        <span style="font-weight:600;font-size:15px">
            <i class="bi bi-funnel me-2"></i> Selección de variables
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-4" style="align-items:flex-end">
            <div class="form-group mb-0">
                <label class="form-label">Producto</label>
                <select id="disp_producto" class="form-control">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Variable X</label>
                <select id="disp_param_x" class="form-control">
                    <option value="">-- Seleccione producto primero --</option>
                    <?php foreach ($parametros as $p): if ($p['tipo_dato'] !== 'numerico') continue; ?>
                        <option value="<?= $p['id'] ?>" data-producto="<?= $p['producto_id'] ?>" style="display:none">
                            <?= htmlspecialchars($p['parametro_nombre']) ?> (<?= htmlspecialchars($p['etapa']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Variable Y</label>
                <select id="disp_param_y" class="form-control">
                    <option value="">-- Seleccione producto primero --</option>
                    <?php foreach ($parametros as $p): if ($p['tipo_dato'] !== 'numerico') continue; ?>
                        <option value="<?= $p['id'] ?>" data-producto="<?= $p['producto_id'] ?>" style="display:none">
                            <?= htmlspecialchars($p['parametro_nombre']) ?> (<?= htmlspecialchars($p['etapa']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <button class="btn btn-primary" onclick="analizarDispersion()">
                    <i class="bi bi-graph-up"></i> Correlacionar
                </button>
            </div>
        </div>
        <div class="form-row cols-2" style="margin-top:14px">
            <div class="form-group mb-0">
                <label class="form-label">Fecha desde</label>
                <input type="date" id="disp_fecha_desde" class="form-control">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Fecha hasta</label>
                <input type="date" id="disp_fecha_hasta" class="form-control">
            </div>
        </div>
        <div class="form-hint" style="margin-top:10px;font-size:var(--fs-dense-xs);color:var(--text-muted)">
            <i class="bi bi-info-circle"></i>
            Solo aplica a parámetros numéricos (X̄-R/X-MR). Se compara el promedio de cada
            variable por sesión/lote — se necesitan al menos 5 lotes donde ambas variables
            se hayan medido.
        </div>
    </div>
</div>

<div id="disp-placeholder" class="card mb-3">
    <div class="card-body text-center text-muted" style="padding:50px">
        <i class="bi bi-graph-up" style="font-size:32px;opacity:.4"></i>
        <p style="margin-top:10px">Seleccione producto y dos variables numéricas para ver la correlación.</p>
    </div>
</div>

<div id="disp-resultado" style="display:none">
    <!-- Tarjetas estadísticas -->
    <div id="disp-stat-cards" class="stats-grid mb-3"></div>

    <!-- Interpretación -->
    <div id="disp-interpretacion" class="card mb-3"></div>

    <!-- Gráfico de dispersión -->
    <div class="card mb-3">
        <div class="card-header" style="background:#090e22;color:#fff">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-graph-up me-2"></i> <span id="disp-titulo-grafico">Diagrama de Dispersión</span>
            </span>
        </div>
        <div class="card-body">
            <div style="position:relative;width:100%;height:400px">
                <canvas id="chart-dispersion"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla de pares -->
    <div class="card">
        <div class="card-header" style="background:#090e22;color:#fff">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-table me-2"></i> Datos emparejados por lote
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Fecha</th>
                            <th id="th-disp-x">X</th>
                            <th id="th-disp-y">Y</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-disp-pares"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const appUrl = '<?= APP_URL ?>';
let chartDispersion = null;

// ── Cascada producto → parámetros X/Y (solo numéricos) ──
document.getElementById('disp_producto').addEventListener('change', function() {
    const productoId = this.value;
    ['disp_param_x', 'disp_param_y'].forEach(id => {
        const sel = document.getElementById(id);
        sel.value = '';
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) {
                opt.textContent = productoId ? '-- Seleccione --' : '-- Seleccione producto primero --';
                return;
            }
            opt.style.display = opt.dataset.producto === productoId ? '' : 'none';
        });
    });
});

async function analizarDispersion() {
    const productoId = document.getElementById('disp_producto').value;
    const paramX      = document.getElementById('disp_param_x').value;
    const paramY      = document.getElementById('disp_param_y').value;
    const desde       = document.getElementById('disp_fecha_desde').value;
    const hasta       = document.getElementById('disp_fecha_hasta').value;

    if (!productoId || !paramX || !paramY) {
        alert('Seleccione el producto y ambas variables.');
        return;
    }
    if (paramX === paramY) {
        alert('Seleccione dos variables distintas.');
        return;
    }

    const params = new URLSearchParams({ producto_id: productoId, parametro_x: paramX, parametro_y: paramY });
    if (desde) params.append('fecha_desde', desde);
    if (hasta) params.append('fecha_hasta', hasta);

    document.getElementById('disp-placeholder').innerHTML =
        '<div class="card-body text-center text-muted" style="padding:50px"><i class="bi bi-hourglass-split"></i> Calculando...</div>';
    document.getElementById('disp-placeholder').style.display = 'block';
    document.getElementById('disp-resultado').style.display = 'none';

    try {
        const res  = await fetch(`${appUrl}/m6/dispersion/analizar?` + params);
        const resp = await res.json();
        if (!resp.success) {
            document.getElementById('disp-placeholder').innerHTML =
                `<div class="card-body text-center" style="padding:50px;color:#dc2626">
                    <i class="bi bi-exclamation-triangle"></i> ${resp.message}</div>`;
            return;
        }
        renderDispersion(resp.data);
        document.getElementById('disp-placeholder').style.display = 'none';
        document.getElementById('disp-resultado').style.display = 'block';
    } catch (e) {
        document.getElementById('disp-placeholder').innerHTML =
            `<div class="card-body text-center" style="padding:50px;color:#dc2626">Error de conexión: ${e.message}</div>`;
    }
}

function renderDispersion(d) {
    // Tarjetas
    const colorR = Math.abs(d.r) >= 0.7 ? '#16a34a' : Math.abs(d.r) >= 0.3 ? '#d97706' : '#64748b';
    const cards = [
        { label:'N (lotes emparejados)', value: d.n,   color:'#4f8ef7' },
        { label:'Coeficiente r (Pearson)', value: d.r, color: colorR },
        { label:'r² (determinación)',     value: d.r2, color: colorR },
        { label:'Ecuación de regresión', value: `Y = ${d.b0.toFixed(4)} + ${d.b1.toFixed(4)}·X`, color:'#0f172a', html:true },
        { label:'Estadístico t',          value: d.t_stat, color:'#0f172a' },
        { label:`r crítico (gl=${d.gl}, α=0.05)`, value: d.r_critico, color:'#0f172a' },
    ];
    document.getElementById('disp-stat-cards').innerHTML = cards.map(c => `
        <div class="stat-card" style="border-left-color:${c.color}">
            <div>
                <div class="stat-label">${c.label}</div>
                <div class="stat-value" style="color:${c.color};font-size:${c.html ? '14px' : '20px'}">${c.value}</div>
            </div>
        </div>`).join('');

    // Interpretación
    const bg = d.significativa ? '#f0fdf4' : '#fffbeb';
    const border = d.significativa ? '#16a34a' : '#d97706';
    document.getElementById('disp-interpretacion').innerHTML = `
        <div class="card-body" style="border-left:4px solid ${border};background:${bg}">
            <i class="bi bi-${d.significativa ? 'check-circle' : 'exclamation-triangle'}"
               style="color:${border};margin-right:8px"></i>
            <strong>${d.interpretacion}</strong>
            <div style="font-size:var(--fs-dense-xs);color:#64748b;margin-top:6px">Ref: ${d.ref_biblio}</div>
        </div>`;

    // Gráfico
    document.getElementById('disp-titulo-grafico').textContent = `${d.x_label} vs. ${d.y_label}`;
    document.getElementById('th-disp-x').textContent = d.x_label;
    document.getElementById('th-disp-y').textContent = d.y_label;

    const puntos = d.pares.map(p => ({ x: parseFloat(p.valor_x), y: parseFloat(p.valor_y) }));
    const xs = puntos.map(p => p.x);
    const xMin = Math.min(...xs), xMax = Math.max(...xs);
    const lineaRegresion = [
        { x: xMin, y: d.b0 + d.b1 * xMin },
        { x: xMax, y: d.b0 + d.b1 * xMax },
    ];

    if (chartDispersion) chartDispersion.destroy();
    chartDispersion = new Chart(document.getElementById('chart-dispersion'), {
        type: 'scatter',
        data: {
            datasets: [
                { label: 'Lotes', data: puntos, backgroundColor: '#4f8ef7', pointRadius: 6 },
                { label: 'Línea de regresión', data: lineaRegresion, type: 'line',
                  borderColor: '#ef4444', borderWidth: 2, pointRadius: 0, fill: false, borderDash: [6,3] },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#334155' } },
                tooltip: {
                    callbacks: {
                        label: ctx => `${d.x_label}: ${ctx.parsed.x.toFixed(4)}, ${d.y_label}: ${ctx.parsed.y.toFixed(4)}`
                    }
                }
            },
            scales: {
                x: { title: { display: true, text: d.x_label, color: '#64748b' }, ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' } },
                y: { title: { display: true, text: d.y_label, color: '#64748b' }, ticks: { color: '#64748b' }, grid: { color: '#f1f5f9' } },
            }
        }
    });

    // Tabla
    document.getElementById('tbody-disp-pares').innerHTML = d.pares.map(p => `
        <tr>
            <td style="font-family:monospace;font-weight:700">${p.codigo_lote}</td>
            <td>${new Date(p.fecha).toLocaleDateString('es-BO')}</td>
            <td>${parseFloat(p.valor_x).toFixed(4)}</td>
            <td>${parseFloat(p.valor_y).toFixed(4)}</td>
        </tr>`).join('');
}
</script>