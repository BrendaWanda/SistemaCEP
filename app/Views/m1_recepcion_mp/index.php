<?php
/** @var array $stats @var array $ultimas @var array $alertas @var array $decisiones @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">🚛 Recepción de Materia Prima</div>
        <div class="page-sub">
            Control de ingreso, análisis y aprobación de materias primas e insumos.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/nueva" class="btn btn-primary">+ Nueva recepción</a>
    </div>
    <?php endif ?>
</div>

<!-- Estadísticas del mes -->
<div class="stats-grid" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">📋</div>
        <div>
            <div class="stat-value"><?= $stats['total_mes'] ?></div>
            <div class="stat-label">Recepciones este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">✅</div>
        <div>
            <div class="stat-value" style="color:#15803d"><?= $stats['aprobadas'] ?></div>
            <div class="stat-label">Aprobadas este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2">❌</div>
        <div>
            <div class="stat-value" style="color:#dc2626"><?= $stats['rechazadas'] ?></div>
            <div class="stat-label">Rechazadas este mes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed">⚠️</div>
        <div>
            <div class="stat-value" style="color:<?= $stats['por_vencer'] > 0 ? '#d97706' : '#15803d' ?>">
                <?= $stats['por_vencer'] ?>
            </div>
            <div class="stat-label">Lotes por vencer (30d)</div>
        </div>
    </div>
</div>

<!-- Alertas de vencimiento -->
<?php if (!empty($alertas)): ?>
<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;
            padding:12px 16px;margin-bottom:16px">
    <div style="font-weight:700;color:#c2410c;margin-bottom:8px">
        ⚠️ Materia prima próxima a vencer
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
                ? '⛔ Vencido hace '.abs($dias).'d'
                : '⏰ Vence en '.$dias.'d ('
                    .date('d/m/Y', strtotime($a['fecha_vencimiento'])).')' ?>
        </span>
        · Disp: <?= number_format($a['cantidad_disponible'],2) ?>
        <?= htmlspecialchars($a['unidad_medida']) ?>
    </div>
    <?php endforeach ?>
    </div>
    <?php if (count($alertas) > 6): ?>
    <div style="margin-top:8px;font-size:12px">
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
                                border-radius:4px;font-size:11px">
                        <?= htmlspecialchars($r['codigo_recepcion']) ?>
                    </code>
                </td>
                <td>
                    <strong><?= htmlspecialchars($r['insumo_nombre']) ?></strong>
                    <div style="font-size:11px;color:#94a3b8">
                        <?= htmlspecialchars($r['insumo_codigo']) ?>
                        · Lote: <?= htmlspecialchars($r['lote_proveedor']) ?>
                    </div>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($r['proveedor_nombre']) ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= number_format($r['cantidad_recibida'],2) ?>
                    <?= htmlspecialchars($r['unidad_medida']) ?>
                </td>
                <td style="text-align:center;font-size:12px">
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
                        class="btn btn-sm btn-secondary" target="_blank">🖨️</a>
                </td>
            </tr>
            <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>