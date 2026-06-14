<?php
/** @var array $sesiones @var array $stats @var array $filtros
 *  @var array $estados @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📋 Registro de Proceso</div>
        <div class="page-sub">
            Control estadístico en tiempo real · Formularios SIREMU digitalizados
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m2/nueva-sesion" class="btn btn-primary">
            + Nueva sesión
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Estadísticas -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">📋</div>
        <div>
            <div class="stat-value"><?= $stats['total_hoy'] ?></div>
            <div class="stat-label">Sesiones hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">✅</div>
        <div>
            <div class="stat-value" style="color:#15803d">
                <?= $stats['liberados_hoy'] ?>
            </div>
            <div class="stat-label">Liberados hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⚠️</div>
        <div>
            <div class="stat-value"
                    style="color:<?= $stats['con_senales'] > 0 ? '#dc2626' : '#15803d' ?>">
                <?= $stats['con_senales'] ?>
            </div>
            <div class="stat-label">Señales SPC (7 días)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">🔒</div>
        <div>
            <div class="stat-value"
                    style="color:<?= $stats['cuarentena'] > 0 ? '#d97706' : '#94a3b8' ?>">
                <?= $stats['cuarentena'] ?>
            </div>
            <div class="stat-label">En cuarentena</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"><span class="card-title">🔍 Filtros</span></div>
    <form method="GET" action="<?= APP_URL ?>/m2">
    <div class="card-body">
        <div class="form-row cols-3">
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $val => $info): ?>
                    <option value="<?= $val ?>"
                        <?= ($filtros['estado'] ?? '') === $val ? 'selected':'' ?>>
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
        <a href="<?= APP_URL ?>/m2" class="btn btn-secondary">Limpiar</a>
        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
    </div>
    </form>
</div>

<!-- Tabla de sesiones -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Producto</th>
                        <th style="text-align:center">Fecha</th>
                        <th style="text-align:center">Turno</th>
                        <th style="text-align:center">Pesos</th>
                        <th style="text-align:center">Señales SPC</th>
                        <th style="text-align:center">Liberación</th>
                        <th style="text-align:center">Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sesiones)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted"
                        style="padding:40px">
                        No hay sesiones de registro.
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m2/nueva-sesion">
                            Crear la primera
                        </a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($sesiones as $s):
                    $est = $estados[$s['estado']]
                            ?? ['label'=>$s['estado'],'badge'=>'badge-muted'];
                ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/m2/sesion/<?= $s['id'] ?>"
                            style="font-weight:700;color:#1d4ed8;
                                    text-decoration:none;font-family:monospace">
                            <?= htmlspecialchars($s['codigo_lote']) ?>
                        </a>
                    </td>
                    <td>
                        <strong style="font-size:12px">
                            <?= htmlspecialchars($s['producto_nombre']) ?>
                        </strong>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?= date('d/m/Y', strtotime($s['fecha'])) ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted">
                            <?= ucfirst($s['lote_turno']) ?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-info">
                            <?= (int)$s['total_pesos'] ?> subgrupos
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?php if ($s['senales_spc'] > 0): ?>
                        <span class="badge badge-danger">
                            ⚠️ <?= $s['senales_spc'] ?> señal(es)
                        </span>
                        <?php else: ?>
                        <span class="badge badge-success">✓ OK</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <?php if ($s['tiene_liberacion']): ?>
                        <span class="badge badge-success">✓ Liberado</span>
                        <?php else: ?>
                        <span class="badge badge-muted">Pendiente</span>
                        <?php endif ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $est['badge'] ?>">
                            <?= $est['label'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m2/sesion/<?= $s['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <a href="<?= APP_URL ?>/m2/sesion/<?= $s['id'] ?>/imprimir"
                            class="btn btn-sm btn-secondary"
                            target="_blank">Imprimir</a>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>