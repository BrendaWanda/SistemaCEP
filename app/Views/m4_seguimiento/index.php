<?php
/** @var array $lotes @var array $stats @var array $filtros
 *  @var array $productos @var array $estados @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🏭 Seguimiento de Producción</div>
        <div class="page-sub">
            Control de lotes, rendimiento real vs teórico y mermas.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m4/nuevo-lote" class="btn btn-primary">
            + Abrir nuevo lote
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Estadísticas del mes -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">📋</div>
        <div>
            <div class="stat-value"><?= $stats['total_lotes'] ?></div>
            <div class="stat-label">Lotes este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">✅</div>
        <div>
            <div class="stat-value" style="color:#15803d">
                <?= $stats['liberados'] ?>
            </div>
            <div class="stat-label">Liberados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">⚙️</div>
        <div>
            <div class="stat-value" style="color:#d97706">
                <?= $stats['en_proceso'] ?>
            </div>
            <div class="stat-label">En proceso</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">⚠️</div>
        <div>
            <div class="stat-value" style="color:<?= $stats['cuarentena'] > 0 ? '#dc2626' : '#94a3b8' ?>">
                <?= $stats['cuarentena'] ?>
            </div>
            <div class="stat-label">En cuarentena</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4">📊</div>
        <div>
            <div class="stat-value">
                <?= number_format($stats['rend_promedio'], 1) ?>%
            </div>
            <div class="stat-label">Rendimiento promedio</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"><span class="card-title">🔍 Filtros</span></div>
    <form method="GET" action="<?= APP_URL ?>/m4">
    <div class="card-body">
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="form-label">Producto</label>
                <select name="producto_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= ($filtros['producto_id'] ?? '') == $id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $val => $info): ?>
                    <option value="<?= $val ?>"
                        <?= ($filtros['estado'] ?? '') === $val ? 'selected' : '' ?>>
                        <?= $info['label'] ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control"
                    value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control"
                    value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m4" class="btn btn-secondary">Limpiar</a>
        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
    </div>
    </form>
</div>

<!-- Tabla de lotes -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código lote</th>
                        <th>Producto</th>
                        <th style="text-align:center">Fecha</th>
                        <th style="text-align:center">Turno</th>
                        <th style="text-align:center">Recetas</th>
                        <th style="text-align:center">Rendimiento</th>
                        <th style="text-align:center">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($lotes)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:40px">
                        No hay lotes registrados.
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m4/nuevo-lote">Abrir el primero</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($lotes as $l):
                    $est = $estados[$l['estado']] ?? ['label'=>$l['estado'],'badge'=>'badge-muted'];
                    $pct = (float)($l['porcentaje_rendimiento'] ?? 0);
                    $colorRend = $pct >= 95 ? '#15803d' : ($pct >= 85 ? '#d97706' : '#dc2626');
                ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>"
                            style="font-weight:700;color:#1d4ed8;text-decoration:none;
                                font-family:monospace;font-size:14px">
                            <?= htmlspecialchars($l['codigo_lote']) ?>
                        </a>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($l['producto_nombre']) ?></strong>
                        <div style="font-size:11px;color:#94a3b8">
                            <?= htmlspecialchars($l['linea_nombre']) ?>
                        </div>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted">
                            <?= ucfirst($l['turno']) ?>
                        </span>
                    </td>
                    <td style="text-align:center;font-weight:600">
                        <?= number_format((float)$l['numero_recetas'], 1) ?>
                    </td>
                    <td style="text-align:center">
                        <?php if ($pct > 0): ?>
                        <div style="font-size:14px;font-weight:700;color:<?= $colorRend ?>">
                            <?= number_format($pct, 1) ?>%
                        </div>
                        <div style="font-size:10px;color:#94a3b8">
                            <?= number_format($l['rendimiento_real_total'] ?? 0) ?>
                            / <?= number_format($l['rendimiento_teorico_total'] ?? 0) ?>
                            und
                        </div>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:12px">Sin datos</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $est['badge'] ?>">
                            <?= $est['label'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>/imprimir"
                            class="btn btn-sm btn-secondary" target="_blank">🖨️</a>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>