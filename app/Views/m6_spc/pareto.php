<?php
/** @var array $productos */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="bi bi-bar-chart-steps" style="color:#4f8ef7;margin-right:10px"></i>
            Pareto de Parámetros Críticos
        </h1>
        <p class="page-sub">¿Cuáles parámetros concentran la mayoría de los problemas de este producto?</p>
    </div>
</div>

<!-- ══ FILTROS ══ -->
<div class="card mb-4">
    <div class="card-header" style="background:#090e22;color:#fff">
        <span style="font-weight:600;font-size:15px">
            <i class="bi bi-funnel me-2"></i> Selección
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-4" style="align-items:flex-end">
            <div class="form-group mb-0">
                <label class="form-label">Producto</label>
                <select id="par_producto" class="form-control">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Tipo de Pareto</label>
                <select id="par_tipo" class="form-control">
                    <option value="atributo">Atributos (no conformes — carta p)</option>
                    <option value="variable">Variables (señales SPC — X̄-R/X-MR)</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Estratificación</label>
                <select id="par_estrato" class="form-control">
                    <option value="todos">Todas las etapas</option>
                    <option value="proceso">Solo en proceso (amasado…envasado)</option>
                    <option value="terminado">Solo producto terminado</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <button class="btn btn-primary" onclick="analizarPareto()">
                    <i class="bi bi-bar-chart-steps"></i> Generar Pareto
                </button>
            </div>
        </div>
        <div class="form-row cols-2" style="margin-top:14px">
            <div class="form-group mb-0">
                <label class="form-label">Fecha desde</label>
                <input type="date" id="par_fecha_desde" class="form-control">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Fecha hasta</label>
                <input type="date" id="par_fecha_hasta" class="form-control">
            </div>
        </div>
        <div class="form-hint" style="margin-top:10px;font-size:var(--fs-dense-xs);color:var(--text-muted)">
            <i class="bi bi-info-circle"></i>
            Los Paretos de atributos y variables se generan por separado, cada uno con su
            propia unidad de conteo (no conformes vs. señales SPC) — no se combinan en un
            solo gráfico, porque no comparten la misma unidad de medida.
        </div>
    </div>
</div>

<div id="par-placeholder" class="card mb-3">
    <div class="card-body text-center text-muted" style="padding:50px">
        <i class="bi bi-bar-chart-steps" style="font-size:32px;opacity:.4"></i>
        <p style="margin-top:10px">Seleccione un producto para generar el Pareto.</p>
    </div>
</div>

<div id="par-resultado" style="display:none">
    <div id="par-stat-cards" class="stats-grid mb-3"></div>

    <div id="par-interpretacion" class="card mb-3"></div>

    <div class="card mb-3">
        <div class="card-header" style="background:#090e22;color:#fff">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-bar-chart-steps me-2"></i> <span id="par-titulo-grafico">Diagrama de Pareto</span>
            </span>
        </div>
        <div class="card-body">
            <div style="position:relative;width:100%;height:400px">
                <canvas id="chart-pareto"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="background:#090e22;color:#fff">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-table me-2"></i> Detalle por parámetro
            </span>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-wrap">
                <table class="data-table-bordered" style="font-size:var(--fs-dense-sm)">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Parámetro</th>
                            <th>Etapa</th>
                            <th id="th-par-eventos">Eventos</th>
                            <th>Oportunidades</th>
                            <th>Tasa</th>
                            <th>% relativo</th>
                            <th>% acumulado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-pareto"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const appUrl = '<?= APP_URL ?>';
let chartPareto = null;

async function analizarPareto() {
    const productoId = document.getElementById('par_producto').value;
    const tipo    = document.getElementById('par_tipo').value;
    const estrato = document.getElementById('par_estrato').value;
    const desde = document.getElementById('par_fecha_desde').value;
    const hasta = document.getElementById('par_fecha_hasta').value;

    if (!productoId) { alert('Seleccione un producto.'); return; }

    const params = new URLSearchParams({ producto_id: productoId, tipo, estrato });
    if (desde) params.append('fecha_desde', desde);
    if (hasta) params.append('fecha_hasta', hasta);

    document.getElementById('par-placeholder').innerHTML =
        '<div class="card-body text-center text-muted" style="padding:50px"><i class="bi bi-hourglass-split"></i> Calculando...</div>';
    document.getElementById('par-placeholder').style.display = 'block';
    document.getElementById('par-resultado').style.display = 'none';

    try {
        const res  = await fetch(`${appUrl}/m6/pareto/analizar?` + params);
        const resp = await res.json();
        if (!resp.success) {
            document.getElementById('par-placeholder').innerHTML =
                `<div class="card-body text-center" style="padding:50px;color:#dc2626">
                    <i class="bi bi-exclamation-triangle"></i> ${resp.message}</div>`;
            return;
        }
        renderPareto(resp.data);
        document.getElementById('par-placeholder').style.display = 'none';
        document.getElementById('par-resultado').style.display = 'block';
    } catch (e) {
        document.getElementById('par-placeholder').innerHTML =
            `<div class="card-body text-center" style="padding:50px;color:#dc2626">Error de conexión: ${e.message}</div>`;
    }
}

