<?php
// app/Views/m6_spc/index.php
?>

<style>
/* ══ ESTILOS DE IMPRESIÓN ══ */
@media print {
    /* Ocultar elementos de navegación y filtros */
    .sidebar, .topbar, nav, .page-header .page-actions,
    #filtros-spc, #spc-placeholder, #spc-loading,
    #btn-imprimir-spc, .btn, button,
    .fase-banner-btns { display: none !important; }

    /* Sin márgenes en body */
    body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }

    /* Encabezado de impresión */
    #print-header { display: block !important; }

    /* Cards sin sombras */
    .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important;
            break-inside: avoid; margin-bottom: 12px !important; }
    .card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* Gráficos — tamaño fijo para impresión */
    canvas { max-width: 100% !important; height: auto !important; }

    /* Tablas */
    table { break-inside: avoid; font-size: 11px !important; }
    th, td { padding: 4px 6px !important; }

    /* Tarjetas estadísticas en grid */
    .stats-grid { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; }

    /* Advertencias */
    .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* Saltos de página */
    #seccion-anova, #seccion-indices-adic { page-break-before: auto; }
    #seccion-curva-oc { page-break-before: always; }

    /* Mostrar URLs de referencia */
    a[href]:after { content: " (" attr(href) ")"; font-size: 9px; color: #64748b; }

    /* Pie de página de impresión */
    #print-footer { display: block !important; }
}

/* Ocultar header y footer de print en pantalla */
#print-header, #print-footer { display: none; }
</style>

<!-- Header oculto que aparece solo al imprimir -->
<div id="print-header" style="display:none;padding:16px 0;border-bottom:2px solid #090e22;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-size:18px;font-weight:700;color:#090e22">
                Industrias Alimenticias Gustossi SRL
            </div>
            <div style="font-size:13px;color:#64748b">
                SIACEP — Sistema de Control Estadístico de Proceso (M6)
            </div>
        </div>
        <div style="text-align:right;font-size:12px;color:#64748b">
            <div id="print-producto-param" style="font-weight:600"></div>
            <div>Fecha impresión: <?= date('d/m/Y H:i') ?></div>
            <div>Fase I — Análisis Retrospectivo</div>
        </div>
    </div>
</div>

<!-- Footer oculto que aparece solo al imprimir -->
<div id="print-footer" style="display:none;margin-top:24px;padding-top:12px;
     border-top:1px solid #e2e8f0;font-size:10px;color:#94a3b8;text-align:center">
    Generado por SIACEP · Industrias Alimenticias Gustossi SRL ·
    Metodología: Montgomery, D.C. (2013). Introduction to Statistical Quality Control, 7th Ed.
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="bi bi-graph-up-arrow" style="color:#4f8ef7;margin-right:10px"></i>
            M6 — Control Estadístico de Proceso
        </h1>
        <p class="page-sub">Cartas de control X̄-R · X̄-S · X-MR · Carta p · Análisis de capacidad</p>
    </div>
    <div class="page-actions">
        <button id="btn-imprimir-spc" onclick="imprimirAnalisis()"
                class="btn btn-secondary" style="display:none">
            <i class="bi bi-printer"></i> Exportar / Imprimir
        </button>
    </div>
</div>

