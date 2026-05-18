<?php
/** @var array $mantenimientos @var array $stats @var array $alertas
 *  @var array $filtros @var array $tipos @var array $estados @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🔧 Mantenimiento</div>
        <div class="page-sub">
            Preventivo, correctivo y calibraciones.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m3/nuevo" class="btn btn-primary">+ Nuevo registro</a>
    </div>
    <?php endif ?>
</div>

<!-- Estadísticas -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">🔧</div>
        <div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Registros este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">✅</div>
        <div>
            <div class="stat-value" style="color:#15803d"><?= $stats['completados'] ?></div>
            <div class="stat-label">Completados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">⏱️</div>
        <div>
            <div class="stat-value" style="color:#dc2626">
                <?= number_format($stats['horas_paro'], 1) ?>h
            </div>
            <div class="stat-label">Horas de paro correctivo</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">⚠️</div>
        <div>
            <div class="stat-value"
                    style="color:<?= $stats['alertas'] > 0 ? '#d97706' : '#15803d' ?>">
                <?= $stats['alertas'] ?>
            </div>
            <div class="stat-label">Equipos próximos a vencer</div>
        </div>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($alertas)): ?>
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;
            padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;color:#c2410c;margin-bottom:8px">
        ⚠️ Equipos con mantenimiento vencido o próximo (15 días)
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:6px">
    <?php foreach (array_slice($alertas, 0, 6) as $a):
        $dias  = (int)$a['dias_restantes'];
        $color = $dias < 0 ? '#dc2626' : ($dias < 7 ? '#c2410c' : '#d97706');
    ?>
    <div style="font-size:12px;color:#92400e;background:#fef3c7;
                border-radius:6px;padding:6px 10px">
        <strong><?= htmlspecialchars($a['codigo']) ?></strong>
        — <?= htmlspecialchars($a['equipo_nombre']) ?><br>
        <span style="color:<?= $color ?>">
            <?= $dias < 0
                ? '⛔ Vencido hace '.abs($dias).'d'
                : '⏰ Vence en '.$dias.'d ('.date('d/m/Y',strtotime($a['fecha_prox_mant'])).')' ?>
        </span>
    </div>
    <?php endforeach ?>
    </div>
    <?php if (count($alertas) > 6): ?>
    <a href="<?= APP_URL ?>/m3/alertas"
        style="font-size:12px;color:#92400e;margin-top:8px;display:block">
        Ver todas las alertas (<?= count($alertas) ?>) →
    </a>
    <?php endif ?>
</div>
<?php endif ?>

<!-- Filtros -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"><span class="card-title">🔍 Filtros</span></div>
    <form method="GET" action="<?= APP_URL ?>/m3">
    <div class="card-body">
        <div class="form-row cols-4">
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $val => $info): ?>
                    <option value="<?= $val ?>"
                        <?= ($filtros['tipo'] ?? '') === $val ? 'selected':'' ?>>
                        <?= $info['label'] ?>
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
        <a href="<?= APP_URL ?>/m3" class="btn btn-secondary">Limpiar</a>
        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
    </div>
    </form>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th style="text-align:center">Tipo</th>
                        <th>Descripción</th>
                        <th style="text-align:center">Fecha inicio</th>
                        <th style="text-align:center">Duración</th>
                        <th style="text-align:center">Resultado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($mantenimientos)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:40px">
                        No hay registros de mantenimiento.
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m3/nuevo">Crear el primero</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($mantenimientos as $m):
                    $tipo = $tipos[$m['tipo']] ?? ['label'=>$m['tipo'],'badge'=>'badge-muted'];
                    $est  = $estados[$m['resultado']] ?? ['label'=>$m['resultado'],'badge'=>'badge-muted'];
                    $durMin = (int)($m['duracion_min'] ?? 0);
                    $durFmt = $durMin >= 60
                        ? floor($durMin/60).'h '.($durMin%60).'min'
                        : ($durMin > 0 ? $durMin.'min' : '—');
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($m['equipo_nombre']) ?></strong>
                        <div style="font-size:11px;color:#94a3b8">
                            <?= htmlspecialchars($m['equipo_codigo']) ?> ·
                            <?= htmlspecialchars($m['linea_nombre']) ?>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $tipo['badge'] ?>">
                            <?= $tipo['label'] ?>
                        </span>
                    </td>
                    <td style="font-size:12px;max-width:200px">
                        <?= htmlspecialchars(
                            mb_strimwidth($m['descripcion_trabajo'] ?? '—', 0, 60, '...')
                        ) ?>
                    </td>
                    <td style="text-align:center;font-size:12px">
                        <?= $m['fecha_inicio']
                            ? date('d/m/Y H:i', strtotime($m['fecha_inicio']))
                            : ($m['fecha_programada']
                                ? '📅 '.date('d/m/Y', strtotime($m['fecha_programada']))
                                : '—') ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge badge-muted"><?= $durFmt ?></span>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $est['badge'] ?>">
                            <?= $est['label'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m3/<?= $m['id'] ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                        <?php if ($canWrite): ?>
                        <a href="<?= APP_URL ?>/m3/<?= $m['id'] ?>/editar"
                            class="btn btn-sm btn-secondary">Editar</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>