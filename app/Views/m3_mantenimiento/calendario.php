<?php
/** @var array $programados @var array $tipos @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="page-header">
    <div>
        <div class="page-title">📅 Calendario de Mantenimientos</div>
        <div class="page-sub">
            Mantenimientos programados y en proceso.
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m3/nuevo" class="btn btn-primary">
            + Nuevo mantenimiento
        </a>
    </div>
    <?php endif ?>
</div>

<?php if (empty($programados)): ?>
<div class="card">
    <div class="card-body text-center" style="padding:60px;color:#94a3b8">
        <div style="font-size:48px;margin-bottom:12px">📅</div>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px">
            No hay mantenimientos programados
        </div>
        <div style="font-size:13px;margin-bottom:16px">
            Todos los mantenimientos están completados o no hay registros.
        </div>
        <?php if ($canWrite): ?>
        <a href="<?= APP_URL ?>/m3/nuevo" class="btn btn-primary">
            Programar mantenimiento
        </a>
        <?php endif ?>
    </div>
</div>

<?php else: ?>

<!-- Agrupar por mes -->
<?php
$porMes = [];
foreach ($programados as $m) {
    $fecha = $m['fecha_programada'] ?? $m['fecha_ejecucion'] ?? date('Y-m-d');
    $mes   = date('Y-m', strtotime($fecha));
    $porMes[$mes][] = $m;
}
ksort($porMes);
?>

<?php foreach ($porMes as $mes => $items): ?>
<?php
$nombreMes = strftime('%B %Y', strtotime($mes.'-01'))
    ?: date('F Y', strtotime($mes.'-01'));
$dt        = new DateTime($mes.'-01');
$diasMes   = (int)$dt->format('t');
$primerDia = (int)date('N', strtotime($mes.'-01')); // 1=lun, 7=dom
?>

<div class="card" style="margin-bottom:20px">
    <div class="card-header"
            style="background:#1e3a5f;color:#fff">
        <span class="card-title" style="color:#fff;font-size:15px">
            📅 <?= ucfirst(date('F Y', strtotime($mes.'-01'))) ?>
        </span>
        <span class="badge"
                style="background:rgba(255,255,255,.2);color:#fff">
            <?= count($items) ?> mantenimiento(s)
        </span>
    </div>
    <div class="card-body">

        <!-- Grid del calendario -->
        <div style="display:grid;grid-template-columns:repeat(7,1fr);
                    gap:4px;margin-bottom:12px">

            <!-- Cabeceras días -->
            <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
            <div style="text-align:center;font-size:11px;font-weight:700;
                        color:#64748b;padding:4px">
                <?= $d ?>
            </div>
            <?php endforeach ?>

            <!-- Celdas vacías antes del primer día -->
            <?php for ($i = 1; $i < $primerDia; $i++): ?>
            <div></div>
            <?php endfor ?>

            <!-- Días del mes -->
            <?php for ($dia = 1; $dia <= $diasMes; $dia++):
                $fechaDia  = $mes.'-'.str_pad($dia, 2, '0', STR_PAD_LEFT);
                $hoy       = date('Y-m-d') === $fechaDia;
                $eventosDay= array_filter($items, function($m) use ($fechaDia) {
                    $f = $m['fecha_programada'] ?? $m['fecha_ejecucion'] ?? '';
                    return substr($f, 0, 10) === $fechaDia;
                });
            ?>
            <div style="min-height:60px;border:1px solid #e2e8f0;
                        border-radius:6px;padding:4px;
                        background:<?= $hoy ? '#dbeafe' : '#fff' ?>;
                        border-color:<?= $hoy ? '#2563eb' : '#e2e8f0' ?>">
                <div style="font-size:12px;font-weight:<?= $hoy ? '800':'400' ?>;
                            color:<?= $hoy ? '#1d4ed8' : '#374151' ?>;
                            margin-bottom:2px">
                    <?= $dia ?>
                </div>
                <?php foreach ($eventosDay as $ev):
                    $tipoEv = $tipos[$ev['tipo']] ?? ['badge'=>'badge-muted'];
                ?>
                <div class="badge <?= $tipoEv['badge'] ?>"
                        style="font-size:9px;width:100%;text-align:left;
                            margin-bottom:2px;white-space:nowrap;
                            overflow:hidden;text-overflow:ellipsis;
                            display:block;cursor:pointer"
                        title="<?= htmlspecialchars($ev['equipo_nombre']
                                    .' — '.$ev['descripcion']) ?>"
                        onclick="window.location='<?= APP_URL ?>/m3/<?= $ev['id'] ?>'">
                    <?= htmlspecialchars(mb_strimwidth(
                        $ev['equipo_codigo'], 0, 10, '...'
                    )) ?>
                </div>
                <?php endforeach ?>
            </div>
            <?php endfor ?>
        </div>

        <!-- Lista del mes -->
        <div style="border-top:1px solid #f1f5f9;padding-top:12px">
            <div style="font-size:11px;font-weight:700;color:#64748b;
                        text-transform:uppercase;margin-bottom:8px">
                Detalle del mes
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
            <?php foreach ($items as $m):
                $tipo    = $tipos[$m['tipo']] ?? ['label'=>$m['tipo'],'badge'=>'badge-muted'];
                $fecha   = $m['fecha_programada'] ?? $m['fecha_ejecucion'] ?? '—';
                $diasArr = $fecha !== '—'
                    ? (int)((strtotime($fecha) - strtotime(date('Y-m-d'))) / 86400)
                    : null;
            ?>
            <div style="display:flex;align-items:center;gap:10px;
                        padding:8px 12px;border-radius:8px;
                        background:#f8fafc;border:1px solid #e2e8f0">
                <div style="width:40px;text-align:center;flex-shrink:0">
                    <div style="font-size:16px;font-weight:800;
                                color:#1e3a5f">
                        <?= $fecha !== '—'
                            ? date('d', strtotime($fecha)) : '—' ?>
                    </div>
                    <div style="font-size:9px;color:#94a3b8;
                                text-transform:uppercase">
                        <?= $fecha !== '—'
                            ? date('M', strtotime($fecha)) : '' ?>
                    </div>
                </div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600">
                        <?= htmlspecialchars($m['equipo_nombre']) ?>
                        <span style="font-family:monospace;font-size:11px;
                                        color:#94a3b8;margin-left:4px">
                            <?= htmlspecialchars($m['equipo_codigo']) ?>
                        </span>
                    </div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px">
                        <?= htmlspecialchars(
                            mb_strimwidth($m['descripcion'] ?? '', 0, 80, '...')
                        ) ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;
                            align-items:flex-end;gap:4px;flex-shrink:0">
                    <span class="badge <?= $tipo['badge'] ?>">
                        <?= $tipo['label'] ?>
                    </span>
                    <?php if ($diasArr !== null): ?>
                    <span style="font-size:11px;
                                    color:<?= $diasArr < 0 ? '#dc2626'
                                    : ($diasArr === 0 ? '#d97706' : '#64748b') ?>">
                        <?= $diasArr < 0
                            ? 'Hace '.abs($diasArr).'d'
                            : ($diasArr === 0 ? 'Hoy' : 'En '.$diasArr.'d') ?>
                    </span>
                    <?php endif ?>
                </div>
                <a href="<?= APP_URL ?>/m3/<?= $m['id'] ?>"
                    class="btn btn-sm btn-secondary">Ver</a>
            </div>
            <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach ?>
<?php endif ?>