<!-- ══ FILTROS ══ -->
<div class="card mb-4" id="filtros-spc">
    <div class="card-header" style="background:#090e22;color:#fff">
        <span style="font-weight:600;font-size:15px">
            <i class="bi bi-funnel me-2"></i> Filtros de Análisis
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-4" style="align-items:flex-end">
            <div class="form-group mb-0">
                <label class="form-label">Producto</label>
                <select id="fil_producto" class="form-control">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Parámetro de Control</label>
                <select id="fil_parametro" class="form-control">
                    <option value="">-- Seleccione producto primero --</option>
                    <?php foreach ($parametros as $p): ?>
                        <option value="<?= $p['id'] ?>"
                                data-lse="<?= $p['lse'] ?>"
                                data-lie="<?= $p['lie'] ?>"
                                data-unidad="<?= htmlspecialchars($p['unidad'] ?? '') ?>"
                                data-producto="<?= $p['producto_id'] ?>"
                                data-n="<?= $p['tamanio_subgrupo'] ?>"
                                style="display:none">
                            <?= htmlspecialchars($p['parametro_nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Fecha Desde</label>
                <input type="date" id="fil_desde" class="form-control">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" id="fil_hasta" class="form-control">
            </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:16px;align-items:center;flex-wrap:wrap">
            <button onclick="cargarAnalisis()" class="btn btn-primary">
                <i class="bi bi-bar-chart"></i> Analizar
            </button>
            <button onclick="limpiarFiltros()" class="btn btn-secondary">
                <i class="bi bi-x-lg"></i> Limpiar
            </button>
            <div id="specs-banner" style="display:none;
                margin-left:8px;display:none;gap:20px;
                background:#eff6ff;border:1px solid #bfdbfe;
                border-radius:8px;padding:8px 16px;font-size:14px;color:#1e40af">
                <span><i class="bi bi-arrow-up-circle me-1"></i>LSE: <strong id="val-lse">—</strong></span>
                <span><i class="bi bi-arrow-down-circle me-1"></i>LIE: <strong id="val-lie">—</strong></span>
                <span><i class="bi bi-rulers me-1"></i>Tolerancia: <strong id="val-tol">—</strong></span>
                <span id="val-carta-tipo" style="background:#1e3a5f;color:#93c5fd;
                    padding:2px 10px;border-radius:10px;font-size:12px;font-weight:700"></span>
            </div>
        </div>
    </div>
</div>

<!-- ══ PLACEHOLDER ══ -->
<div id="spc-placeholder" style="
    text-align:center;padding:4rem 2rem;
    background:#f8fafc;border:2px dashed #e2e8f0;
    border-radius:10px;color:#94a3b8">
    <i class="bi bi-graph-up-arrow" style="font-size:3rem;color:#4f8ef7;display:block;margin-bottom:1rem"></i>
    <p>Seleccione <strong>producto</strong> y <strong>parámetro</strong>, luego presione <strong>Analizar</strong></p>
</div>

<!-- ══ LOADING ══ -->
<div id="spc-loading" style="display:none;text-align:center;padding:3rem">
    <div style="width:48px;height:48px;border:4px solid #e2e8f0;
        border-top-color:#4f8ef7;border-radius:50%;
        animation:spin .8s linear infinite;margin:0 auto 1rem"></div>
    <p style="color:#64748b">Calculando estadísticos SPC…</p>
</div>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<!-- ══ RESULTADOS ══ -->
<div id="spc-resultados" style="display:none">

    <!-- ─── BLOQUE 1: TARJETAS ESTADÍSTICAS (colapsable) ─── -->
    <div class="card mb-3" id="card-stat-cards">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('stat-cards-body','ico-stat-cards')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-bar-chart-line me-2"></i>Resumen Estadístico
            </span>
            <i id="ico-stat-cards" class="bi bi-chevron-up" style="font-size:14px"></i>
        </div>
        <div id="stat-cards-body">
            <div id="stat-cards" class="stats-grid" style="padding:12px 16px"></div>
        </div>
    </div>

    <!-- ─── BLOQUE 2: FASE I + ADVERTENCIAS ─── -->
    <div id="fase-banner" class="mb-3"></div>
    <div id="panel-advertencias-metodologicas" class="mb-3"></div>

    <!-- ─── BLOQUE 3: ANÁLISIS DE NORMALIDAD (histograma + QQ) ─── -->
    <div id="seccion-normalidad" class="card mb-3" style="display:none">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('normalidad-body','ico-normalidad')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-distribute-vertical me-2"></i>
                Análisis de Normalidad — Histograma y Gráfica de Probabilidad
            </span>
            <i id="ico-normalidad" class="bi bi-chevron-up" style="font-size:14px"></i>
        </div>
        <div id="normalidad-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;width:100%">
                    <!-- Histograma -->
                    <div style="min-width:0">
                        <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                             margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                            <i class="bi bi-bar-chart-fill me-1"></i>Histograma de Frecuencias
                        </div>
                        <div style="position:relative;width:100%">
                            <canvas id="chart-histograma"></canvas>
                        </div>
                        <div id="hist-ad-resultado" style="margin-top:8px"></div>
                    </div>
                    <!-- Q-Q Plot -->
                    <div style="min-width:0">
                        <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                             margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                            <i class="bi bi-graph-up me-1"></i>Gráfica de Probabilidad Normal (Q-Q Plot)
                        </div>
                        <div style="position:relative;width:100%">
                            <canvas id="chart-qq"></canvas>
                        </div>
                        <div id="qq-resultado" style="margin-top:8px;font-size:12px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 4: CARTAS DE CONTROL ─── -->
    <div class="card mb-3">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('cartas-body','ico-cartas')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-activity me-2"></i>Cartas de Control SPC
            </span>
            <i id="ico-cartas" class="bi bi-chevron-up" style="font-size:14px"></i>
        </div>
        <div id="cartas-body">
            <div class="card-body" style="padding:12px 16px">

                <!-- Carta X̄-R o X̄-S -->
                <div id="seccion-carta-xr" style="display:none">
                    <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                         margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                        <i class="bi bi-activity me-1"></i>Carta de Control X̄ (Medias)
                    </div>
                    <canvas id="chart-xbar" height="90" style="margin-bottom:20px"></canvas>
                    <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                         margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                        <span id="hdr-carta-secundaria">
                            <i class="bi bi-activity me-1"></i>Carta de Control R (Rangos)
                        </span>
                    </div>
                    <canvas id="chart-r" height="70"></canvas>
                </div>

                <!-- Carta X-MR -->
                <div id="seccion-carta-xmr" style="display:none">
                    <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                         margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                        <i class="bi bi-activity me-1"></i>Carta de Control X (Valores Individuales)
                    </div>
                    <canvas id="chart-x" height="90" style="margin-bottom:20px"></canvas>
                    <div style="font-weight:600;font-size:13px;color:#1e3a5f;
                         margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                        <i class="bi bi-activity me-1"></i>Carta de Control MR (Rango Móvil)
                    </div>
                    <canvas id="chart-mr" height="70"></canvas>
                </div>

                <!-- Carta p -->
                <div id="seccion-carta-p" style="display:none">
                    <div style="display:flex;justify-content:space-between;align-items:center;
                         margin-bottom:8px;padding-bottom:6px;border-bottom:2px solid #e2e8f0">
                        <div style="font-weight:600;font-size:13px;color:#1e3a5f">
                            <i class="bi bi-activity me-1"></i>Carta de Control p (Proporción de No Conformes)
                        </div>
                        <div id="carta-p-resumen" style="font-size:12px;color:#64748b"></div>
                    </div>
                    <canvas id="chart-p" height="90"></canvas>
                </div>

            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 5: TABLA DE SUBGRUPOS / LOTES (colapsable) ─── -->
    <div class="card mb-3">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('tabla-sg-body','ico-tabla-sg')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-table me-2"></i>Tabla de Datos por Subgrupo
            </span>
            <div style="display:flex;align-items:center;gap:10px">
                <span id="subgrupos-count" class="badge badge-info"></span>
                <i id="ico-tabla-sg" class="bi bi-chevron-down" style="font-size:14px"></i>
            </div>
        </div>
        <div id="tabla-sg-body" style="display:none">
            <div style="padding:0;overflow-x:auto">
                <table class="data-table-bordered" id="tabla-subgrupos">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Fecha / Turno</th>
                            <th>Lote Ref.</th>
                            <th>Origen</th>
                            <th>n</th>
                            <th id="th-col-xbar">X̄ (Media)</th>
                            <th id="th-col-r">R (Rango)</th>
                            <th>s (Desv.Est.)</th>
                            <th>Mín.</th>
                            <th>Máx.</th>
                            <th>CV%</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-subgrupos"></tbody>
                </table>
            </div>
            <!-- Tabla carta p (colapsada dentro) -->
            <div id="tabla-carta-p-wrap" style="display:none;padding:0;overflow-x:auto">
                <table class="data-table-bordered">
                    <thead>
                        <tr>
                            <th>N°</th><th>Fecha / Turno</th><th>Lote Ref.</th>
                            <th>n insp.</th><th>No conformes</th><th>Conformes</th>
                            <th>Proporción p</th><th>Porcentaje</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-carta-p"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 6: RUN CHART ─── -->
    <div id="seccion-run-chart" class="card mb-3" style="display:none">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('run-chart-body','ico-run-chart')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-activity me-2"></i>Gráfico de Corridas (Run Chart)
            </span>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;color:#9ca3af">Montgomery (2013), Cap.3</span>
                <i id="ico-run-chart" class="bi bi-chevron-up" style="font-size:14px"></i>
            </div>
        </div>
        <div id="run-chart-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">
                    <canvas id="chart-run" height="110"></canvas>
                    <div id="contenido-run-chart" style="font-size:12px"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Run Chart carta p -->
    <div id="seccion-run-chart-p" class="card mb-3" style="display:none">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('run-chart-p-body','ico-run-chart-p')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-activity me-2"></i>Gráfico de Corridas — Proporciones p
            </span>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;color:#9ca3af">Montgomery (2013), Cap.7</span>
                <i id="ico-run-chart-p" class="bi bi-chevron-up" style="font-size:14px"></i>
            </div>
        </div>
        <div id="run-chart-p-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start">
                    <canvas id="chart-run-p" height="110"></canvas>
                    <div id="contenido-run-chart-p" style="font-size:12px"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 7: ANÁLISIS DE CAPACIDAD + ÍNDICES (dos columnas) ─── -->
    <div class="card mb-3">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('capacidad-body','ico-capacidad')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-speedometer2 me-2"></i>Análisis de Capacidad del Proceso
            </span>
            <i id="ico-capacidad" class="bi bi-chevron-up" style="font-size:14px"></i>
        </div>
        <div id="capacidad-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">
                    <!-- Izquierda: índices principales Cp/Cpk -->
                    <div id="tabla-capacidad"></div>
                    <!-- Derecha: Cpm, k, St + ANOVA -->
                    <div>
                        <div id="contenido-indices-adic"></div>
                        <div id="contenido-anova" style="margin-top:16px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 8: CURVA OC ─── -->
    <div id="seccion-curva-oc" class="card mb-3" style="display:none">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('curva-oc-body','ico-curva-oc')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-graph-down-arrow me-2"></i>
                Curva OC — Probabilidad de No Detección (β)
            </span>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;color:#9ca3af">Montgomery (2013), Cap.5</span>
                <i id="ico-curva-oc" class="bi bi-chevron-up" style="font-size:14px"></i>
            </div>
        </div>
        <div id="curva-oc-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
                    <canvas id="chart-oc" height="200"></canvas>
                    <div>
                        <p style="font-size:12px;color:#64748b;margin-bottom:10px;line-height:1.6"
                           id="texto-curva-oc"></p>
                        <table class="data-table-bordered" style="font-size:12px">
                            <thead>
                                <tr>
                                    <th>δ / p₁</th><th>β (%)</th>
                                    <th>Poder (%)</th><th>ARL</th><th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-oc-refs"></tbody>
                        </table>
                        <p style="font-size:11px;color:#94a3b8;margin-top:8px">
                            β(δ)=Φ(L−δ√n)−Φ(−L−δ√n), L=3 · ARL=1/(1−β)<br>
                            <em>Montgomery (2013), Cap.5, Ec.5.23</em>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 9: PARETO (solo carta p) ─── -->
    <div id="seccion-pareto-p" class="card mb-3" style="display:none">
        <div class="card-header" style="background:#090e22;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('pareto-p-body','ico-pareto-p')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-bar-chart-steps me-2"></i>Diagrama de Pareto — No Conformes por Lote
            </span>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;color:#9ca3af">Montgomery (2013), Cap.7</span>
                <i id="ico-pareto-p" class="bi bi-chevron-up" style="font-size:14px"></i>
            </div>
        </div>
        <div id="pareto-p-body">
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
                    <canvas id="chart-pareto-p" height="180"></canvas>
                    <div id="tabla-pareto-p" style="overflow-x:auto"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BLOQUE 10: SEÑALES NELSON (conclusiones) ─── -->
    <div class="card mb-3" id="card-violaciones" style="display:none">
        <div class="card-header" style="background:#991b1b;color:#fff;cursor:pointer;
             display:flex;justify-content:space-between;align-items:center"
             onclick="toggleSeccion('violaciones-body','ico-violaciones')">
            <span style="font-weight:600;font-size:15px">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Señales de Control — Reglas de Nelson (Conclusiones)
            </span>
            <i id="ico-violaciones" class="bi bi-chevron-up" style="font-size:14px"></i>
        </div>
        <div id="violaciones-body">
            <div class="card-body" id="panel-violaciones"></div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
<script>
let chartXbar=null, chartR=null, chartX=null, chartMR=null, chartHist=null;
let tipoCarta = 'xr'; // 'xr' o 'xmr'

// ── Filtrar parámetros por producto ──
document.getElementById('fil_producto').addEventListener('change', function() {
    const productoId = this.value;
    const sel = document.getElementById('fil_parametro');
    sel.value = '';
    document.getElementById('specs-banner').style.display = 'none';

    Array.from(sel.options).forEach(opt => {
        if (!opt.value) {
            opt.textContent = productoId ? '-- Seleccione parámetro --' : '-- Seleccione producto primero --';
            opt.style.display = '';
            return;
        }
        opt.style.display = opt.dataset.producto === productoId ? '' : 'none';
    });
});

// ── Mostrar specs al elegir parámetro ──
document.getElementById('fil_parametro').addEventListener('change', function() {
    const opt = this.selectedOptions[0];
    const lse = parseFloat(opt.dataset.lse || 0);
    const lie = parseFloat(opt.dataset.lie || 0);
    const u   = opt.dataset.unidad || '';
    const n   = parseInt(opt.dataset.n || 1);

    tipoCarta = n > 1 ? 'xr' : 'xmr';

    const banner = document.getElementById('specs-banner');
    if (lse || lie) {
        document.getElementById('val-lse').textContent = lse + (u?' '+u:'');
        document.getElementById('val-lie').textContent = lie + (u?' '+u:'');
        document.getElementById('val-tol').textContent = (lse-lie).toFixed(4) + (u?' '+u:'');
        document.getElementById('val-carta-tipo').textContent = n > 1 ? 'Carta X̄-R (n='+n+')' : 'Carta X-MR';
        banner.style.display = 'flex';
    } else {
        banner.style.display = 'none';
    }
});

async function cargarAnalisis() {
    const prodId  = document.getElementById('fil_producto').value;
    const paramId = document.getElementById('fil_parametro').value;
    const desde   = document.getElementById('fil_desde').value;
    const hasta   = document.getElementById('fil_hasta').value;

    if (!prodId || !paramId) {
        alert('Seleccione Producto y Parámetro antes de analizar.');
        return;
    }

    document.getElementById('spc-placeholder').style.display = 'none';
    document.getElementById('spc-resultados').style.display = 'none';
    document.getElementById('spc-loading').style.display = 'block';

    const params = new URLSearchParams({ producto_id: prodId, parametro_id: paramId });
    if (desde) params.append('fecha_desde', desde);
    if (hasta) params.append('fecha_hasta', hasta);

    try {
        const res  = await fetch('<?= APP_URL ?>/m6/analizar?' + params);
        const data = await res.json();

        if (data.error) {
            alert(data.error);
            document.getElementById('spc-placeholder').style.display = 'block';
            return;
        }

        tipoCarta = data.tipo_carta || 'xr';
        renderizarResultados(data);

    } catch(e) {
        alert('Error de conexión: ' + e.message);
        document.getElementById('spc-placeholder').style.display = 'block';
    } finally {
        document.getElementById('spc-loading').style.display = 'none';
    }
}

// ── Toggle colapsable ──
function toggleSeccion(bodyId, iconId) {
    const body = document.getElementById(bodyId);
    const icon = document.getElementById(iconId);
    if (!body) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : '';
    if (icon) icon.className = visible ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}

function renderizarResultados(d) {
    renderStatCards(d);
    renderFaseBanner(d);
    renderAdvertencias(d);

    // Mostrar/ocultar secciones según tipo de carta
    const esXR  = d.tipo_carta === 'xr';
    const esXMR = d.tipo_carta === 'xmr';
    const esP   = d.tipo_carta === 'p';

    document.getElementById('seccion-carta-xr').style.display  = esXR  ? 'block' : 'none';
    document.getElementById('seccion-carta-xmr').style.display = esXMR ? 'block' : 'none';
    document.getElementById('seccion-carta-p').style.display   = esP   ? 'block' : 'none';

    // Sección normalidad solo para cartas numéricas
    const secNorm = document.getElementById('seccion-normalidad');
    if (secNorm) secNorm.style.display = esP ? 'none' : 'block';

    // Tabla carta p dentro del colapsable
    const tablaPWrap = document.getElementById('tabla-carta-p-wrap');
    if (tablaPWrap) tablaPWrap.style.display = esP ? 'block' : 'none';
    const tablaSgWrap = document.getElementById('tabla-subgrupos').closest('table');

    if (esP) {
        renderCartaP(d);
        renderTablaCártaP(d);
        document.getElementById('th-col-xbar').textContent = 'p (Proporción)';
        document.getElementById('th-col-r').textContent    = '—';
    } else if (esXR) {
        const esXS     = d.subtipo_carta === 'xs';
        const labelSec = d.label_secundaria ?? (esXS ? 'S (Desv.Est.)' : 'R (Rango)');
        const hdrR     = document.getElementById('hdr-carta-secundaria');
        if (hdrR) hdrR.innerHTML = `<i class="bi bi-activity me-1"></i>` +
            (esXS ? `Carta de Control S (Desv. Estándar) — n=${d.n} ≥ 6`
                  : `Carta de Control R (Rangos) — n=${d.n}`);
        renderCartaXbar(d);
        renderCartaR(d);
        document.getElementById('th-col-xbar').textContent = 'X̄ (Media)';
        document.getElementById('th-col-r').textContent    = labelSec;
    } else {
        renderCartaX(d);
        renderCartaMR(d);
        document.getElementById('th-col-xbar').textContent = 'X (Valor)';
        document.getElementById('th-col-r').textContent    = 'MR';
    }

    renderTablaSubgrupos(d);
    renderHistograma(d);
    renderQQPlot(d);
    renderCapacidad(d);
    renderANOVA(d);
    renderIndicesAdicionales(d);
    renderRunChart(d);
    renderRunChartP(d);
    renderParetoP(d);
    renderCurvaOC(d);
    renderViolaciones(d);

    document.getElementById('spc-resultados').style.display = 'block';
    document.getElementById('spc-resultados').scrollIntoView({ behavior:'smooth' });
    document.getElementById('btn-imprimir-spc').style.display = 'inline-flex';
}

// ── Banner Fase I ──
function renderFaseBanner(d) {
    const el = document.getElementById('fase-banner');
    if (!el) return;
    const kActual = d.k;
    const kRec    = d.k_recomendado ?? 25;
    const pct     = Math.min(100, Math.round(kActual / kRec * 100));
    const color   = pct >= 100 ? '#16a34a' : pct >= 60 ? '#d97706' : '#dc2626';
    el.innerHTML = `
    <div style="background:#1e3a5f;border-radius:8px;padding:10px 16px;
                display:flex;gap:16px;align-items:center;flex-wrap:wrap;
                font-size:13px;color:#e2e8f0;margin-bottom:0">
        <div style="display:flex;align-items:center;gap:8px">
            <i class="bi bi-info-circle" style="color:#60a5fa;font-size:16px"></i>
            <strong style="color:#93c5fd">FASE I — Análisis Retrospectivo</strong>
        </div>
        <div style="flex:1;min-width:180px">
            <div style="font-size:11px;color:#94a3b8;margin-bottom:3px">
                Subgrupos: ${kActual} / ${kRec} recomendados (Montgomery 2013, Cap.5)
            </div>
            <div style="background:#0f172a;border-radius:4px;height:5px;overflow:hidden">
                <div style="width:${pct}%;height:100%;background:${color};border-radius:4px"></div>
            </div>
        </div>
        <div style="font-size:11px;color:#94a3b8;max-width:380px">
            Límites calculados son <strong style="color:#fbbf24">límites de prueba de Fase I</strong>.
            Para Fase II se requiere verificar estabilidad con nuevos datos.
        </div>
    </div>`;
}

// ── Panel de advertencias metodológicas ──
function renderAdvertencias(d) {
    const el = document.getElementById('panel-advertencias-metodologicas');
    if (!el) return;
    if (!d.advertencias || d.advertencias.length === 0) { el.innerHTML = ''; return; }

    const colorMap = {
        danger:  { bg:'#fef2f2', border:'#ef4444', text:'#991b1b', icon:'bi-exclamation-triangle-fill', badgeBg:'#dc2626' },
        warning: { bg:'#fffbeb', border:'#f59e0b', text:'#92400e', icon:'bi-exclamation-circle-fill',   badgeBg:'#d97706' },
        info:    { bg:'#eff6ff', border:'#3b82f6', text:'#1e40af', icon:'bi-info-circle-fill',           badgeBg:'#2563eb' },
    };

    el.innerHTML = `
    <div style="margin-bottom:12px">
        ${d.advertencias.map(a => {
            const s = colorMap[a.tipo] || colorMap.info;
            return `<div style="background:${s.bg};border-left:4px solid ${s.border};
                        border-radius:0 8px 8px 0;padding:10px 14px;margin-bottom:8px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <i class="bi ${s.icon}" style="color:${s.border}"></i>
                    <strong style="color:${s.text};font-size:13px">${a.titulo}</strong>
                    <span style="background:${s.badgeBg};color:#fff;font-size:10px;
                                 padding:1px 6px;border-radius:4px;font-weight:700">
                        ${a.tipo.toUpperCase()}
                    </span>
                </div>
                <div style="font-size:12px;color:${s.text};margin-bottom:4px">${a.msg}</div>
                <div style="font-size:11px;color:#64748b;font-style:italic">
                    <i class="bi bi-book"></i> ${a.ref}
                </div>
            </div>`;
        }).join('')}
    </div>`;
}

// ── Stat cards ──
function renderStatCards(d) {
    const e = d.estadisticos, c = d.capacidad;
    let cards = [];

    if (d.tipo_carta === 'p') {
        cards = [
            { label:'Lotes (k)',          value: d.k,                    color:'#7c3aed' },
            { label:'N inspeccionado',    value: d.n,                    color:'#7c3aed' },
            { label:'p̄ (proporción media)',value: e.media,              color:'#0f172a' },
            { label:'LCS',                value: d.limites_p?.lcs,       color:'#ef4444' },
            { label:'LCI',                value: d.limites_p?.lci ?? 0,  color:'#ef4444' },
            { label:'Total no conformes', value: d.total_no_conf,        color:'#dc2626' },
            { label:'Total inspeccionado',value: d.total_insp,           color:'#0f172a' },
            { label:'Mínimo p',           value: e.minimo,               color:'#0f172a' },
            { label:'Máximo p',           value: e.maximo,               color:'#0f172a' },
        ];
    } else {
        const esXR   = d.tipo_carta === 'xr';
        const tipoBadge = esXR
            ? '<span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700">X̄-R</span>'
            : '<span style="background:#dcfce7;color:#065f46;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700">X-MR</span>';
        const mediaLbl = esXR ? 'X̄̄ (Gran media)' : 'X̄ (Media global)';
        const sigmaLbl = esXR ? 'σ̂ C.P. = R̄/d₂' : 'σ̂ C.P. = MR̄/d₂';
        const lcsLbl   = esXR ? 'LCS (carta X̄)' : 'UCL (carta X)';
        const lciLbl   = esXR ? 'LCI (carta X̄)' : 'LCL (carta X)';

        cards = [
            { label:'Subgrupos (k)',   value: d.k,           color:'#4f8ef7' },
            { label:'N total obs.',    value: e.n_total,      color:'#4f8ef7' },
            { label:'Tipo carta',      value: tipoBadge,      color:'#0f172a', html:true },
            { label: mediaLbl,         value: e.media,        color:'#0f172a' },
            { label: sigmaLbl,         value: e.desv_cp,      color:'#0f172a' },
            { label:'σ L.P. = s global', value: e.desv_lp,   color:'#0f172a' },
            { label:'CV%',             value: e.cv_pct+'%',
              color: e.cv_pct<10?'#16a34a':e.cv_pct<20?'#d97706':'#dc2626' },
            { label: lcsLbl,           value: d.limites_xbar?.lcs,   color:'#ef4444' },
            { label:'LC (línea central)', value: d.limites_xbar?.media, color:'#22c55e' },
            { label: lciLbl,           value: d.limites_xbar?.lci,   color:'#ef4444' },
            { label:'LSE (especif.)',   value: d.specs?.lse,          color:'#f97316' },
            { label:'LIE (especif.)',   value: d.specs?.lie,          color:'#f97316' },
            { label:'Cp (C.Plazo)',  value: c.cp,
              color: typeof c.cp==='number'?(c.cp>=1.33?'#16a34a':c.cp>=1?'#d97706':'#dc2626'):'#64748b' },
            { label:'Cpk (C.Plazo)', value: c.cpk,
              color: typeof c.cpk==='number'?(c.cpk>=1.33?'#16a34a':c.cpk>=1?'#d97706':'#dc2626'):'#64748b' },
            { label:'Mínimo',  value: e.minimo,  color:'#0f172a' },
            { label:'Máximo',  value: e.maximo,  color:'#0f172a' },
            { label:'Mediana', value: e.mediana, color:'#0f172a' },
            { label:'ARL₀',    value: '≈ 370',   color:'#64748b' },
        ];
    }

    document.getElementById('stat-cards').innerHTML = cards.map(card => `
        <div class="stat-card" style="border-left-color:${card.color}">
            <div>
                <div class="stat-label">${card.label}</div>
                <div class="stat-value" style="color:${card.color};font-size:${card.html?'13px':'17px'}">
                    ${card.value ?? '—'}
                </div>
            </div>
        </div>`).join('');
}

// ── Tabla subgrupos ──
function renderTablaSubgrupos(d) {
    const lcs = d.limites_xbar?.lcs ?? d.limites_p?.lcs;
    const lci = d.limites_xbar?.lci ?? d.limites_p?.lci ?? 0;
    document.getElementById('subgrupos-count').textContent = d.k + ' subgrupos';

    if (d.tipo_carta === 'p') {
        document.getElementById('th-col-xbar').textContent = 'p (Proporción)';
        document.getElementById('th-col-r').textContent    = 'No conformes';
        document.getElementById('tbody-subgrupos').innerHTML = d.subgrupos.map(sg => {
            const badge = (sg.proporcion ?? 0) > lcs
                ? '<span class="badge badge-danger">Fuera LC</span>'
                : '<span class="badge badge-success">OK</span>';
            return `<tr>
                <td>${sg.numero}</td>
                <td>${sg.fecha}</td>
                <td>${sg.lote_ref}</td>
                <td><span class="badge badge-muted">${sg.origen}</span></td>
                <td>${sg.n_obs}</td>
                <td><strong>${sg.proporcion ?? '—'}</strong></td>
                <td style="color:#ef4444;font-weight:700">${sg.no_conformes ?? '—'}</td>
                <td>—</td><td>—</td><td>—</td>
                <td>${sg.porcentaje ?? '—'}%</td>
                <td>${badge}</td>
            </tr>`;
        }).join('');
        return;
    }

    document.getElementById('th-col-xbar').textContent = d.tipo_carta === 'xr' ? 'X̄ (Media)' : 'X (Valor)';
    document.getElementById('th-col-r').textContent    = d.tipo_carta === 'xr' ? 'R (Rango)' : 'MR';

    document.getElementById('tbody-subgrupos').innerHTML = d.subgrupos.map(sg => {
        let badge;
        if (sg.xbar > lcs || sg.xbar < lci)
            badge = '<span class="badge badge-danger">Fuera LC</span>';
        else if (sg.cv > 15)
            badge = '<span class="badge badge-warning">CV Alto</span>';
        else
            badge = '<span class="badge badge-success">OK</span>';

        return `<tr>
            <td>${sg.numero}</td>
            <td>${sg.fecha}</td>
            <td>${sg.lote_ref}</td>
            <td><span class="badge badge-muted">${sg.origen}</span></td>
            <td>${sg.n_obs}</td>
            <td><strong>${sg.xbar}</strong></td>
            <td>${sg.rango}</td>
            <td>${sg.desv_est}</td>
            <td>${sg.minimo}</td>
            <td>${sg.maximo}</td>
            <td>${sg.cv}%</td>
            <td>${badge}</td>
        </tr>`;
    }).join('');
}

// ── Helpers Chart.js ──
function lineConst(label, val, len, color, dash) {
    return {
        label, data: Array(len).fill(val),
        borderColor: color, borderWidth: 1.5,
        borderDash: dash, pointRadius: 0,
        backgroundColor: 'transparent',
        datalabels: { display: false }, // evitar labels dobles
    };
}
function makeAnnotations(limites, specs, tipoCarta) {
    const anns = {};
    if (!limites) return anns;

    const addLine = (id, val, label, color, dash) => {
        if (val === undefined || val === null) return;
        anns[id] = {
            type: 'line', yMin: val, yMax: val,
            borderColor: color, borderWidth: 1.5,
            borderDash: dash || [],
            label: {
                display: true, content: `${label}: ${parseFloat(val).toFixed(4)}`,
                position: 'end', backgroundColor: 'rgba(255,255,255,0.85)',
                color: color, font: { size: 10, weight: 'bold' },
                padding: { x: 4, y: 2 }, borderRadius: 3,
            }
        };
    };

    addLine('lcs',  limites.lcs,   'LCS', '#ef4444', [6,3]);
    addLine('lc',   limites.media, 'LC',  '#22c55e', []);
    addLine('lci',  limites.lci,   'LCI', '#ef4444', [6,3]);

    if (specs && tipoCarta !== 'p') {
        if (specs.lse) addLine('lse', specs.lse, 'LSE', '#f97316', [4,4]);
        if (specs.lie) addLine('lie', specs.lie, 'LIE', '#f97316', [4,4]);
    }
    return anns;
}

const chartOpts = (yLabel, limites, specs, tipoCarta) => ({
    responsive: true,
    interaction: { mode:'index', intersect:false },
    plugins: {
        legend: { labels: { color:'#334155', font:{ size:11 } } },
        tooltip: { backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#cbd5e1' },
        annotation: { annotations: makeAnnotations(limites, specs, tipoCarta) }
    },
    scales: {
        x: { ticks:{ color:'#64748b', maxRotation:45, font:{size:10} }, grid:{ color:'#f1f5f9' } },
        y: { ticks:{ color:'#64748b' }, grid:{ color:'#f1f5f9' },
             title:{ display:true, text:yLabel, color:'#64748b' } }
    }
});

// ── Carta X̄ (X̄-R o X̄-S) ──
function renderCartaXbar(d) {
    const labels = d.subgrupos.map((sg,i) => `SG${i+1}\n${sg.fecha}`);
    const violMap = d.viol_por_subgrupo ?? {};

    // Color por regla violada:
    // Regla 1 → rojo fuerte, Regla 2 → naranja, Regla 3 → amarillo, Regla 4 → morado
    const pColors = d.xbars.map((_,i) => {
        const reglas = violMap[i] ?? [];
        if (reglas.includes(1)) return '#ef4444';
        if (reglas.includes(2)) return '#f97316';
        if (reglas.includes(3)) return '#eab308';
        if (reglas.includes(4)) return '#a855f7';
        return '#4f8ef7';
    });
    const pRadius = d.xbars.map((_,i) => {
        const reglas = violMap[i] ?? [];
        return reglas.length > 0 ? 8 : 5;
    });

    if (chartXbar) chartXbar.destroy();
    chartXbar = new Chart(document.getElementById('chart-xbar'), {
        type:'line',
        data:{ labels, datasets:[
            { label:'X̄ Medias', data:d.xbars, borderColor:'#4f8ef7',
              backgroundColor:'transparent', pointBackgroundColor:pColors,
              pointBorderColor: pColors, pointRadius:pRadius,
              pointBorderWidth:2, borderWidth:2, tension:0.1 },
            lineConst('LCS', d.limites_xbar.lcs,   labels.length,'#ef4444',[6,3]),
            lineConst('X̄̄',   d.limites_xbar.media, labels.length,'#22c55e',[]),
            lineConst('LCI', d.limites_xbar.lci,   labels.length,'#ef4444',[6,3]),
            lineConst('LSE', d.specs.lse,           labels.length,'#f97316',[4,4]),
            lineConst('LIE', d.specs.lie,           labels.length,'#f97316',[4,4]),
        ]},
        options: chartOpts('Valor', d.limites_xbar, d.specs, 'xr')
    });
}

// ── Carta R o S (X̄-R / X̄-S) ──
function renderCartaR(d) {
    const labels   = d.subgrupos.map((_,i) => `SG${i+1}`);
    const esXS     = d.subtipo_carta === 'xs';
    const labelSec = d.label_secundaria ?? (esXS ? 'S' : 'R');
    const mediaLbl = esXS ? 's̄' : 'R̄';
    const colorSec = esXS ? '#0891b2' : '#6366f1';

    if (chartR) chartR.destroy();
    chartR = new Chart(document.getElementById('chart-r'), {
        type:'line',
        data:{ labels, datasets:[
            { label: labelSec, data:d.rangos, borderColor: colorSec,
              backgroundColor:'transparent', pointRadius:5, borderWidth:2, tension:0.1 },
            lineConst('LCS', d.limites_r.lcs,   labels.length,'#ef4444',[6,3]),
            lineConst(mediaLbl, d.limites_r.media, labels.length,'#22c55e',[]),
            ...(d.limites_r.lci>0?[lineConst('LCI',d.limites_r.lci,labels.length,'#ef4444',[6,3])]:[]),
        ]},
        options: chartOpts(labelSec, d.limites_r, null, 'xr')
    });
}

// ── Carta X (X-MR) ──
function renderCartaX(d) {
    const labels = d.subgrupos.map((sg,i) => `${i+1}\n${sg.fecha}`);
    const viol1  = new Set(d.violaciones.filter(v=>v.regla===1).map(v=>v.subgrupo-1));
    const pColors = d.xbars.map((_,i) => viol1.has(i)?'#ef4444':'#4f8ef7');

    if (chartX) chartX.destroy();
    chartX = new Chart(document.getElementById('chart-x'), {
        type:'line',
        data:{ labels, datasets:[
            { label:'X Valores', data:d.xbars, borderColor:'#4f8ef7',
              backgroundColor:'transparent', pointBackgroundColor:pColors,
              pointRadius:5, borderWidth:2, tension:0.1 },
            lineConst('UCL', d.limites_x.lcs,   labels.length,'#ef4444',[6,3]),
            lineConst('X̄',   d.limites_x.media, labels.length,'#22c55e',[]),
            lineConst('LCL', d.limites_x.lci,   labels.length,'#ef4444',[6,3]),
            lineConst('LSE', d.specs.lse,        labels.length,'#f97316',[4,4]),
            lineConst('LIE', d.specs.lie,        labels.length,'#f97316',[4,4]),
        ]},
        options: chartOpts('Valor', d.limites_x, d.specs, 'xmr')
    });
}

// ── Carta MR (X-MR) ──
function renderCartaMR(d) {
    const labels = d.subgrupos.map((_,i) => `${i+1}`);
    if (chartMR) chartMR.destroy();
    chartMR = new Chart(document.getElementById('chart-mr'), {
        type:'line',
        data:{ labels, datasets:[
            { label:'MR', data:d.rangos_mr, borderColor:'#8b5cf6',
              backgroundColor:'transparent', pointRadius:5, borderWidth:2, tension:0.1 },
            lineConst('UCL_MR', d.limites_mr.lcs,   labels.length,'#ef4444',[6,3]),
            lineConst('MR̄',     d.limites_mr.media, labels.length,'#22c55e',[]),
        ]},
        options: chartOpts('Rango Móvil', d.limites_mr, null, 'xmr')
    });
}

// ── Histograma con eje X numérico (para que LSE/LIE funcionen)──
function renderHistograma(d) {
    const h = d.histograma;
    if (!h || !h.etiquetas || !h.frecuencias) return;

    const lse    = d.specs?.lse ?? 0;
    const lie    = d.specs?.lie ?? 0;
    const esP    = d.tipo_carta === 'p';
    const ancho  = h.ancho_bin ?? 1;

    // Colores de barras
    const bg = h.etiquetas.map(x =>
        (!esP && lse > 0 && (x > lse || x < lie))
            ? 'rgba(239,68,68,.55)' : 'rgba(79,142,247,.55)'
    );

    // Escala para curvas normales
    const totalObs = d.estadisticos?.n_total ?? 1;
    const escala   = totalObs * ancho;

    // Datasets: barras + curvas normales
    const datasets = [{
        label: 'Frecuencia', data: h.frecuencias,
        backgroundColor: bg, borderColor: bg.map(c=>c.replace('.55','1')),
        borderWidth: 1, borderRadius: 2,
        type: 'bar', yAxisID: 'y',
        barPercentage: 0.95, categoryPercentage: 0.95,
    }];

    if (d.curva_lp?.x?.length) {
        const mu = d.estadisticos.media, sigma = d.estadisticos.desv_lp;
        const yLP = h.etiquetas.map(xi =>
            sigma > 0 ? escala*(1/(sigma*Math.sqrt(2*Math.PI)))*Math.exp(-0.5*((xi-mu)/sigma)**2) : 0
        );
        datasets.push({ label:'Normal L.P.', data:yLP,
            borderColor:'#dc2626', borderWidth:2, pointRadius:0,
            fill:false, tension:0.4, type:'line', yAxisID:'y' });
    }
    if (d.curva_cp?.x?.length) {
        const mu = d.estadisticos.media, sigma = d.estadisticos.desv_cp;
        const yCP = h.etiquetas.map(xi =>
            sigma > 0 ? escala*(1/(sigma*Math.sqrt(2*Math.PI)))*Math.exp(-0.5*((xi-mu)/sigma)**2) : 0
        );
        datasets.push({ label:'Normal C.P.', data:yCP,
            borderColor:'#2563eb', borderWidth:1.5, borderDash:[4,3],
            pointRadius:0, fill:false, tension:0.4, type:'line', yAxisID:'y' });
    }

    // Resultado Anderson-Darling debajo del canvas (solo cartas numéricas)
    const ad = d.normalidad ?? null;
    const adHtml = ad?.ad !== null && ad !== null
        ? `<div style="margin-top:8px;padding:6px 10px;border-radius:6px;font-size:12px;
               background:${ad.normal?'#f0fdf4':'#fef2f2'};
               border:1px solid ${ad.normal?'#86efac':'#fca5a5'};
               color:${ad.normal?'#15803d':'#dc2626'}">
               <strong>Prueba Anderson-Darling</strong> (Montgomery 2013, Cap.3)<br>
               AD = ${ad.ad} · p-valor = ${ad.p_value} ·
               ${ad.normal?'✔ Normalidad confirmada (p ≥ 0.05)':'✗ No normalidad (p < 0.05)'}
           </div>` : '';

    const contenedor = document.getElementById('hist-ad-resultado');
    if (contenedor) contenedor.innerHTML = adHtml;

    // Anotaciones LSE y LIE como líneas verticales
    const anns = {};
    if (!esP && lse > 0) {
        anns.lse = { type:'line', xMin:lse, xMax:lse,
            borderColor:'#ef4444', borderWidth:2.5, borderDash:[6,3],
            label:{ display:true, content:`LSE: ${lse}`, position:'start',
                    color:'#dc2626', font:{size:10,weight:'bold'},
                    backgroundColor:'rgba(255,255,255,0.9)', padding:{x:4,y:2} } };
    }
    if (!esP && lie > 0) {
        anns.lie = { type:'line', xMin:lie, xMax:lie,
            borderColor:'#f97316', borderWidth:2.5, borderDash:[6,3],
            label:{ display:true, content:`LIE: ${lie}`, position:'end',
                    color:'#ea580c', font:{size:10,weight:'bold'},
                    backgroundColor:'rgba(255,255,255,0.9)', padding:{x:4,y:2} } };
    }

    if (chartHist) chartHist.destroy();
    chartHist = new Chart(document.getElementById('chart-histograma'), {
        type: 'bar',
        data: { labels: h.etiquetas.map(v => parseFloat(v)), datasets },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.6,
            plugins: {
                legend: { labels:{ color:'#64748b', font:{size:11} } },
                tooltip: { callbacks:{ title: t => 'Valor: '+t[0].label } },
                annotation: { annotations: anns }
            },
            scales: {
                x: {
                    type: 'linear',
                    ticks: { color:'#64748b',
                             callback: v => parseFloat(v).toFixed(1) },
                    grid: { color:'#f1f5f9' },
                    title: { display:true, text:'Valor', color:'#64748b' }
                },
                y: { ticks:{ color:'#64748b' }, grid:{ color:'#f1f5f9' },
                     title:{ display:true, text:'Frecuencia', color:'#64748b' } }
            }
        }
    });
}


// ── Capacidad ──
function renderCapacidad(d) {
    const c   = d.capacidad;
    const ad  = d.normalidad ?? null;
    const hayViolaciones = d.violaciones && d.violaciones.length > 0;
    const esNormal = ad?.normal === true;

    const cls = v => typeof v==='number'
        ? (v>=1.33?'color:#16a34a;font-weight:700':v>=1?'color:#d97706;font-weight:700':'color:#dc2626;font-weight:700')
        : 'color:#64748b';
    const interp = v => {
        if (typeof v!=='number') return '—';
        if (v>=1.67) return '✔ Excelente'; if (v>=1.33) return '✔ Capaz';
        if (v>=1.00) return '⚠ Marginal';  return '✗ No capaz';
    };

    // Advertencia visual si proceso inestable
    const alertaInestable = hayViolaciones
        ? `<div style="background:#fef2f2;border:1px solid #ef4444;border-radius:6px;
                       padding:8px 12px;margin-bottom:10px;font-size:12px;color:#991b1b">
               <i class="bi bi-exclamation-triangle-fill"></i>
               <strong>Proceso inestable:</strong> Cp y Cpk no son válidos con señales activas.
               Montgomery (2013), Cap.6, p.354.
           </div>` : '';

    // Advertencia PPM si no hay normalidad
    const alertaPPM = !esNormal
        ? `<div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:6px;
                       padding:8px 12px;margin-bottom:10px;font-size:12px;color:#92400e">
               <i class="bi bi-exclamation-circle-fill"></i>
               <strong>PPM con cautela:</strong> Los datos no son normales (AD p=${ad?.p_value}).
               El PPM estimado asume normalidad y puede ser impreciso.
               Montgomery (2013), Cap.6, p.354.
           </div>` : '';

    // Carta p → solo mostrar límites, sin Cp/Cpk
    if (d.tipo_carta === 'p') {
        document.getElementById('tabla-capacidad').innerHTML = alertaInestable + `
        <table class="data-table-bordered">
            <thead><tr><th colspan="2">Estadísticos carta p</th></tr></thead>
            <tbody>
                <tr><td style="text-align:left">p̄ (prop. media)</td>
                    <td style="font-weight:700;color:#7c3aed">${d.limites_p?.media ?? '—'}</td></tr>
                <tr><td style="text-align:left">LCS = p̄ + 3√(p̄(1-p̄)/n)</td>
                    <td style="font-weight:700;color:#ef4444">${d.limites_p?.lcs ?? '—'}</td></tr>
                <tr><td style="text-align:left">LCI = p̄ − 3√(p̄(1-p̄)/n)</td>
                    <td style="font-weight:700;color:#ef4444">${d.limites_p?.lci ?? 0}</td></tr>
                <tr><td style="text-align:left">n inspeccionado</td>
                    <td>${d.n}</td></tr>
                <tr><td style="text-align:left">PPM estimado</td>
                    <td style="color:#d97706;font-weight:700">${fmtN(c.ppm_cp)}</td></tr>
            </tbody>
        </table>
        <p style="font-size:11px;color:#94a3b8;margin-top:8px">
            Fórmulas: Montgomery (2013), Cap. 7 — Cartas de control para atributos.<br>
            LCS/LCI = p̄ ± 3√[p̄(1-p̄)/n]
        </p>`;
        return;
    }

    // Cartas X̄-R y X-MR
    const esXR = d.tipo_carta === 'xr';
    const sigmaFormula = esXR ? 'σ̂ = R̄/d₂' : 'σ̂ = MR̄/d₂';
    const lcsFormula   = esXR ? 'X̄̄ + A₂·R̄' : 'X̄ + 3·σ̂';
    const lciFormula   = esXR ? 'X̄̄ − A₂·R̄' : 'X̄ − 3·σ̂';

    document.getElementById('tabla-capacidad').innerHTML = alertaInestable + alertaPPM + `
    <table class="data-table-bordered" style="margin-bottom:8px">
        <thead><tr><th colspan="3" style="background:#1e3a5f">Límites de Control</th></tr>
               <tr><th>Límite</th><th>Fórmula</th><th>Valor</th></tr></thead>
        <tbody>
            <tr><td style="text-align:left">LCS</td>
                <td style="font-size:11px;color:#64748b">${lcsFormula}</td>
                <td style="color:#ef4444;font-weight:700">${d.limites_xbar?.lcs ?? '—'}</td></tr>
            <tr><td style="text-align:left">LC</td>
                <td style="font-size:11px;color:#64748b">X̄̄ o X̄</td>
                <td style="color:#22c55e;font-weight:700">${d.limites_xbar?.media ?? '—'}</td></tr>
            <tr><td style="text-align:left">LCI</td>
                <td style="font-size:11px;color:#64748b">${lciFormula}</td>
                <td style="color:#ef4444;font-weight:700">${d.limites_xbar?.lci ?? '—'}</td></tr>
        </tbody>
    </table>
    <table class="data-table-bordered" style="margin-bottom:8px">
        <thead><tr><th colspan="3" style="background:#1e3a5f">Límites de Especificación</th></tr>
               <tr><th>Límite</th><th>Fuente</th><th>Valor</th></tr></thead>
        <tbody>
            <tr><td style="text-align:left">LSE</td>
                <td style="font-size:11px;color:#64748b">parametros_proceso.valor_max</td>
                <td style="color:#f97316;font-weight:700">${d.specs?.lse ?? '—'}</td></tr>
            <tr><td style="text-align:left">LIE</td>
                <td style="font-size:11px;color:#64748b">parametros_proceso.valor_min</td>
                <td style="color:#f97316;font-weight:700">${d.specs?.lie ?? '—'}</td></tr>
            <tr><td style="text-align:left">Tolerancia</td>
                <td style="font-size:11px;color:#64748b">LSE − LIE</td>
                <td style="font-weight:700">${c.tolerancia ?? '—'}</td></tr>
        </tbody>
    </table>
    <table class="data-table-bordered">
        <thead><tr><th colspan="4" style="background:#1e3a5f">Índices de Capacidad</th></tr>
               <tr><th>Índice</th><th>C. Plazo</th><th>L. Plazo</th><th>Interpretación</th></tr></thead>
        <tbody>
            <tr><td style="text-align:left">Potencial (Cp/Pp)</td>
                <td style="${cls(c.cp)}">${c.cp}</td>
                <td style="${cls(c.pp)}">${c.pp}</td>
                <td>${interp(c.cp)}</td></tr>
            <tr><td style="text-align:left">Ajustado (Cpk/Ppk)</td>
                <td style="${cls(c.cpk)}">${c.cpk}</td>
                <td style="${cls(c.ppk)}">${c.ppk}</td>
                <td>${interp(c.cpk)}</td></tr>
            <tr><td style="text-align:left" title="CPU=(LSE-X̄)/3σ̂">CPU / PPU ↑</td>
                <td>${c.cpu}</td><td>${c.ppu??'—'}</td>
                <td style="font-size:11px;color:#64748b">(LSE−X̄)/3σ</td></tr>
            <tr><td style="text-align:left" title="CPL=(X̄-LIE)/3σ̂">CPL / PPL ↓</td>
                <td>${c.cpl}</td><td>${c.ppl??'—'}</td>
                <td style="font-size:11px;color:#64748b">(X̄−LIE)/3σ</td></tr>
            <tr style="border-top:2px solid #e2e8f0">
                <td style="text-align:left">PPM estimado</td>
                <td colspan="2" style="color:#d97706;font-weight:700">
                    ${fmtN(c.ppm_cp)} / ${fmtN(c.ppm_lp)}
                </td>
                <td style="font-size:11px;color:#64748b">CP / LP</td></tr>
            <tr><td style="text-align:left">Nivel Sigma</td>
                <td colspan="3" style="color:#4f8ef7;font-weight:700">${c.sigma_nivel}σ</td></tr>
            <tr><td style="text-align:left">ARL₀ (sin cambio)</td>
                <td colspan="3" style="color:#64748b;font-size:12px">≈ 370 muestras (límites 3σ)</td></tr>
        </tbody>
    </table>
    ${c.ic_cp || c.ic_cpk ? `
    <table class="data-table-bordered" style="margin-top:8px">
        <thead><tr><th colspan="4" style="background:#1e3a5f">Intervalos de Confianza 95% — Cp y Cpk</th></tr>
               <tr><th>Índice</th><th>Estimación puntual</th><th>IC Inferior</th><th>IC Superior</th></tr></thead>
        <tbody>
            ${c.ic_cp ? `<tr>
                <td style="text-align:left">Cp</td>
                <td style="${cls(c.cp)}">${c.cp}</td>
                <td style="color:#64748b">${c.ic_cp.lci}</td>
                <td style="color:#64748b">${c.ic_cp.lcs}</td>
            </tr>` : ''}
            ${c.ic_cpk ? `<tr>
                <td style="text-align:left">Cpk</td>
                <td style="${cls(c.cpk)}">${c.cpk}</td>
                <td style="color:#64748b">${c.ic_cpk.lci}</td>
                <td style="color:#64748b">${c.ic_cpk.lcs}</td>
            </tr>` : ''}
        </tbody>
    </table>
    <p style="font-size:11px;color:#94a3b8;margin-top:4px">
        IC Cp: basado en χ² (Wilson & Hilferty) · IC Cpk: aproximación Bissell (1990)<br>
        Ref: Montgomery (2013), Cap.6, p.357-358
    </p>` : ''}
    <p style="font-size:11px;color:#94a3b8;margin-top:8px">
        <strong>Bibliografía:</strong> Montgomery (2013), Cap.6 — Process Capability Analysis.<br>
        Cp = (LSE−LIE)/6σ̂ · Cpk = mín(CPU,CPL) · ${sigmaFormula}<br>
        Pp = (LSE−LIE)/6s · Ppk = mín(PPU,PPL) · s = desv. estándar global<br>
        CPU=(LSE−X̄)/3σ̂ · CPL=(X̄−LIE)/3σ̂ · Cpk≥1.33→Capaz · Cpk≥1.67→Excelente
    </p>`;
}

function fmtN(v) { return typeof v==='number'?v.toLocaleString('es-BO'):'—'; }

// ── Violaciones ──
// ── Curva OC (Operating Characteristic) ──
// Ref: Montgomery (2013), Cap.5 (X̄-R/X-MR) y Cap.7 (Carta p)
let chartOC = null;
function renderCurvaOC(d) {
    const seccion = document.getElementById('seccion-curva-oc');
    if (!d.curva_oc) { seccion.style.display = 'none'; return; }
    seccion.style.display = 'block';

    const oc      = d.curva_oc;
    const puntos  = oc.puntos;
    const esCarta_p = d.tipo_carta === 'p';

    // Etiquetas y datos según tipo de carta
    const labels  = esCarta_p
        ? puntos.map(p => p.p1.toFixed(3))
        : puntos.map(p => p.delta.toFixed(1));
    const beta    = puntos.map(p => p.beta_pct);
    const poder   = puntos.map(p => p.poder);

    // Tabla de referencias
    const tbody = document.getElementById('tbody-oc-refs');
    tbody.innerHTML = oc.referencias.map(r => {
        const colorPoder = r.poder >= 80 ? '#16a34a' : r.poder >= 50 ? '#d97706' : '#dc2626';
        const colorBeta  = r.beta_pct <= 20 ? '#16a34a' : r.beta_pct <= 50 ? '#d97706' : '#dc2626';
        const col1 = esCarta_p ? `p₁ = ${r.p1}` : `δ = ${r.delta}σ`;
        return `<tr>
            <td style="text-align:center;font-weight:700">${col1}</td>
            <td style="text-align:center;color:${colorBeta};font-weight:700">${r.beta_pct}%</td>
            <td style="text-align:center;color:${colorPoder};font-weight:700">${r.poder}%</td>
            <td style="text-align:center;font-weight:600">${r.arl}</td>
            <td style="font-size:11px;color:#64748b">${r.desc}</td>
        </tr>`;
    }).join('');

    // Texto explicativo dinámico
    const textoOC = document.getElementById('texto-curva-oc');
    if (textoOC) {
        textoOC.innerHTML = esCarta_p
            ? `La curva OC muestra la probabilidad β de <strong>no detectar</strong> que la
               proporción real p₁ es diferente de p̄=${oc.pbar}.
               ARL = 1/(1−β) = lotes promedio hasta detectar el cambio.<br>
               <em>Montgomery (2013), Cap.7, p.300-303</em>`
            : `La curva OC muestra la probabilidad β de <strong>no detectar</strong> un cambio
               de δ múltiplos de σ en la media. ARL = 1/(1−β) muestras hasta detectar.<br>
               β(δ) = Φ(L−δ√n) − Φ(−L−δ√n), L=3 &nbsp;·&nbsp;
               <em>Montgomery (2013), Cap.5, Ec.5.23</em>`;
    }

    // Anotaciones según tipo
    const anns = esCarta_p ? {} : {
        ref80: { type:'line', yMin:80, yMax:80, borderColor:'#22c55e', borderWidth:1, borderDash:[4,4],
                 label:{ display:true, content:'Poder 80%', position:'start', color:'#16a34a',
                         font:{size:10}, backgroundColor:'rgba(255,255,255,0.85)' } },
        ref50: { type:'line', yMin:50, yMax:50, borderColor:'#f59e0b', borderWidth:1, borderDash:[4,4],
                 label:{ display:true, content:'β = 50%', position:'start', color:'#d97706',
                         font:{size:10}, backgroundColor:'rgba(255,255,255,0.85)' } },
    };
    if (esCarta_p && oc.pbar !== undefined) {
        anns.pbar = { type:'line', xMin:oc.pbar.toFixed(3), xMax:oc.pbar.toFixed(3),
                      borderColor:'#7c3aed', borderWidth:1.5, borderDash:[4,4],
                      label:{ display:true, content:`p̄=${oc.pbar}`, position:'end',
                              color:'#7c3aed', font:{size:10}, backgroundColor:'rgba(255,255,255,0.85)' } };
    }

    const xTitle = esCarta_p ? 'Proporción real p₁' : 'Tamaño del cambio δ (múltiplos de σ)';

    if (chartOC) chartOC.destroy();
    chartOC = new Chart(document.getElementById('chart-oc'), {
        type: 'line',
        data: { labels, datasets: [
            { label:'β — P(no detección) %', data:beta,
              borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.08)',
              fill:true, borderWidth:2, pointRadius:1, tension:0.4 },
            { label:'Poder de detección %', data:poder,
              borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,0.06)',
              fill:true, borderWidth:2, pointRadius:1, tension:0.4 },
        ]},
        options: {
            responsive:true,
            interaction:{ mode:'index', intersect:false },
            plugins:{
                legend:{ labels:{ color:'#334155', font:{size:11} } },
                tooltip:{ backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#cbd5e1',
                    callbacks:{
                        title: ctx => esCarta_p ? `p₁ = ${ctx[0].label}` : `δ = ${ctx[0].label}σ`,
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)}%`
                    }},
                annotation:{ annotations: anns }
            },
            scales:{
                x:{ title:{ display:true, text:xTitle, color:'#64748b' },
                    ticks:{ color:'#64748b', maxTicksLimit:12 }, grid:{ color:'#f1f5f9' } },
                y:{ min:0, max:100,
                    title:{ display:true, text:'Probabilidad (%)', color:'#64748b' },
                    ticks:{ color:'#64748b', callback: v => v+'%' }, grid:{ color:'#f1f5f9' } }
            }
        }
    });
}

// ── ANOVA ──
function renderANOVA(d) {
    const el = document.getElementById('contenido-anova');
    if (!el) return;
    if (d.tipo_carta !== 'xr' || !d.anova || !d.anova.k) {
        el.innerHTML = ''; return;
    }
    const a = d.anova;

    const colorPct = (pct) => pct > 50 ? '#dc2626' : pct > 30 ? '#d97706' : '#16a34a';

    el.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
            <table class="data-table-bordered" style="font-size:12px;margin-bottom:0">
                <thead>
                    <tr>
                        <th>Fuente</th>
                        <th>SC</th>
                        <th>GL</th>
                        <th>CM</th>
                        <th>F</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:left;font-weight:600">Entre subgrupos</td>
                        <td>${a.SCB}</td><td>${a.glB}</td><td>${a.CMB}</td>
                        <td style="font-weight:700;color:#4f8ef7">${a.F}</td>
                    </tr>
                    <tr>
                        <td style="text-align:left;font-weight:600">Dentro subgrupos</td>
                        <td>${a.SCW}</td><td>${a.glW}</td><td>${a.CMW}</td><td>—</td>
                    </tr>
                    <tr style="border-top:2px solid #e2e8f0;font-weight:700">
                        <td style="text-align:left">Total</td>
                        <td>${a.SCT}</td><td>${a.glT}</td><td>—</td><td>—</td>
                    </tr>
                </tbody>
            </table>
            <p style="font-size:11px;color:#94a3b8;margin-top:6px">
                SC=Suma Cuadrados · GL=Grados Libertad · CM=Cuadrado Medio<br>
                F = CM_entre / CM_dentro — Montgomery (2013), Cap.4, Ec.4.7
            </p>
        </div>
        <div>
            <table class="data-table-bordered" style="font-size:12px;margin-bottom:0">
                <thead>
                    <tr><th>Componente</th><th>σ²</th><th>σ</th><th>% Contrib.</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:left">Dentro (variabilidad proceso)</td>
                        <td>${a.var_dentro}</td>
                        <td>${a.sigma_dentro}</td>
                        <td style="color:${colorPct(a.pct_dentro)};font-weight:700">${a.pct_dentro}%</td>
                    </tr>
                    <tr>
                        <td style="text-align:left">Entre (causas asignables)</td>
                        <td>${a.var_entre}</td>
                        <td>${a.sigma_entre}</td>
                        <td style="color:${colorPct(a.pct_entre)};font-weight:700">${a.pct_entre}%</td>
                    </tr>
                    <tr style="border-top:2px solid #e2e8f0;font-weight:700">
                        <td style="text-align:left">Total (St)</td>
                        <td>${a.var_total}</td>
                        <td style="color:#4f8ef7">${a.St}</td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>
            <p style="font-size:11px;color:#94a3b8;margin-top:6px">
                St = √(σ²_dentro + σ²_entre) = desviación estándar total<br>
                Montgomery (2013), Cap.5, p.234 — Componentes de varianza
            </p>
        </div>
    </div>`;
}

// ── Índices adicionales: Cpm, k, St ──
function renderIndicesAdicionales(d) {
    const el = document.getElementById('contenido-indices-adic');
    if (!el) return;
    if (d.tipo_carta === 'p' || !d.indices_adic) {
        el.innerHTML = ''; return;
    }
    const ia = d.indices_adic;

    const clsCpm = v => typeof v==='number'
        ? (v>=1.33?'color:#16a34a;font-weight:700':v>=1?'color:#d97706;font-weight:700':'color:#dc2626;font-weight:700')
        : 'color:#64748b';
    const clsK = v => typeof v==='number'
        ? (v<=0.1?'color:#16a34a;font-weight:700':v<=0.3?'color:#22c55e;font-weight:700':v<=0.5?'color:#d97706;font-weight:700':'color:#dc2626;font-weight:700')
        : 'color:#64748b';

    el.innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <table class="data-table-bordered" style="font-size:12px">
            <thead>
                <tr><th colspan="3" style="background:#1e3a5f">Índice Cpm — Taguchi</th></tr>
                <tr><th>Índice</th><th>C. Plazo</th><th>L. Plazo</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left">Cpm</td>
                    <td style="${clsCpm(ia.cpm_cp)}">${ia.cpm_cp}</td>
                    <td style="${clsCpm(ia.cpm_lp)}">${ia.cpm_lp}</td>
                </tr>
                <tr>
                    <td style="text-align:left">Interpretación</td>
                    <td colspan="2">${ia.interp_cpm}</td>
                </tr>
                <tr>
                    <td style="text-align:left">Nominal (T)</td>
                    <td colspan="2">${ia.nominal}</td>
                </tr>
                <tr>
                    <td style="text-align:left">√(σ²+(μ-T)²) CP</td>
                    <td colspan="2">${ia.desvT_CP}</td>
                </tr>
            </tbody>
        </table>
        <table class="data-table-bordered" style="font-size:12px">
            <thead>
                <tr><th colspan="2" style="background:#1e3a5f">Índice k (Centrado) y St</th></tr>
                <tr><th>Índice</th><th>Valor</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left">k (centrado)</td>
                    <td style="${clsK(ia.k_centrado)}">${ia.k_centrado}</td>
                </tr>
                <tr>
                    <td style="text-align:left">Interpretación k</td>
                    <td style="font-size:11px">${ia.interp_k}</td>
                </tr>
                <tr>
                    <td style="text-align:left">St (desv. total)</td>
                    <td style="font-weight:700;color:#4f8ef7">${ia.St}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p style="font-size:11px;color:#94a3b8;margin-top:8px">
        Cpm = (LSE−LIE) / (6·√(σ²+(μ−T)²)) — penaliza desviación del nominal T<br>
        k = |μ−m| / ((LSE−LIE)/2) — 0=centrado, 1=en límite<br>
        St = desviación total del proceso (incluye varianza entre y dentro subgrupos)<br>
        <em>${ia.ref_cpm} · ${ia.ref_k}</em>
    </p>`;
}

// ── Run Chart ──
let chartRun = null;
function renderRunChart(d) {
    const sec = document.getElementById('seccion-run-chart');
    if (!d.run_chart || !d.run_chart.valores?.length) {
        sec.style.display = 'none'; return;
    }
    sec.style.display = 'block';
    const rc = d.run_chart;

    // Gráfico
    const labels  = rc.fechas.map((f,i) => `${i+1}`);
    const mediana = rc.mediana;
    const colores = rc.valores.map(v => v > mediana ? '#2563eb' : v < mediana ? '#dc2626' : '#94a3b8');

    if (chartRun) chartRun.destroy();
    chartRun = new Chart(document.getElementById('chart-run'), {
        type: 'line',
        data: { labels, datasets: [
            { label: 'Valores', data: rc.valores,
              borderColor: '#4f8ef7', backgroundColor: 'transparent',
              pointBackgroundColor: colores, pointRadius: 5, borderWidth: 1.5, tension: 0.1 },
            { label: `Mediana: ${mediana}`, data: Array(rc.n).fill(mediana),
              borderColor: '#22c55e', borderWidth: 1.5, borderDash: [5,3],
              pointRadius: 0, fill: false },
        ]},
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color:'#334155', font:{size:11} } },
                tooltip: { backgroundColor:'#1e293b', bodyColor:'#cbd5e1',
                    callbacks: { title: ctx => rc.fechas[ctx[0].dataIndex] ?? '' } },
                annotation: {
                    annotations: {
                        mediana: { type:'line', yMin:mediana, yMax:mediana,
                            borderColor:'#22c55e', borderWidth:1.5, borderDash:[5,3],
                            label:{ display:true, content:`Mediana: ${mediana}`,
                                    position:'end', color:'#16a34a', font:{size:10},
                                    backgroundColor:'rgba(255,255,255,0.85)' } }
                    }
                }
            },
            scales: {
                x: { title:{display:true,text:'Subgrupo / Observación',color:'#64748b'},
                     ticks:{color:'#64748b'}, grid:{color:'#f1f5f9'} },
                y: { ticks:{color:'#64748b'}, grid:{color:'#f1f5f9'},
                     title:{display:true,text:'Valor',color:'#64748b'} }
            }
        }
    });

    // Panel de resultados
    const colorAl = rc.aleatorio ? '#16a34a' : '#dc2626';
    const iconAl  = rc.aleatorio ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    let html = `
    <div style="background:${rc.aleatorio?'#f0fdf4':'#fef2f2'};border:1px solid ${rc.aleatorio?'#86efac':'#fca5a5'};
                border-radius:8px;padding:10px 12px;margin-bottom:10px">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <i class="bi ${iconAl}" style="color:${colorAl}"></i>
            <strong style="color:${colorAl};font-size:13px">
                ${rc.aleatorio ? 'Proceso aleatorio' : 'Patrón no aleatorio'}
            </strong>
        </div>
        <div style="font-size:12px;color:#374151">${rc.interpretacion}</div>
    </div>
    <table class="data-table-bordered" style="font-size:12px">
        <tbody>
            <tr><td style="text-align:left">N observaciones</td><td>${rc.n}</td></tr>
            <tr><td style="text-align:left">Mediana</td><td><strong>${rc.mediana}</strong></td></tr>
            <tr><td style="text-align:left">Puntos ↑ mediana</td><td>${rc.n_arriba}</td></tr>
            <tr><td style="text-align:left">Puntos ↓ mediana</td><td>${rc.n_abajo}</td></tr>
            <tr><td style="text-align:left">Rachas observadas</td><td><strong>${rc.rachas}</strong></td></tr>
            <tr><td style="text-align:left">Rachas esperadas</td><td>${rc.media_rachas}</td></tr>
            <tr><td style="text-align:left">Z estadístico</td><td>${rc.z_rachas}</td></tr>
            <tr><td style="text-align:left">p-valor</td>
                <td style="color:${rc.p_value<0.05?'#dc2626':'#16a34a'};font-weight:700">
                    ${rc.p_value}
                </td></tr>
        </tbody>
    </table>`;

    if (rc.tendencias && rc.tendencias.length > 0) {
        html += `<div style="margin-top:8px;padding:6px 10px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e">
            <i class="bi bi-arrow-up-right"></i> <strong>Tendencia detectada</strong> en posición(es):
            ${rc.tendencias.map(t => `SG${t.pos} (${t.tipo})`).join(', ')}
        </div>`;
    }
    if (rc.hay_ciclos) {
        html += `<div style="margin-top:6px;padding:6px 10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;font-size:12px;color:#1e40af">
            <i class="bi bi-arrow-repeat"></i> <strong>Posibles ciclos</strong> — alternancia frecuente detectada.
        </div>`;
    }
    html += `<p style="font-size:11px;color:#94a3b8;margin-top:8px">${rc.ref}</p>`;

    document.getElementById('contenido-run-chart').innerHTML = html;
}

// ── Run Chart para Carta p ──
// Ref: Montgomery (2013), Cap.7, p.292
let chartRunP = null;
function renderRunChartP(d) {
    const sec = document.getElementById('seccion-run-chart-p');
    if (d.tipo_carta !== 'p' || !d.run_chart || !d.run_chart.valores?.length) {
        sec.style.display = 'none'; return;
    }
    sec.style.display = 'block';
    const rc = d.run_chart;

    const labels  = rc.fechas.map((f, i) => `${i+1}`);
    const mediana = rc.mediana;
    const colores = rc.valores.map(v =>
        v > mediana ? '#7c3aed' : v < mediana ? '#ef4444' : '#94a3b8'
    );

    if (chartRunP) chartRunP.destroy();
    chartRunP = new Chart(document.getElementById('chart-run-p'), {
        type: 'line',
        data: { labels, datasets: [
            { label:'Proporción p', data:rc.valores,
              borderColor:'#7c3aed', backgroundColor:'transparent',
              pointBackgroundColor:colores, pointRadius:5, borderWidth:1.5, tension:0.1 },
            { label:`Mediana: ${mediana}`, data:Array(rc.n).fill(mediana),
              borderColor:'#22c55e', borderWidth:1.5, borderDash:[5,3],
              pointRadius:0, fill:false },
        ]},
        options: {
            responsive: true,
            plugins: {
                legend: { labels:{ color:'#334155', font:{size:11} } },
                tooltip: { backgroundColor:'#1e293b', bodyColor:'#cbd5e1',
                    callbacks:{ title: ctx => rc.fechas[ctx[0].dataIndex] ?? '' } },
                annotation: {
                    annotations: {
                        mediana: { type:'line', yMin:mediana, yMax:mediana,
                            borderColor:'#22c55e', borderWidth:1.5, borderDash:[5,3],
                            label:{ display:true, content:`Mediana: ${mediana}`,
                                    position:'end', color:'#16a34a', font:{size:10},
                                    backgroundColor:'rgba(255,255,255,0.85)' } }
                    }
                }
            },
            scales: {
                x:{ title:{display:true,text:'Lote / Turno',color:'#64748b'},
                    ticks:{color:'#64748b'}, grid:{color:'#f1f5f9'} },
                y:{ min:0, ticks:{color:'#64748b',callback:v=>v.toFixed(4)},
                    grid:{color:'#f1f5f9'},
                    title:{display:true,text:'Proporción p',color:'#64748b'} }
            }
        }
    });

    // Panel resultado
    const colorAl = rc.aleatorio ? '#16a34a' : '#dc2626';
    const iconAl  = rc.aleatorio ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    let html = `
    <div style="background:${rc.aleatorio?'#f0fdf4':'#fef2f2'};border:1px solid ${rc.aleatorio?'#86efac':'#fca5a5'};
                border-radius:8px;padding:10px 12px;margin-bottom:10px">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
            <i class="bi ${iconAl}" style="color:${colorAl}"></i>
            <strong style="color:${colorAl};font-size:13px">
                ${rc.aleatorio ? 'Variación aleatoria' : 'Patrón no aleatorio'}
            </strong>
        </div>
        <div style="font-size:12px;color:#374151">${rc.interpretacion}</div>
    </div>
    <table class="data-table-bordered" style="font-size:12px">
        <tbody>
            <tr><td style="text-align:left">k lotes</td><td>${rc.n}</td></tr>
            <tr><td style="text-align:left">Mediana p</td><td><strong>${rc.mediana}</strong></td></tr>
            <tr><td style="text-align:left">Rachas observadas</td><td><strong>${rc.rachas}</strong></td></tr>
            <tr><td style="text-align:left">Rachas esperadas</td><td>${rc.media_rachas}</td></tr>
            <tr><td style="text-align:left">Z estadístico</td><td>${rc.z_rachas}</td></tr>
            <tr><td style="text-align:left">p-valor</td>
                <td style="color:${rc.p_value<0.05?'#dc2626':'#16a34a'};font-weight:700">
                    ${rc.p_value}
                </td></tr>
        </tbody>
    </table>`;

    if (rc.tendencias && rc.tendencias.length > 0) {
        html += `<div style="margin-top:8px;padding:6px 10px;background:#fffbeb;
                     border:1px solid #fde68a;border-radius:6px;font-size:12px;color:#92400e">
            <i class="bi bi-arrow-up-right"></i> <strong>Tendencia en proporciones</strong>:
            ${rc.tendencias.map(t => `Lote ${t.pos} (${t.tipo})`).join(', ')}
        </div>`;
    }
    html += `<p style="font-size:11px;color:#94a3b8;margin-top:8px">${rc.ref}</p>`;
    document.getElementById('contenido-run-chart-p').innerHTML = html;
}

// ── Pareto de no conformes — Carta p ──
// Ref: Montgomery (2013), Cap.7, p.292
let chartParetoP = null;
function renderParetoP(d) {
    const sec = document.getElementById('seccion-pareto-p');
    if (d.tipo_carta !== 'p' || !d.pareto || d.pareto.length === 0) {
        sec.style.display = 'none'; return;
    }
    sec.style.display = 'block';
    const pareto = d.pareto;

    const labels   = pareto.map(p => p.fecha.length > 20 ? p.fecha.substring(0,18)+'…' : p.fecha);
    const barras   = pareto.map(p => p.no_conformes);
    const acumLine = pareto.map(p => p.pct_acumulado);

    // Colores: rojo si contribuye >20% al total, naranja si >10%, azul si menos
    const colores = pareto.map(p =>
        p.pct_relativo > 20 ? 'rgba(220,38,38,0.75)' :
        p.pct_relativo > 10 ? 'rgba(249,115,22,0.75)' :
        'rgba(124,58,237,0.65)'
    );

    if (chartParetoP) chartParetoP.destroy();
    chartParetoP = new Chart(document.getElementById('chart-pareto-p'), {
        type: 'bar',
        data: { labels, datasets: [
            { label:'No conformes', data:barras,
              backgroundColor:colores, borderColor:colores.map(c=>c.replace('0.75','1').replace('0.65','1')),
              borderWidth:1, yAxisID:'y' },
            { label:'% Acumulado', data:acumLine,
              type:'line', borderColor:'#f59e0b', backgroundColor:'transparent',
              borderWidth:2, pointRadius:4, pointBackgroundColor:'#f59e0b',
              fill:false, tension:0.1, yAxisID:'y2' },
        ]},
        options: {
            responsive: true,
            plugins: {
                legend: { labels:{ color:'#334155', font:{size:11} } },
                tooltip: { backgroundColor:'#1e293b', bodyColor:'#cbd5e1',
                    callbacks:{
                        title: ctx => pareto[ctx[0].dataIndex]?.fecha ?? '',
                        afterBody: ctx => {
                            const p = pareto[ctx[0].dataIndex];
                            if (!p) return '';
                            return [`Proporción: ${p.proporcion} (${p.porcentaje}%)`,
                                    `% del total: ${p.pct_relativo}%`,
                                    `% acumulado: ${p.pct_acumulado}%`];
                        }
                    }
                },
                annotation: {
                    annotations: {
                        pareto80: { type:'line', yScaleID:'y2', yMin:80, yMax:80,
                            borderColor:'#f59e0b', borderWidth:1.5, borderDash:[5,3],
                            label:{ display:true, content:'80%', position:'end',
                                    color:'#d97706', font:{size:10},
                                    backgroundColor:'rgba(255,255,255,0.85)' } }
                    }
                }
            },
            scales: {
                x: { ticks:{color:'#64748b',maxRotation:45,font:{size:10}}, grid:{color:'#f1f5f9'} },
                y: { position:'left', title:{display:true,text:'N° No conformes',color:'#64748b'},
                     ticks:{color:'#64748b'}, grid:{color:'#f1f5f9'} },
                y2: { position:'right', min:0, max:100,
                      title:{display:true,text:'% Acumulado',color:'#d97706'},
                      ticks:{color:'#d97706',callback:v=>v+'%'}, grid:{display:false} }
            }
        }
    });

    // Tabla Pareto
    let html = `<table class="data-table-bordered" style="font-size:12px">
        <thead>
            <tr>
                <th>Rango</th><th>Lote / Fecha</th>
                <th>No conf.</th><th>% Relativo</th><th>% Acum.</th>
            </tr>
        </thead>
        <tbody>
        ${pareto.map((p, i) => {
            const color = p.pct_relativo > 20 ? '#dc2626' :
                          p.pct_relativo > 10 ? '#d97706' : '#374151';
            const acumColor = p.pct_acumulado <= 80 ? '#16a34a' : '#64748b';
            return `<tr>
                <td style="text-align:center;font-weight:700">${i+1}</td>
                <td style="font-size:11px">${p.fecha}</td>
                <td style="text-align:center;font-weight:700;color:${color}">${p.no_conformes}</td>
                <td style="text-align:center;color:${color};font-weight:600">${p.pct_relativo}%</td>
                <td style="text-align:center;color:${acumColor};font-weight:600">${p.pct_acumulado}%</td>
            </tr>`;
        }).join('')}
        </tbody>
        <tfoot>
            <tr style="border-top:2px solid #e2e8f0;font-weight:700">
                <td colspan="2" style="text-align:right">Total</td>
                <td style="text-align:center">${d.total_nc_pareto}</td>
                <td style="text-align:center">100%</td><td></td>
            </tr>
        </tfoot>
    </table>
    <p style="font-size:11px;color:#94a3b8;margin-top:8px">
        Línea naranja: % acumulado · Regla 80/20: los primeros lotes/turnos
        concentran el mayor % de no conformes.<br>
        <em>Montgomery (2013), Cap.7, p.292</em>
    </p>`;
    document.getElementById('tabla-pareto-p').innerHTML = html;
}

// ── Violaciones ──
function renderViolaciones(d) {
    const card  = document.getElementById('card-violaciones');
    const panel = document.getElementById('panel-violaciones');
    if (!d.violaciones || d.violaciones.length === 0) {
        if (card) card.style.display = 'none'; return;
    }
    if (card) card.style.display = 'block';
    const leyenda = `
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;
                padding:8px 12px;background:#f8fafc;border-radius:6px;font-size:11px">
        <span style="font-weight:600;color:#374151">Colores en carta X̄:</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ef4444;margin-right:4px"></span>Regla 1 — Fuera ±3σ</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#f97316;margin-right:4px"></span>Regla 2 — 9 del mismo lado</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#eab308;margin-right:4px"></span>Regla 3 — Tendencia 6</span>
        <span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#a855f7;margin-right:4px"></span>Regla 4 — 2/3 fuera ±2σ</span>
    </div>`;
    panel.innerHTML = leyenda + d.violaciones.map(v => `
        <div style="display:flex;align-items:center;gap:12px;
                    background:#fef2f2;border-left:3px solid #ef4444;
                    border-radius:0 6px 6px 0;padding:8px 14px;
                    margin-bottom:6px;font-size:13px;color:#991b1b">
            <span class="badge badge-danger">SG ${v.subgrupo}</span>
            <span class="badge" style="background:#64748b;color:#fff;font-size:11px">Regla ${v.regla}</span>
            <span><strong>Regla ${v.regla}:</strong> ${v.desc}</span>
        </div>`).join('');
}

// ── Carta p ──
let chartP = null;
function renderCartaP(d) {
    const labels = d.subgrupos.map((sg,i) => `${i+1}\n${sg.fecha}`);
    const lcs    = d.limites_p.lcs;
    const lci    = d.limites_p.lci;
    const pbar   = d.limites_p.media;

    const pColors = d.xbars.map(v => v > lcs ? '#ef4444' : '#7c3aed');

    // Resumen en header
    document.getElementById('carta-p-resumen').innerHTML =
        `p̄ = ${pbar} | LCS = ${lcs} | LCI = ${lci}`;

    // Calcular max eje Y para que siempre se vea el LCS
    const maxVal = Math.max(lcs * 1.2, Math.max(...d.xbars) * 1.1, 0.05);

    // Anotaciones con etiquetas numéricas en las líneas
    const annotations = {
        lcs: {
            type: 'line', yMin: lcs, yMax: lcs,
            borderColor: '#ef4444', borderWidth: 1.5, borderDash: [6,3],
            label: {
                display: true, content: `LCS: ${lcs}`, position: 'end',
                backgroundColor: 'rgba(255,255,255,0.9)', color: '#ef4444',
                font: { size: 10, weight: 'bold' },
                padding: { x:4, y:2 }, borderRadius: 3,
            }
        },
        pbar: {
            type: 'line', yMin: pbar, yMax: pbar,
            borderColor: '#22c55e', borderWidth: 1.5,
            label: {
                display: true, content: `p̄: ${pbar}`, position: 'end',
                backgroundColor: 'rgba(255,255,255,0.9)', color: '#22c55e',
                font: { size: 10, weight: 'bold' },
                padding: { x:4, y:2 }, borderRadius: 3,
            }
        },
    };
    if (lci > 0) {
        annotations.lci = {
            type: 'line', yMin: lci, yMax: lci,
            borderColor: '#ef4444', borderWidth: 1.5, borderDash: [6,3],
            label: {
                display: true, content: `LCI: ${lci}`, position: 'end',
                backgroundColor: 'rgba(255,255,255,0.9)', color: '#ef4444',
                font: { size: 10, weight: 'bold' },
                padding: { x:4, y:2 }, borderRadius: 3,
            }
        };
    }

    if (chartP) chartP.destroy();
    chartP = new Chart(document.getElementById('chart-p'), {
        type: 'line',
        data: { labels, datasets: [
            { label:'p Proporción', data:d.xbars, borderColor:'#7c3aed',
              backgroundColor:'transparent', pointBackgroundColor:pColors,
              pointRadius:5, borderWidth:2, tension:0.1 },
        ]},
        options: {
            responsive: true,
            interaction: { mode:'index', intersect:false },
            plugins: {
                legend: { labels: { color:'#334155', font:{ size:11 } } },
                tooltip: { backgroundColor:'#1e293b', titleColor:'#f1f5f9', bodyColor:'#cbd5e1' },
                annotation: { annotations }
            },
            scales: {
                x: { ticks:{ color:'#64748b', maxRotation:45, font:{size:11} }, grid:{ color:'#f1f5f9' } },
                y: {
                    min: 0,
                    max: parseFloat(maxVal.toFixed(4)),
                    ticks: { color:'#64748b', callback: v => v.toFixed(4) },
                    grid: { color:'#f1f5f9' },
                    title: { display:true, text:'Proporción p', color:'#64748b' }
                }
            }
        }
    });
}

function renderTablaCártaP(d) {
    const lcs = d.limites_p.lcs;
    document.getElementById('tbody-carta-p').innerHTML = d.subgrupos.map(sg => {
        const badge = sg.proporcion > lcs
            ? '<span class="badge badge-danger">Fuera LC</span>'
            : '<span class="badge badge-success">OK</span>';
        return `<tr>
            <td>${sg.numero}</td>
            <td>${sg.fecha}</td>
            <td>${sg.lote_ref}</td>
            <td>${sg.n_obs}</td>
            <td style="color:#ef4444;font-weight:700">${sg.no_conformes}</td>
            <td style="color:#22c55e">${sg.conformes}</td>
            <td><strong>${sg.proporcion}</strong></td>
            <td>${sg.porcentaje}%</td>
            <td>${badge}</td>
        </tr>`;
    }).join('');
}

// ── Q-Q Plot (Gráfica de Probabilidad Normal) ──
// Ref: Montgomery (2013), Cap. 3
let chartQQ = null;
function renderQQPlot(d) {
    const qqEl = document.getElementById('chart-qq');
    const resEl = document.getElementById('qq-resultado');

    // Solo para cartas numéricas
    if (d.tipo_carta === 'p' || !d.qq_plot || !d.qq_plot.puntos?.length) {
        if (qqEl) qqEl.parentElement.parentElement.style.display = 'none';
        return;
    }
    if (qqEl) qqEl.parentElement.parentElement.style.display = 'block';

    const puntos = d.qq_plot.puntos;
    const linea  = d.qq_plot.linea;

    // Puntos muestrales: x = valor teórico, y = valor muestral
    const puntosData = puntos.map(p => ({ x: p.x_teorico, y: p.x_muestral }));
    const lineaData  = linea  ? linea.map(p => ({ x: p.x_teorico, y: p.x_muestral })) : [];

    // Color según normalidad
    const ad = d.normalidad ?? null;
    const colorPuntos = ad?.normal ? '#2563eb' : '#ef4444';

    if (chartQQ) chartQQ.destroy();
    chartQQ = new Chart(qqEl, {
        type: 'scatter',
        data: { datasets: [
            {
                label: 'Datos observados',
                data: puntosData,
                backgroundColor: colorPuntos,
                pointRadius: 4,
                pointHoverRadius: 6,
            },
            {
                label: 'Línea normal ref.',
                data: lineaData,
                type: 'line',
                borderColor: '#ef4444',
                borderWidth: 1.5,
                pointRadius: 0,
                fill: false,
                tension: 0,
            },
        ]},
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.6,
            plugins: {
                legend: { labels: { color:'#334155', font:{ size:11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => `Observado: ${ctx.parsed.y} | Teórico: ${ctx.parsed.x}`
                    }
                }
            },
            scales: {
                x: {
                    title: { display:true, text:'Cuantiles teóricos normales', color:'#64748b' },
                    ticks: { color:'#64748b' }, grid: { color:'#f1f5f9' }
                },
                y: {
                    title: { display:true, text:'Valores observados', color:'#64748b' },
                    ticks: { color:'#64748b' }, grid: { color:'#f1f5f9' }
                }
            }
        }
    });

    // Resultado AD debajo del Q-Q
    if (ad && ad.ad !== null && resEl) {
        resEl.innerHTML = `<div style="padding:6px 10px;border-radius:6px;font-size:12px;
            background:${ad.normal?'#f0fdf4':'#fef2f2'};
            border:1px solid ${ad.normal?'#86efac':'#fca5a5'};
            color:${ad.normal?'#15803d':'#dc2626'}">
            <strong>Prueba Anderson-Darling</strong> (Montgomery 2013, Cap.3)<br>
            AD = ${ad.ad} &nbsp;·&nbsp; p-valor = ${ad.p_value} &nbsp;·&nbsp;
            ${ad.normal ? '✔ Normalidad confirmada (p ≥ 0.05)' : '✗ No normalidad (p < 0.05)'}
        </div>`;
    }
}

function limpiarFiltros() {
    ['fil_producto','fil_parametro'].forEach(id => document.getElementById(id).value = '');
    ['fil_desde','fil_hasta'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('specs-banner').style.display = 'none';
    document.getElementById('spc-resultados').style.display = 'none';
    document.getElementById('spc-placeholder').style.display = 'block';
    document.getElementById('btn-imprimir-spc').style.display = 'none';
    // Limpiar divs dinámicos
    ['fase-banner','panel-advertencias-metodologicas',
    'stat-cards','hist-ad-resultado','qq-resultado',
    'tabla-capacidad','contenido-capacidad','contenido-anova',
    'contenido-indices-adic','contenido-run-chart','contenido-run-chart-p',
    'tabla-pareto-p','panel-violaciones','tbody-oc-refs'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
    ['seccion-normalidad','seccion-curva-oc','seccion-run-chart',
    'seccion-run-chart-p','seccion-pareto-p','card-violaciones'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
    Array.from(document.getElementById('fil_parametro').options).forEach(opt => {
        opt.style.display = opt.value ? 'none' : '';
    });
    document.querySelector('#fil_parametro option:first-child').textContent = '-- Seleccione producto primero --';
}

// ── Exportar / Imprimir análisis SPC ──
function imprimirAnalisis() {
    // Actualizar header de impresión con producto y parámetro
    const prod  = document.getElementById('fil_producto');
    const param = document.getElementById('fil_parametro');
    const prodNombre  = prod?.options[prod?.selectedIndex]?.text ?? '—';
    const paramNombre = param?.options[param?.selectedIndex]?.text ?? '—';
    const printPP = document.getElementById('print-producto-param');
    if (printPP) printPP.textContent = `${prodNombre} — ${paramNombre}`;

    // Pequeña pausa para que Chart.js termine de renderizar
    setTimeout(() => window.print(), 300);
}
</script>