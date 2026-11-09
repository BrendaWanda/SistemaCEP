<?php
/** @var array $datos @var string $codigo */
?>

<div class="page-header">
    <div>
        <div class="page-title" style="font-family:monospace">
            🔗 Lote <?= htmlspecialchars($codigo) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($datos['producto_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($datos['fecha_produccion'])) ?> ·
            <?= htmlspecialchars($datos['linea_nombre']) ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m5/lote/<?= urlencode($codigo) ?>/pdf"
            class="btn btn-secondary" target="_blank">
            🖨️ Exportar PDF
        </a>
        <a href="<?= APP_URL ?>/m5" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<!-- Estado del lote -->
<?php
$estMap = [
    'liberado'   => ['bg'=>'#f0fdf4','border'=>'#86efac','color'=>'#15803d','label'=>'✅ PRODUCTO LIBERADO'],
    'cuarentena' => ['bg'=>'#fffbeb','border'=>'#fde68a','color'=>'#d97706','label'=>'⚠️ EN CUARENTENA'],
    'rechazado'  => ['bg'=>'#fef2f2','border'=>'#fca5a5','color'=>'#dc2626','label'=>'❌ RECHAZADO'],
    'en_proceso' => ['bg'=>'#eff6ff','border'=>'#93c5fd','color'=>'#1d4ed8','label'=>'⚙️ EN PROCESO'],
    'cerrado'    => ['bg'=>'#f8fafc','border'=>'#e2e8f0','color'=>'#475569','label'=>'🔒 CERRADO'],
];
$est = $estMap[$datos['estado']] ?? ['bg'=>'#f8fafc','border'=>'#e2e8f0','color'=>'#475569','label'=>ucfirst($datos['estado'])];
?>
<div style="background:<?= $est['bg'] ?>;border:2px solid <?= $est['border'] ?>;
            border-radius:10px;padding:14px 20px;margin-bottom:20px;
            display:flex;justify-content:space-between;align-items:center">
    <div style="font-size:18px;font-weight:900;color:<?= $est['color'] ?>">
        <?= $est['label'] ?>
    </div>
    <div style="text-align:right;font-size:12px;color:#64748b">
        <div>Lote contrato: <strong><?= htmlspecialchars($datos['nivel'] ?? '—') ?></strong></div>
        <div>Receta: <strong><?= htmlspecialchars($datos['receta_nombre'].' v'.$datos['receta_version']) ?></strong></div>
        <div>Supervisor: <strong><?= htmlspecialchars($datos['supervisor_nombre'].' '.$datos['supervisor_apellidos']) ?></strong></div>
    </div>
</div>

<!-- NIVEL 1: Datos del lote -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#1e3a5f;color:#fff">
        <span class="card-title" style="color:#fff">
            📦 Nivel 1 — Producto Terminado
        </span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
            <?php
            $datos1 = [
                ['Código lote',    '<span style="font-family:monospace;font-size:15px;font-weight:800">'
                                    .htmlspecialchars($datos['codigo_lote']).'</span>'],
                ['Producto',       htmlspecialchars($datos['producto_nombre'])],
                ['Código producto',htmlspecialchars($datos['producto_codigo'])],
                ['Fecha producción',date('d/m/Y', strtotime($datos['fecha_produccion']))],
                ['Turno',          ucfirst($datos['turno'])],
                ['N° recetas',     number_format((float)$datos['numero_recetas'],1)],
                ['Rend. teórico',  number_format($datos['rendimiento_teorico_total'] ?? 0).' und'],
                ['Rend. real',     number_format($datos['rendimiento_real_total'] ?? 0).' und '
                                    .'('.number_format($datos['porcentaje_rendimiento'] ?? 0,1).'%)'],
                ['F. elaboración', date('d/m/Y', strtotime($datos['fecha_elaboracion']))],
                ['F. vencimiento', date('d/m/Y', strtotime($datos['fecha_vencimiento']))],
                ['Peso nominal',   number_format($datos['peso_nominal_g'],1).'g'],
                ['LSE / LIE',      number_format($datos['lse_g'],3).'g / '.number_format($datos['lie_g'],3).'g'],
            ];
            foreach ($datos1 as [$l,$v]):
            ?>
            <div>
                <div style="font-size:10px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:2px">
                    <?= $l ?>
                </div>
                <div style="font-size:13px;font-weight:500"><?= $v ?></div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- NIVEL 2: MP consumida -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#0891b2;color:#fff">
        <span class="card-title" style="color:#fff">
            🚛 Nivel 2 — Materia Prima consumida
            <span style="font-size:12px;opacity:.8;margin-left:8px">
                (Trazabilidad hacia proveedores)
            </span>
        </span>
        <span style="color:#fff;font-size:12px">
            <?= count($datos['mp_consumida']) ?> insumos
        </span>
    </div>
    <?php if (empty($datos['mp_consumida'])): ?>
    <div class="card-body text-muted text-center" style="padding:20px">
        No hay consumo de MP registrado para este lote.
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table" style="font-size:12px">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Tipo</th>
                    <th style="text-align:right">Cantidad usada</th>
                    <th>Lote proveedor</th>
                    <th style="text-align:center">Vencimiento stock</th>
                    <th>Proveedor</th>
                    <th>Procedencia</th>
                    <th>Recepción</th>
                    <th style="text-align:center">Decisión recep.</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($datos['mp_consumida'] as $mp):
                $decMap = [
                    'aprobado'   => ['badge-success','✓'],
                    'rechazado'  => ['badge-danger','✕'],
                    'observado'  => ['badge-warning','!'],
                    'cuarentena' => ['badge-purple','?'],
                ];
                $dec = $decMap[$mp['decision_recepcion']] ?? ['badge-muted','—'];
                $tipoBadge = match($mp['insumo_tipo']) {
                    'materia_prima'    => 'badge-info',
                    'material_empaque' => 'badge-purple',
                    default            => 'badge-muted',
                };
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($mp['insumo_nombre']) ?></strong>
                    <div style="font-size:10px;color:#94a3b8">
                        <?= htmlspecialchars($mp['insumo_codigo']) ?>
                    </div>
                </td>
                <td>
                    <span class="badge <?= $tipoBadge ?>" style="font-size:10px">
                        <?= htmlspecialchars($mp['insumo_tipo']) ?>
                    </span>
                </td>
                <td style="text-align:right;font-weight:700">
                    <?= number_format((float)$mp['cantidad_usada'],3) ?>
                    <?= htmlspecialchars($mp['unidad_medida']) ?>
                </td>
                <td>
                    <code style="background:#f1f5f9;padding:1px 5px;
                                    border-radius:3px;font-size:10px">
                        <?= htmlspecialchars($mp['lote_proveedor']) ?>
                    </code>
                </td>
                <td style="text-align:center">
                    <?= date('d/m/Y', strtotime($mp['fecha_vencimiento'])) ?>
                </td>
                <td><strong><?= htmlspecialchars($mp['proveedor_nombre']) ?></strong></td>
                <td style="color:#64748b">
                    <?= htmlspecialchars($mp['proveedor_procedencia'] ?? '—') ?>
                </td>
                <td>
                    <code style="font-size:10px">
                        <?= htmlspecialchars($mp['codigo_recepcion']) ?>
                    </code>
                    <div style="font-size:10px;color:#94a3b8">
                        <?= date('d/m/Y', strtotime($mp['fecha_recepcion'])) ?>
                    </div>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $dec[0] ?>"><?= $dec[1] ?></span>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- NIVEL 3: Proceso -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#d97706;color:#fff">
        <span class="card-title" style="color:#fff">
            ⚙️ Nivel 3 — Registro de proceso
        </span>
        <span style="color:#fff;font-size:12px">
            <?= count($datos['sesiones']) ?> sesión(es)
        </span>
    </div>
    <?php if (empty($datos['sesiones'])): ?>
    <div class="card-body text-muted text-center" style="padding:20px">
        No hay sesiones de registro de proceso para este lote.
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align:center">Fecha</th>
                    <th style="text-align:center">Turno</th>
                    <th>Supervisor</th>
                    <th style="text-align:center">Subgrupos pesos</th>
                    <th style="text-align:center">Señales SPC</th>
                    <th style="text-align:center">Horneado</th>
                    <th style="text-align:center">Envasado</th>
                    <th style="text-align:center">Estado</th>
                    <th>Ver</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($datos['sesiones'] as $s): ?>
            <tr>
                <td style="text-align:center;font-size:12px">
                    <?= date('d/m/Y', strtotime($s['fecha'])) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-muted">
                        <?= ucfirst($s['turno']) ?>
                    </span>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($s['supervisor_nombre']) ?>
                </td>
                <td style="text-align:center">
                    <span class="badge badge-info">
                        <?= (int)$s['total_pesos'] ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <?php if ($s['senales_spc'] > 0): ?>
                    <span class="badge badge-danger">
                        ⚠️ <?= $s['senales_spc'] ?>
                    </span>
                    <?php else: ?>
                    <span class="badge badge-success">✓ OK</span>
                    <?php endif ?>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $s['total_horneado'] > 0 ? 'badge-success' : 'badge-muted' ?>">
                        <?= $s['total_horneado'] > 0 ? '✓' : '—' ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span class="badge <?= $s['total_envasado'] > 0 ? 'badge-success' : 'badge-muted' ?>">
                        <?= $s['total_envasado'] > 0 ? '✓' : '—' ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <?php
                    $estSes = match($s['estado']) {
                        'completo' => ['badge-success','Completo'],
                        'revisado' => ['badge-purple','Revisado'],
                        default    => ['badge-info','En proceso'],
                    };
                    ?>
                    <span class="badge <?= $estSes[0] ?>"><?= $estSes[1] ?></span>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/m2/sesion/<?= $s['id'] ?>"
                        class="btn btn-sm btn-secondary">Ver</a>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>

<!-- NIVEL 4: Liberación PT -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#7c3aed;color:#fff">
        <span class="card-title" style="color:#fff">
            ✅ Nivel 4 — Liberación de Producto Terminado
        </span>
    </div>
    <?php if (!$datos['liberacion']): ?>
    <div class="card-body text-muted text-center" style="padding:20px">
        Sin registro de liberación para este lote.
    </div>
    <?php else: ?>
    <?php $lib = $datos['liberacion']; ?>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:8px">
                    Decisión
                </div>
                <div style="padding:12px;border-radius:8px;text-align:center;
                            background:<?= $lib['decision_final']==='liberado'
                                ? '#f0fdf4' : '#fffbeb' ?>;
                            border:1px solid <?= $lib['decision_final']==='liberado'
                                ? '#86efac' : '#fde68a' ?>">
                    <div style="font-size:16px;font-weight:900;
                                color:<?= $lib['decision_final']==='liberado'
                                    ? '#15803d' : '#d97706' ?>">
                        <?= $lib['decision_final'] === 'liberado'
                            ? '✅ PRODUCTO LIBERADO'
                            : '⚠️ EN CUARENTENA' ?>
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:6px">
                        Supervisor calidad:
                        <strong>
                            <?= htmlspecialchars($lib['supervisor_cal_nombre']
                                .' '.($lib['supervisor_cal_apellidos'] ?? '')) ?>
                        </strong>
                    </div>
                    <?php if ($lib['resultado_humedad_pct']
                                || $lib['resultado_ph']): ?>
                    <div style="margin-top:6px;font-size:12px">
                        <?php if ($lib['resultado_humedad_pct']): ?>
                        Humedad PT:
                        <strong><?= number_format($lib['resultado_humedad_pct'],2) ?>%</strong>
                        <?php endif ?>
                        <?php if ($lib['resultado_ph']): ?>
                        · pH PT:
                        <strong><?= number_format($lib['resultado_ph'],2) ?></strong>
                        <?php endif ?>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:8px">
                    Verificaciones
                </div>
                <?php
                $verifs = [
                    'Codif. lote legible'  => $lib['codif_lote_legible'],
                    'Codif. lote correcto' => $lib['codif_lote_correcto'],
                    'F. venc. legible'     => $lib['codif_fvenc_legible'],
                    'Envase primario'      => $lib['envase_primario'],
                    'Inocuidad'           => $lib['inocuo'],
                    'Color PT'             => $lib['conf_color'],
                    'Sabor PT'             => $lib['conf_sabor'],
                ];
                foreach ($verifs as $label => $val):
                    $confMap = [
                        'conforme'    => ['badge-success','✓ Conforme'],
                        'no_conforme' => ['badge-danger','✕ No conforme'],
                        'na'          => ['badge-muted','N/A'],
                    ];
                    $conf = $confMap[$val ?? ''] ?? null;
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:4px 0;border-bottom:1px solid #f1f5f9;
                            font-size:12px">
                    <span style="color:#64748b"><?= $label ?></span>
                    <?= $conf
                        ? "<span class=\"badge {$conf[0]}\" style=\"font-size:10px\">{$conf[1]}</span>"
                        : '<span style="color:#94a3b8">—</span>' ?>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>

<!-- NIVEL 5: Timeline de eventos -->
<?php if (!empty($datos['eventos'])): ?>
<div class="card" style="margin-bottom:14px">
    <div class="card-header">
        <span class="card-title">📅 Línea de tiempo — Eventos del lote</span>
    </div>
    <div class="card-body">
        <div style="position:relative;padding-left:20px">
            <div style="position:absolute;left:8px;top:0;bottom:0;
                        width:2px;background:#e2e8f0"></div>
            <?php foreach ($datos['eventos'] as $ev):
                $evIcon = match($ev['tipo_evento']) {
                    'apertura_lote'   => ['🏭','#dbeafe'],
                    'recepcion_mp'    => ['📦','#dcfce7'],
                    'registro_proceso'=> ['⚙️','#fef9c3'],
                    'liberacion'      => ['✅','#dcfce7'],
                    'cuarentena'      => ['⚠️','#fff7ed'],
                    'cierre_lote'     => ['🔒','#f1f5f9'],
                    default           => ['📌','#f1f5f9'],
                };
            ?>
            <div style="display:flex;gap:12px;margin-bottom:12px;
                        align-items:flex-start">
                <div style="width:20px;height:20px;border-radius:50%;
                            background:<?= $evIcon[1] ?>;
                            display:flex;align-items:center;
                            justify-content:center;font-size:12px;
                            flex-shrink:0;position:relative;z-index:1">
                    <?= $evIcon[0] ?>
                </div>
                <div style="flex:1;padding:8px 12px;background:#f8fafc;
                            border-radius:8px;border:1px solid #e2e8f0">
                    <div style="display:flex;justify-content:space-between;
                                margin-bottom:3px">
                        <span style="font-size:11px;font-weight:700;
                                        color:#374151;text-transform:uppercase">
                            <?= str_replace('_',' ', $ev['tipo_evento']) ?>
                        </span>
                        <span style="font-size:10px;color:#94a3b8">
                            <?= date('d/m/Y H:i', strtotime($ev['creado_en'])) ?>
                        </span>
                    </div>
                    <div style="font-size:12px;color:#64748b">
                        <?= htmlspecialchars($ev['descripcion']) ?>
                    </div>
                    <?php if ($ev['usuario_nombre']): ?>
                    <div style="font-size:10px;color:#94a3b8;margin-top:2px">
                        Por: <?= htmlspecialchars($ev['usuario_nombre']) ?>
                    </div>
                    <?php endif ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>