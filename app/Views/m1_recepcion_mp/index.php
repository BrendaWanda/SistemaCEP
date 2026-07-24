<?php
/** @var array $stats
 *  @var array $ultimas
 *  @var array $alertas
 *  @var array $pendientesResolucion
 *  @var array $decisiones
 *  @var bool  $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title"><i class="bi bi-truck-front-fill"></i> Recepción de Materia Prima</div>
        <div class="page-sub">
            Control de ingreso, análisis y aprobación de materias primas e insumos.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/nueva" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva recepción
        </a>
    </div>
    <?php endif ?>
</div>

<!-- Estadísticas del mes -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div>
            <div class="stat-value"><?= $stats['total_mes'] ?></div>
            <div class="stat-label">Recepciones este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="stat-value" style="color:#15803d"><?= $stats['aprobadas'] ?></div>
            <div class="stat-label">Aprobadas este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2"><i class="bi bi-x-circle-fill"></i></div>
        <div>
            <div class="stat-value" style="color:#dc2626"><?= $stats['rechazadas'] ?></div>
            <div class="stat-label">Rechazadas este mes</div>
        </div>
    </div>
    <!-- AGREGADO: no había forma visible de saber cuántas recepciones
         están esperando resolución (cuarentena/observado) — a
         diferencia de las alertas de vencimiento, que sí tienen su
         propio indicador. -->
    <div class="stat-card">
        <div class="stat-icon" style="background:#f5f3ff"><i class="bi bi-question-diamond-fill"></i></div>
        <div>
            <div class="stat-value" style="color:<?= $stats['pendientes'] > 0 ? '#7c3aed' : '#94a3b8' ?>">
                <?= $stats['pendientes'] ?>
            </div>
            <div class="stat-label">Pendientes de resolución</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="stat-value" style="color:<?= $stats['por_vencer'] > 0 ? '#d97706' : '#15803d' ?>">
                <?= $stats['por_vencer'] ?>
            </div>
            <div class="stat-label">Lotes por vencer (30d)</div>
        </div>
    </div>
</div>

<!-- AGREGADO: banner de recepciones pendientes de resolución -->
<?php if (!empty($pendientesResolucion)): ?>
<div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;
            padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;color:#6d28d9;margin-bottom:8px">
        <i class="bi bi-question-diamond-fill"></i> Recepciones pendientes de resolución
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:6px">
    <?php foreach ($pendientesResolucion as $p): ?>
    <div style="font-size:12px;color:#5b21b6;background:#ede9fe;
                border-radius:6px;padding:6px 10px">
        <strong><?= htmlspecialchars($p['codigo_recepcion']) ?></strong>
        — <?= htmlspecialchars($p['insumo_nombre']) ?><br>
        <?= htmlspecialchars($p['proveedor_nombre']) ?> ·
        <span class="badge badge-warning"><?= strtoupper($p['decision']) ?></span>
        <a href="<?= APP_URL ?>/m1/<?= $p['id'] ?>/resolver"
            style="margin-left:6px;font-weight:600">Resolver →</a>
    </div>
    <?php endforeach ?>
    </div>
    <?php if ($stats['pendientes'] > 6): ?>
    <div style="margin-top:8px;font-size:var(--fs-dense-xs)">
        <a href="<?= APP_URL ?>/m1/historial?decision=cuarentena" style="color:#6d28d9">
            Ver todas las pendientes (<?= $stats['pendientes'] ?>) →
        </a>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<!-- Alertas de vencimiento -->
<?php if (!empty($alertas)): ?>
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;
            padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;color:#c2410c;margin-bottom:8px">
        <i class="bi bi-exclamation-triangle-fill"></i> Materia prima próxima a vencer
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:6px">
    <?php foreach (array_slice($alertas, 0, 6) as $a):
        $dias = (int)$a['dias_restantes'];
        $color = $dias < 0 ? '#dc2626' : ($dias < 15 ? '#c2410c' : '#d97706');
    ?>
    <div style="font-size:12px;color:#92400e;background:#fef3c7;
                border-radius:6px;padding:6px 10px">
        <strong><?= htmlspecialchars($a['insumo_codigo']) ?></strong>
        — <?= htmlspecialchars($a['insumo_nombre']) ?><br>
        <span style="color:<?= $color ?>">
            <?= $dias < 0
                ? '<i class="bi bi-x-circle-fill"></i> Vencido hace '.abs($dias).'d'
                : '<i class="bi bi-alarm-fill"></i> Vence en '.$dias.'d ('
                    .date('d/m/Y', strtotime($a['fecha_vencimiento'])).')' ?>
        </span>
        · Disp: <?= number_format($a['cantidad_disponible'],2) ?>
        <?= htmlspecialchars($a['unidad_medida']) ?>
    </div>
    <?php endforeach ?>
    </div>
    <?php if (count($alertas) > 6): ?>
    <div style="margin-top:8px;font-size:var(--fs-dense-xs)">
        <a href="<?= APP_URL ?>/m1/alertas-vencimiento" style="color:#92400e">
            Ver todas las alertas (<?= count($alertas) ?>) →
        </a>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<!-- Últimas recepciones -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Últimas recepciones</span>
        <a href="<?= APP_URL ?>/m1/historial" class="btn btn-sm btn-secondary">
            Ver historial completo
        </a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th>Proveedor</th>
                    <th style="text-align:center">Cantidad</th>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Decisión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ultimas)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted" style="padding:40px">
                    No hay recepciones registradas.
                    <?php if ($canWrite): ?>
                    <a href="<?= APP_URL ?>/m1/nueva">Registrar la primera</a>
                    <?php endif ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($ultimas as $r):
                $dec = $decisiones[$r['decision']] ?? ['label'=>$r['decision'],'badge'=>'badge-muted'];
            ?>
            <tr>
                <td>
                    <code style="background:#f1f5f9;padding:2px 6px;
                                border-radius:4px;font-size:var(--fs-dense-xs)">
                        <?= htmlspecialchars($r['codigo_recepcion']) ?>
                    </code>
                </td>
                <td>
                    <strong><?= htmlspecialchars($r['insumo_nombre']) ?></strong>
                    <div style="font-size:var(--fs-dense-xs);color:#94a3b8">
                        <?= htmlspecialchars($r['insumo_codigo']) ?>
                        · Lote: <?= htmlspecialchars($r['lote_proveedor']) ?>
                    </div>
                </td>
                <td style="font-size:var(--fs-dense-xs)">
                    <?= htmlspecialchars($r['proveedor_nombre']) ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= number_format($r['cantidad_recibida'],2) ?>
                    <?= htmlspecialchars($r['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:var(--fs-dense-xs)">
                    <?= date('d/m/Y', strtotime($r['fecha_analisis'])) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $dec['badge'] ?>">
                        <?= $dec['label'] ?>
                    </span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/m1/<?= $r['id'] ?>"
                        class="btn btn-sm btn-secondary">Ver</a>
                    <a href="<?= APP_URL ?>/m1/<?= $r['id'] ?>/imprimir"
                        class="btn btn-sm btn-secondary" target="_blank"><i class="bi bi-printer-fill"></i></a>
                </td>
            </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>