function renderPareto(d) {
    const etiquetaEventos = d.tipo === 'atributo' ? 'No conformes' : 'Señales SPC';
    const etiquetaTitulo  = d.tipo === 'atributo'
        ? 'Pareto de Atributos (No Conformes)'
        : 'Pareto de Variables (Señales SPC)';
    const etiquetaEstrato = { todos:'todas las etapas', proceso:'etapas en proceso',
                               terminado:'producto terminado' }[d.estrato];

    // Tarjetas
    const cards = [
        { label:`Total de ${etiquetaEventos.toLowerCase()}`, value: d.total_eventos, color:'#dc2626' },
        { label:'Parámetros analizados',   value: d.total_parametros,   color:'#4f8ef7' },
        { label:'Parámetros "vitales" (80%)', value: d.parametros_vitales, color:'#d97706' },
    ];
    document.getElementById('par-stat-cards').innerHTML = cards.map(c => `
        <div class="stat-card" style="border-left-color:${c.color}">
            <div>
                <div class="stat-label">${c.label}</div>
                <div class="stat-value" style="color:${c.color};font-size:20px">${c.value}</div>
            </div>
        </div>`).join('');

    // Interpretación
    document.getElementById('par-titulo-grafico').textContent = etiquetaTitulo;
    const top = d.datos.slice(0, d.parametros_vitales).map(p => p.parametro_nombre).join(', ');
    document.getElementById('par-interpretacion').innerHTML = `
        <div class="card-body" style="border-left:4px solid #d97706;background:#fffbeb">
            <i class="bi bi-lightbulb" style="color:#d97706;margin-right:8px"></i>
            <strong>${etiquetaTitulo} — ${etiquetaEstrato}.</strong>
            El ${d.parametros_vitales} de ${d.total_parametros} parámetro(s) "vital(es)"
            (${top}) concentra${d.parametros_vitales>1?'n':''} el 80% de ${etiquetaEventos.toLowerCase()}.
            Priorizar la investigación de causa raíz ahí antes que en el resto — es donde
            una acción correctiva tiene mayor impacto (principio de Pareto / regla 80-20).
            <div style="font-size:var(--fs-dense-xs);color:#64748b;margin-top:6px">Ref: ${d.ref_biblio}</div>
        </div>`;

    // Gráfico: barras (eventos) + línea (% acumulado) + línea de referencia 80%
    const labels = d.datos.map(p => p.parametro_nombre);
    const barras = d.datos.map(p => p.eventos);
    const acumulado = d.datos.map(p => p.pct_acumulado);
    const linea80 = d.datos.map(() => 80);
    const colores = d.datos.map((p,i) => i < d.parametros_vitales ? '#dc2626' : '#94a3b8');

    if (chartPareto) chartPareto.destroy();
    chartPareto = new Chart(document.getElementById('chart-pareto'), {
        data: {
            labels,
            datasets: [
                { type:'bar', label: etiquetaEventos, data:barras, backgroundColor:colores, yAxisID:'y' },
                { type:'line', label:'% Acumulado', data:acumulado, borderColor:'#1e40af',
                  backgroundColor:'transparent', pointRadius:4, borderWidth:2, yAxisID:'y1', tension:0.1 },
                { type:'line', label:'Corte 80%', data:linea80, borderColor:'#16a34a',
                  borderDash:[6,3], borderWidth:1.5, pointRadius:0, yAxisID:'y1' },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels:{ color:'#334155' } } },
            scales: {
                x: { ticks:{ color:'#64748b', maxRotation:45, font:{size:10} }, grid:{ display:false } },
                y:  { position:'left',  title:{ display:true, text: etiquetaEventos, color:'#64748b' },
                      ticks:{ color:'#64748b' }, grid:{ color:'#f1f5f9' } },
                y1: { position:'right', min:0, max:100,
                      title:{ display:true, text:'% Acumulado', color:'#64748b' },
                      ticks:{ color:'#64748b' }, grid:{ display:false } },
            }
        }
    });

    // Tabla
    document.getElementById('th-par-eventos').textContent = etiquetaEventos;
    document.getElementById('tbody-pareto').innerHTML = d.datos.map((p, i) => `
        <tr style="${i < d.parametros_vitales ? 'background:#fef2f2' : ''}">
            <td>${i + 1}</td>
            <td style="text-align:left">${p.parametro_nombre}</td>
            <td>${p.etapa}</td>
            <td style="font-weight:700;color:#dc2626">${p.eventos}</td>
            <td>${p.oportunidades}</td>
            <td>${p.tasa_pct}%</td>
            <td>${p.pct_relativo}%</td>
            <td style="font-weight:700">${p.pct_acumulado}%</td>
        </tr>`).join('');
}
</script>