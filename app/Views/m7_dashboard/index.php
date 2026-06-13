<?php
/** @var array $stats @var array $ultimosLotes @var array $alertasStock
 *  @var array $alertasMant @var array $sesionesHoy @var array $lotesEnProceso */
?>

<div class="page-header">
    <div>
        <div class="page-title">Bienvenido, <?= htmlspecialchars(explode(' ', $user['nombre'] ?? 'Usuario')[0]) ?></div>
        <div class="page-sub">
            <?= date('l, d \d\e F \d\e Y') ?> · Resumen operativo del día
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m4/nuevo-lote" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo lote
        </a>
    </div>
</div>

<!-- ── KPIs rápidos del día ──────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff">
            <i class="bi bi-building-fill-gear" style="color:#2563eb;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value"><?= $stats['lotes_hoy'] ?></div>
            <div class="stat-label">Lotes producidos hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4">
            <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value" style="color:#16a34a"><?= $stats['liberados_hoy'] ?></div>
            <div class="stat-label">Liberados hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fafafa">
            <i class="bi bi-hourglass-split" style="color:#71717a;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value" style="color:<?= $stats['en_proceso'] > 0 ? '#d97706' : '#71717a' ?>">
                <?= $stats['en_proceso'] ?>
            </div>
            <div class="stat-label">Lotes en proceso</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2">
            <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value" style="color:<?= $stats['alertas'] > 0 ? '#dc2626' : '#09090b' ?>">
                <?= $stats['alertas'] ?>
            </div>
            <div class="stat-label">Alertas activas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff">
            <i class="bi bi-truck-front-fill" style="color:#2563eb;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value"><?= $stats['recepciones_hoy'] ?></div>
            <div class="stat-label">Recepciones MP hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fafafa">
            <i class="bi bi-graph-up-arrow" style="color:#2563eb;font-size:20px"></i>
        </div>
        <div>
            <div class="stat-value">
                <?= $stats['rend_promedio'] > 0 ? number_format($stats['rend_promedio'],1).'%' : '—' ?>
            </div>
            <div class="stat-label">Rendimiento promedio hoy</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Lotes en proceso -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-hourglass-split" style="color:#d97706;margin-right:6px"></i>
                Lotes en proceso
            </span>
            <a href="<?= APP_URL ?>/m4" class="btn btn-sm btn-secondary">Ver todos</a>
        </div>
        <?php if (empty($lotesEnProceso)): ?>
        <div class="card-body text-center" style="padding:30px;color:#71717a">
            <i class="bi bi-check-circle" style="font-size:32px;color:#e4e4e7"></i>
            <div style="margin-top:8px;font-size:13px">Sin lotes en proceso</div>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Producto</th>
                        <th style="text-align:center">Desde</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($lotesEnProceso, 0, 5) as $l): ?>
                <tr>
                    <td>
                        <span style="font-family:monospace;font-weight:700;
                                    color:#2563eb;font-size:13px">
                            <?= htmlspecialchars($l['codigo_lote']) ?>
                        </span>
                    </td>
                    <td style="font-size:13px">
                        <?= htmlspecialchars($l['producto_nombre']) ?>
                    </td>
                    <td style="text-align:center;font-size:12px;color:#71717a">
                        <?= date('d/m', strtotime($l['fecha_produccion'])) ?>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>"
                            class="btn btn-sm btn-secondary">Ver</a>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>

    <!-- Alertas activas -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-bell-fill" style="color:#dc2626;margin-right:6px"></i>
                Alertas activas
            </span>
        </div>
        <div class="card-body" style="padding:12px 16px">
            <?php
            $hayAlertas = false;

            // Alertas de stock
            foreach (array_slice($alertasStock, 0, 3) as $a):
                $hayAlertas = true;
                $dias = (int)$a['dias_restantes'];
                $color = $dias < 0 ? '#dc2626' : '#d97706';
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                        border-bottom:1px solid #f4f4f5">
                <div style="width:32px;height:32px;border-radius:6px;
                            background:#fef2f2;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0">
                    <i class="bi bi-box-seam" style="color:#dc2626;font-size:14px"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:#09090b;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= htmlspecialchars($a['insumo_nombre']) ?>
                    </div>
                    <div style="font-size:11px;color:<?= $color ?>">
                        <?= $dias < 0
                            ? 'Vencido hace '.abs($dias).'d'
                            : 'Vence en '.$dias.'d' ?>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/m1/alertas-vencimiento"
                    style="font-size:11px;color:#2563eb;text-decoration:none">Ver →</a>
            </div>
            <?php endforeach ?>

            <!-- Alertas de mantenimiento -->
            <?php foreach (array_slice($alertasMant, 0, 3) as $m):
                $hayAlertas = true;
                $dias = (int)$m['dias_restantes'];
                $color = $dias < 0 ? '#dc2626' : '#d97706';
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                        border-bottom:1px solid #f4f4f5">
                <div style="width:32px;height:32px;border-radius:6px;
                            background:#fffbeb;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0">
                    <i class="bi bi-wrench-adjustable" style="color:#d97706;font-size:14px"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:#09090b;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= htmlspecialchars($m['equipo_nombre']) ?>
                    </div>
                    <div style="font-size:11px;color:<?= $color ?>">
                        Mant. <?= $dias < 0 ? 'vencido hace '.abs($dias).'d' : 'en '.$dias.'d' ?>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/m3/alertas"
                    style="font-size:11px;color:#2563eb;text-decoration:none">Ver →</a>
            </div>
            <?php endforeach ?>

            <?php if (!$hayAlertas): ?>
            <div style="text-align:center;padding:20px;color:#71717a">
                <i class="bi bi-check-circle" style="font-size:28px;color:#e4e4e7"></i>
                <div style="margin-top:8px;font-size:13px">Sin alertas activas</div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Últimas recepciones + Accesos rápidos -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">

    <!-- Últimos lotes -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-clock-history" style="margin-right:6px;color:#71717a"></i>
                Últimos lotes producidos
            </span>
            <a href="<?= APP_URL ?>/m4" class="btn btn-sm btn-secondary">Ver todos</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Producto</th>
                        <th style="text-align:center">Fecha</th>
                        <th style="text-align:center">Rend.</th>
                        <th style="text-align:center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($ultimosLotes)): ?>
                <tr>
                    <td colspan="5" class="text-center"
                        style="padding:30px;color:#71717a">
                        No hay lotes registrados aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($ultimosLotes as $l):
                    $pct = (float)($l['porcentaje_rendimiento'] ?? 0);
                    $colorPct = $pct >= 95 ? '#16a34a' : ($pct >= 85 ? '#d97706' : '#dc2626');
                    $estBadge = match($l['estado']) {
                        'liberado'   => ['badge-success','Liberado'],
                        'cuarentena' => ['badge-warning','Cuarentena'],
                        'en_proceso' => ['badge-info','En proceso'],
                        'cerrado'    => ['badge-muted','Cerrado'],
                        default      => ['badge-muted', ucfirst($l['estado'])],
                    };
                ?>
                <tr>
                    <td>
                        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($l['codigo_lote']) ?>"
                            style="font-family:monospace;font-weight:700;
                                color:#2563eb;text-decoration:none;font-size:13px">
                            <?= htmlspecialchars($l['codigo_lote']) ?>
                        </a>
                    </td>
                    <td style="font-size:13px">
                        <?= htmlspecialchars($l['producto_nombre']) ?>
                    </td>
                    <td style="text-align:center;font-size:12px;color:#71717a">
                        <?= date('d/m/Y', strtotime($l['fecha_produccion'])) ?>
                    </td>
                    <td style="text-align:center;font-weight:700;
                                color:<?= $colorPct ?>">
                        <?= $pct > 0 ? number_format($pct,1).'%' : '—' ?>
                    </td>
                    <td style="text-align:center">
                        <span class="badge <?= $estBadge[0] ?>">
                            <?= $estBadge[1] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="bi bi-lightning-fill" style="color:#d97706;margin-right:6px"></i>
                Accesos rápidos
            </span>
        </div>
        <div class="card-body" style="padding:12px">
            <?php
            $accesos = [
                ['/m4/nuevo-lote',   'bi-plus-circle-fill',      '#2563eb', '#eff6ff', 'Nuevo lote',      'Abrir lote de producción'],
                ['/m1/nueva',        'bi-truck-front-fill',       '#16a34a', '#f0fdf4', 'Nueva recepción', 'Registrar ingreso MP'],
                ['/m2/nueva-sesion', 'bi-clipboard2-check-fill',  '#7c3aed', '#f5f3ff', 'Nueva sesión',    'Registro de proceso'],
                ['/m3/nuevo',        'bi-wrench-adjustable-circle-fill','#d97706','#fffbeb','Mantenimiento', 'Registrar mantenimiento'],
                ['/m5',              'bi-diagram-3-fill',         '#0891b2', '#ecfeff', 'Trazabilidad',    'Consultar lote'],
                ['/reportes',        'bi-file-earmark-bar-graph-fill','#71717a','#fafafa','Reportes',      'Generar reportes'],
            ];
            foreach ($accesos as [$url,$icon,$color,$bg,$titulo,$desc]):
            ?>
            <a href="<?= APP_URL . $url ?>"
                style="display:flex;align-items:center;gap:10px;padding:9px 10px;
                        border-radius:7px;text-decoration:none;
                        transition:background .12s;margin-bottom:4px"
                onmouseover="this.style.background='#f4f4f5'"
                onmouseout="this.style.background='transparent'">
                <div style="width:34px;height:34px;border-radius:7px;
                            background:<?= $bg ?>;display:flex;
                            align-items:center;justify-content:center;
                            flex-shrink:0">
                    <i class="bi <?= $icon ?>"
                        style="color:<?= $color ?>;font-size:15px"></i>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;color:#09090b">
                        <?= $titulo ?>
                    </div>
                    <div style="font-size:11px;color:#71717a"><?= $desc ?></div>
                </div>
            </a>
            <?php endforeach ?>
        </div>
    </div>

</div>