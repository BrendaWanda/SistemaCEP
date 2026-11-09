<?php
/** @var string $pageTitle */
?>

<div class="page-header">
    <div>
        <div class="page-title">📄 Reportes</div>
        <div class="page-sub">
            Generación de reportes para el SIREMU y control interno.
        </div>
    </div>
</div>

<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $fechaDesde = date('Y-m-01'); ?>
<?php $fechaHasta = date('Y-m-d'); ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- Recepción MP -->
    <div class="card">
        <div class="card-header" style="background:#0891b2;color:#fff">
            <span class="card-title" style="color:#fff">
                🚛 Recepción de Materia Prima
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Listado de recepciones con decisión de calidad.
                Incluye análisis organoléptico y fisicoquímico.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/recepcion-mp"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- Seguimiento producción -->
    <div class="card">
        <div class="card-header" style="background:#d97706;color:#fff">
            <span class="card-title" style="color:#fff">
                🏭 Seguimiento de Producción
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Resumen de lotes producidos con rendimiento real vs teórico y mermas.
            </div>
            <form method="POST"
                    action="<?= APP_URL ?>/reportes/seguimiento-produccion"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- Liberación PT -->
    <div class="card">
        <div class="card-header" style="background:#7c3aed;color:#fff">
            <span class="card-title" style="color:#fff">
                ✅ Liberación de Producto Terminado
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Registros de liberación o cuarentena de PT por supervisor de calidad.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/liberacion-lote"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- SPC X̄-R -->
    <div class="card">
        <div class="card-header" style="background:#1d4ed8;color:#fff">
            <span class="card-title" style="color:#fff">
                📊 SPC — Gráfico X̄-R y Capacidad
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Reporte de control estadístico con límites X̄-R e índices Cp/Cpk.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/spc-xbar"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-control">
                    <option value="">Todos los productos</option>
                    <?php
                    $productos = (new \App\Models\Producto())->paraSelect(1);
                    foreach ($productos as $id => $nombre):
                    ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- Mantenimiento -->
    <div class="card">
        <div class="card-header" style="background:#374151;color:#fff">
            <span class="card-title" style="color:#fff">
                🔧 Mantenimiento
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Historial de mantenimientos preventivos, correctivos y calibraciones.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/mantenimiento"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- KPIs y OEE -->
    <div class="card">
        <div class="card-header" style="background:#15803d;color:#fff">
            <span class="card-title" style="color:#fff">
                ⚡ KPIs y OEE del período
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Reporte ejecutivo con OEE, disponibilidad, rendimiento y calidad.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/kpi-periodo"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                            value="<?= $fechaDesde ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                            value="<?= $fechaHasta ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                🖨️ Generar reporte
            </button>
            </form>
        </div>
    </div>

    <!-- Trazabilidad por lote -->
    <div class="card" style="grid-column:span 2">
        <div class="card-header" style="background:#1e3a5f;color:#fff">
            <span class="card-title" style="color:#fff">
                🔗 Trazabilidad completa por lote
            </span>
        </div>
        <div class="card-body">
            <div style="font-size:12px;color:#64748b;margin-bottom:12px">
                Árbol completo de trazabilidad: MP → proceso → liberación.
                Se abre la vista de trazabilidad del M5 en formato impresión.
            </div>
            <form method="POST" action="<?= APP_URL ?>/reportes/trazabilidad-lote"
                    target="_blank">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div style="display:flex;gap:10px;align-items:flex-end">
                <div class="form-group" style="flex:1;margin-bottom:0">
                    <label class="form-label">Código de lote</label>
                    <input type="text" name="codigo_lote" class="form-control"
                            placeholder="Ej: 18-05"
                            style="font-family:monospace;font-size:15px">
                </div>
                <button type="submit" class="btn btn-primary"
                        style="padding:10px 24px">
                    🔗 Ver trazabilidad
                </button>
            </div>
            </form>
        </div>
    </div>

</div>