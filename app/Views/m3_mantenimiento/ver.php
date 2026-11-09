<?php
/** @var array $mant
 *  @var array $tipos
 *  @var array $estados
 *  @var bool  $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php
$tipo = $tipos[$mant['tipo']] ?? ['label'=>$mant['tipo'],'badge'=>'badge-muted'];
$est  = $estados[$mant['resultado']] ?? ['label'=>$mant['resultado'],'badge'=>'badge-muted'];
$durMin = (int)($mant['duracion_min'] ?? 0);
$durFmt = $durMin >= 60
    ? floor($durMin/60).'h '.($durMin%60).'min'
    : $durMin.'min';
?>

<div class="page-header">
    <div>
        <div class="page-title"><i class="bi bi-wrench-adjustable-circle-fill"></i> <?= htmlspecialchars($mant['equipo_nombre']) ?></div>
        <div class="page-sub">
            <?= htmlspecialchars($mant['equipo_codigo']) ?> ·
            <span class="badge <?= $tipo['badge'] ?>"><?= $tipo['label'] ?></span> ·
            <span class="badge <?= $est['badge'] ?>"><?= $est['label'] ?></span>
        </div>
    </div>
    <?php if ($canWrite): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m3/<?= $mant['id'] ?>/editar"
            class="btn btn-secondary"><i class="bi bi-pencil"></i> Editar</a>
        <form method="POST" action="<?= APP_URL ?>/m3/<?= $mant['id'] ?>/eliminar"
                style="display:inline">
            <input type="hidden" name="_token" value="<?= \App\Core\Controller::csrfToken() ?>">
            <button type="submit" class="btn btn-danger"
                    data-confirm="¿Eliminar este registro de mantenimiento? Esta acción no se puede deshacer.">
                <i class="bi bi-trash"></i> Eliminar
            </button>
        </form>
    </div>
    <?php endif ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="bi bi-gear-fill"></i> Equipo</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php foreach ([
                ['Nombre',   htmlspecialchars($mant['equipo_nombre'])],
                ['Código',   htmlspecialchars($mant['equipo_codigo'])],
                ['Línea',    htmlspecialchars($mant['linea_nombre'])],
                ['Marca',    htmlspecialchars($mant['marca'] ?? '—')],
                ['Modelo',   htmlspecialchars($mant['modelo'] ?? '—')],
                ['N° Serie', htmlspecialchars($mant['serie'] ?? '—')],
            ] as [$l,$v]): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;font-size:12px;width:40%"><?= $l ?></td>
                <td style="padding:7px 0;font-weight:500"><?= $v ?></td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title"><i class="bi bi-clipboard2-check-fill"></i> Registro</span></div>
        <div class="card-body">
            <table style="width:100%;font-size:13px;border-collapse:collapse">
            <?php foreach ([
                ['Tipo',          '<span class="badge '.$tipo['badge'].'">'.$tipo['label'].'</span>'],
                ['Resultado',     '<span class="badge '.$est['badge'].'">'.$est['label'].'</span>'],
                ['F. programada', $mant['fecha_programada'] ? date('d/m/Y', strtotime($mant['fecha_programada'])) : '—'],
                ['Inicio',        $mant['fecha_inicio'] ? date('d/m/Y H:i', strtotime($mant['fecha_inicio'])) : '—'],
                ['Fin',           $mant['fecha_fin'] ? date('d/m/Y H:i', strtotime($mant['fecha_fin'])) : '—'],
                ['Duración',      $durMin > 0 ? $durFmt : '—'],
                ['Ejecutado por', htmlspecialchars($mant['ejecutado_nombre'] ?? '—')],
                ['Supervisado por',htmlspecialchars($mant['supervisado_nombre'] ?? '—')],
                ['Costo est.',    $mant['costo_estimado'] ? 'Bs. '.number_format($mant['costo_estimado'],2) : '—'],
                ['Paro producción',$mant['paro_produccion'] ? '<i class="bi bi-exclamation-triangle-fill"></i> Sí — '.($mant['tiempo_paro_min'] ?? 0).' min' : 'No'],
                ['Lote afectado',  $mant['lote_afectado_codigo'] ?? ($mant['lote_afectado_id'] ? '#'.$mant['lote_afectado_id'] : '—')],
            ] as [$l,$v]): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:7px 0;color:#64748b;font-size:12px;width:45%"><?= $l ?></td>
                <td style="padding:7px 0;font-weight:500"><?= $v ?></td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>
</div>

<?php foreach ([
    ['<i class="bi bi-file-earmark-text-fill"></i> Descripción del trabajo', 'descripcion_trabajo'],
    ['<i class="bi bi-funnel"></i> Falla detectada',         'falla_detectada'],
    ['<i class="bi bi-bullseye"></i> Causa raíz',              'causa_raiz'],
    ['<i class="bi bi-check-circle-fill"></i> Acción correctiva',        'accion_correctiva'],
    ['<i class="bi bi-gear-fill"></i> Componentes cambiados',    'componentes_cambiados'],
    ['<i class="bi bi-chat-left-text-fill"></i> Observaciones',           'observaciones'],
] as [$titulo, $campo]): ?>
<?php if ($mant[$campo] ?? null): ?>
<div class="card" style="margin-top:14px">
    <div class="card-header"><span class="card-title"><?= $titulo ?></span></div>
    <div class="card-body">
        <p style="font-size:13px;line-height:1.6;white-space:pre-line;color:#374151">
            <?= htmlspecialchars($mant[$campo]) ?>
        </p>
    </div>
</div>
<?php endif ?>
<?php endforeach ?>

<div style="margin-top:14px">
    <a href="<?= APP_URL ?>/m3" class="btn btn-secondary">← Volver</a>
</div>