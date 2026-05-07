<?php /** @var array $stats @var array $alertas @var array $ultimosLotes @var array $usuario */ ?>

<!-- Alertas activas -->
<?php if (!empty($alertas)): ?>
<div style="margin-bottom:16px">
    <?php foreach ($alertas as $alerta): ?>
    <div class="flash flash-<?= $alerta['tipo'] ?>" style="margin-bottom:6px">
        ⚠️ <?= htmlspecialchars($alerta['mensaje']) ?>
        <a href="<?= APP_URL . $alerta['url'] ?>" style="margin-left:12px;font-weight:600">Ver →</a>
    </div>
    <?php endforeach ?>
</div>
<?php endif ?>

<!-- Bienvenida -->
<div class="page-header">
    <div>
        <div class="page-title">Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?> 👋</div>
        <div class="page-sub"><?= htmlspecialchars($usuario['rol_nombre'] ?? '') ?> — <?= date('l, d \d\e F \d\e Y') ?></div>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe">🏭</div>
        <div>
            <div class="stat-value"><?= $stats['productos'] ?></div>
            <div class="stat-label">Productos activos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">📦</div>
        <div>
            <div class="stat-value"><?= $stats['mp_stock'] ?></div>
            <div class="stat-label">Lotes de MP disponibles</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3">🏗️</div>
        <div>
            <div class="stat-value"><?= $stats['lotes_hoy'] ?></div>
            <div class="stat-label">Lotes producidos hoy</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff">⚙️</div>
        <div>
            <div class="stat-value"><?= $stats['equipos'] ?></div>
            <div class="stat-label">Equipos registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fce7f3">🔧</div>
        <div>
            <div class="stat-value" style="color:<?= $stats['calibraciones_vencidas'] > 0 ? '#dc2626' : '#15803d' ?>">
                <?= $stats['calibraciones_vencidas'] ?>
            </div>
            <div class="stat-label">Calibraciones vencidas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfeff">🧪</div>
        <div>
            <div class="stat-value"><?= $stats['insumos'] ?></div>
            <div class="stat-label">Insumos en catálogo</div>
        </div>
    </div>
</div>

<!-- Módulos de acceso rápido -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Acceso rápido</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
            <?php
            use App\Core\Auth;
            $accesos = [
                'm1_recepcion_mp'     => ['url'=>'/m1',       'icon'=>'🚛', 'label'=>'Recepción MP',      'color'=>'#0d9488'],
                'm4_seguimiento'      => ['url'=>'/m4',       'icon'=>'🏭', 'label'=>'Seguim. Producción', 'color'=>'#d97706'],
                'm2_registro_proceso' => ['url'=>'/m2',       'icon'=>'📋', 'label'=>'Registro Proceso',   'color'=>'#dc2626'],
                'm3_mantenimiento'    => ['url'=>'/m3',       'icon'=>'🔧', 'label'=>'Mantenimiento',      'color'=>'#7c3aed'],
                'm5_trazabilidad'     => ['url'=>'/m5',       'icon'=>'🔗', 'label'=>'Trazabilidad',       'color'=>'#be185d'],
                'm6_spc'              => ['url'=>'/m6',       'icon'=>'📊', 'label'=>'SPC',                'color'=>'#1d4ed8'],
                'm0_configuracion'    => ['url'=>'/m0/lineas','icon'=>'⚙️', 'label'=>'Configuración',      'color'=>'#0891b2'],
                'reportes'            => ['url'=>'/reportes', 'icon'=>'📄', 'label'=>'Reportes',           'color'=>'#525252'],
            ];
            foreach ($accesos as $modulo => $info):
                if (!Auth::canAccess($modulo)) continue;
            ?>
            <a href="<?= APP_URL . $info['url'] ?>"
                style="display:flex;flex-direction:column;align-items:center;gap:8px;
                    padding:16px 12px;border-radius:10px;text-decoration:none;
                    border:1px solid #e2e8f0;background:#fff;transition:all .15s;
                    color:<?= $info['color'] ?>"
                onmouseover="this.style.background='<?= $info['color'] ?>20';this.style.borderColor='<?= $info['color'] ?>'"
                onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                <span style="font-size:28px"><?= $info['icon'] ?></span>
                <span style="font-size:12px;font-weight:600;text-align:center;color:#374151">
                    <?= $info['label'] ?>
                </span>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- Últimos lotes -->
<?php if (!empty($ultimosLotes)): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">Últimos lotes de producción</span>
        <a href="<?= APP_URL ?>/m4" class="btn btn-sm btn-secondary">Ver todos</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código lote</th>
                    <th>Producto</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimosLotes as $lote): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($lote['codigo_lote']) ?></strong></td>
                    <td><?= htmlspecialchars($lote['producto_nombre']) ?></td>
                    <td><?= date('d/m/Y', strtotime($lote['fecha_produccion'])) ?></td>
                    <td>
                        <?php
                        $badgeMap = [
                            'en_proceso' => 'info',
                            'cerrado'    => 'muted',
                            'liberado'   => 'success',
                            'cuarentena' => 'warning',
                            'rechazado'  => 'danger',
                        ];
                        $badge = $badgeMap[$lote['estado']] ?? 'muted';
                        ?>
                        <span class="badge badge-<?= $badge ?>">
                            <?= ucfirst(str_replace('_', ' ', $lote['estado'])) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center" style="padding:40px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">🏭</div>
        <div style="font-size:15px;font-weight:600;margin-bottom:6px">No hay lotes registrados aún</div>
        <div style="font-size:13px">Comienza registrando el primer lote de producción</div>
        <?php if (Auth::canAccess('m4_seguimiento')): ?>
        <a href="<?= APP_URL ?>/m4/nuevo-lote" class="btn btn-primary mt-3">Crear primer lote</a>
        <?php endif ?>
    </div>
</div>
<?php endif ?>
