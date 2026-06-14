<?php
/** @var array $sesion @var array $limites @var array $estados
 *  @var bool $canWrite @var array $etapas
 *  @var array $parametros_por_etapa @var array $valores_por_etapa
 *  @var array $subgrupos_por_parametro @var array $limites_por_parametro
 *  @var array $parametros_atributo @var array $inspecciones_atributos */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $est = $estados[$sesion['estado']] ?? ['label'=>$sesion['estado'],'badge'=>'badge-muted']; ?>

<?php
// Apariencia por etapa (icono, color de acento, color de fondo del header)
$etapaConfig = [
    'amasado'            => ['icon'=>'bi-thermometer-half',  'color'=>'#d97706', 'bg'=>'#fef3c7'],
    'formado'            => ['icon'=>'bi-hand-index-thumb',  'color'=>'#2563eb', 'bg'=>'#dbeafe'],
    'fermentacion'       => ['icon'=>'bi-hourglass-split',   'color'=>'#7c3aed', 'bg'=>'#f5f3ff'],
    'horneado'           => ['icon'=>'bi-fire',              'color'=>'#f97316', 'bg'=>'#fff7ed'],
    'envasado'           => ['icon'=>'bi-box-seam',          'color'=>'#22c55e', 'bg'=>'#f0fdf4'],
    'producto_terminado' => ['icon'=>'bi-patch-check',       'color'=>'#dc2626', 'bg'=>'#fef2f2'],
];

// Mapa parametro_id => inspección de atributos ya registrada en esta sesión
$inspeccionPorParametro = [];
foreach ($inspecciones_atributos as $ia) {
    $inspeccionPorParametro[(int)$ia['parametro_id']] = $ia;
}

// Charts a inicializar (parametro_id => limites) — se llena durante el loop
$chartsAInicializar = [];
?>

<style>
    /* Evita que los inputs/selects de los formularios de registro se estiren
       para llenar columnas de grid anchas (p.ej. n=1 en cartas X-MR) */
    input.form-control[type="number"],
    input.form-control[type="text"],
    select.form-control {
        max-width: 200px;
    }
    input.form-control[type="time"] {
        max-width: 130px;
    }
</style>

<div class="page-header">
    <div>
        <div class="page-title">
            <i class="bi bi-clipboard2-check" style="color:#4f8ef7;margin-right:8px"></i>
            Sesión — Lote
            <span style="font-family:monospace"><?= htmlspecialchars($sesion['codigo_lote']) ?></span>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($sesion['producto_nombre']) ?> ·
            <?= date('d/m/Y', strtotime($sesion['fecha'])) ?> ·
            Turno <?= ucfirst($sesion['turno']) ?> ·
            <span class="badge <?= $est['badge'] ?>"><?= $est['label'] ?></span>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/imprimir"
            class="btn btn-secondary" target="_blank">
            <i class="bi bi-printer"></i> Imprimir
        </a>
        <a href="<?= APP_URL ?>/m2" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Info del producto -->
<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
            padding:10px 16px;margin-bottom:16px;font-size:12px;
            display:flex;gap:24px;flex-wrap:wrap">
    <span><i class="bi bi-box" style="color:#4f8ef7"></i> <strong>Peso nominal:</strong> <?= number_format($sesion['peso_nominal_g'],1) ?>g</span>
    <span style="color:#15803d"><i class="bi bi-arrow-up-circle"></i> <strong>LSE:</strong> <?= number_format($sesion['lse_g'],3) ?>g</span>
    <span style="color:#dc2626"><i class="bi bi-arrow-down-circle"></i> <strong>LIE:</strong> <?= number_format($sesion['lie_g'],3) ?>g</span>
</div>

<?php
$numSeccion = 0;
foreach ($etapas as $etapaKey => $etapaLabel):
    $params = $parametros_por_etapa[$etapaKey] ?? [];
    if (empty($params)) continue; // nada configurado en M0 para esta etapa

    $numSeccion++;
    $cfg = $etapaConfig[$etapaKey] ?? ['icon'=>'bi-gear','color'=>'#64748b','bg'=>'#f1f5f9'];

    $noSpc   = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 0));
    $spcNum  = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 1 && $p['tipo_dato'] === 'numerico'));
    $spcAttr = array_values(array_filter($params, fn($p) => (int)$p['es_variable_spc'] === 1 && in_array($p['tipo_dato'], ['seleccion','si_no'], true)));

    $valoresEtapa = $valores_por_etapa[$etapaKey] ?? [];

    // Pivote de valores simples por hora: [hora => [parametro_id => valor]]
    $porHora = [];
    foreach ($valoresEtapa as $v) {
        $porHora[$v['hora']][(int)$v['parametro_id']] = $v['valor'];
    }
    ksort($porHora);
?>
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:<?= $cfg['bg'] ?>;border-bottom:2px solid <?= $cfg['color'] ?>">
        <span class="card-title">
            <i class="bi <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>"></i>
            <?= $numSeccion ?>. <?= htmlspecialchars($etapaLabel) ?>
        </span>
        <span class="badge badge-muted"><?= count($params) ?> parámetro(s)</span>
    </div>

    <!-- ═══════════ Parámetros simples (no SPC) ═══════════ -->
    <?php if (!empty($noSpc)): ?>

        <?php if (!empty($porHora)): ?>
        <div class="table-wrap">
            <table class="data-table" style="font-size:12px">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <?php foreach ($noSpc as $p): ?>
                        <th style="text-align:center">
                            <?= htmlspecialchars($p['nombre']) ?>
                            <?php if ($p['unidad']): ?>
                            <span style="color:#94a3b8">(<?= htmlspecialchars($p['unidad']) ?>)</span>
                            <?php endif ?>
                        </th>
                        <?php endforeach ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($porHora as $hora => $valoresFila): ?>
                    <tr>
                        <td><?= htmlspecialchars($hora) ?></td>
                        <?php foreach ($noSpc as $p):
                            $val = $valoresFila[(int)$p['id']] ?? null;
                        ?>
                        <td style="text-align:center">
                            <?php if ($val === null): ?>
                                <span style="color:#94a3b8">—</span>
                            <?php elseif ($p['tipo_dato'] === 'si_no'): ?>
                                <?= $val === '1' ? '✓ Sí' : 'No' ?>
                            <?php else: ?>
                                <?= htmlspecialchars($val) ?>
                            <?php endif ?>
                        </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>

        <?php if ($canWrite): ?>
        <div style="padding:14px 16px;border-top:1px solid #f1f5f9">
            <form method="POST" action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/valores">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="form-row cols-3" style="margin-bottom:8px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Hora</label>
                    <input type="time" name="hora" class="form-control" value="<?= date('H:i') ?>">
                </div>
            </div>
            <div class="form-row cols-3">
                <?php foreach ($noSpc as $p): ?>
                <div class="form-group" style="margin-bottom:8px">
                    <label class="form-label">
                        <?= htmlspecialchars($p['nombre']) ?>
                        <?php if ($p['unidad']): ?>
                        <span style="color:#94a3b8">(<?= htmlspecialchars($p['unidad']) ?>)</span>
                        <?php endif ?>
                        <?php if ($p['obligatorio']): ?><span class="form-required">*</span><?php endif ?>
                    </label>

                    <?php if ($p['tipo_dato'] === 'numerico'): ?>
                        <input type="number" step="any" class="form-control"
                               name="valores[<?= $p['id'] ?>]"
                               <?= $p['obligatorio'] ? 'required' : '' ?>
                               <?php if ($p['valor_nominal'] !== null): ?>
                               placeholder="Ej: <?= rtrim(rtrim(number_format((float)$p['valor_nominal'],4,'.',''),'0'),'.') ?>"
                               <?php endif ?>>
                        <?php if ($p['valor_min'] !== null || $p['valor_max'] !== null): ?>
                        <div class="form-hint">
                            Rango: <?= $p['valor_min'] ?? '—' ?> – <?= $p['valor_max'] ?? '—' ?>
                        </div>
                        <?php endif ?>

                    <?php elseif ($p['tipo_dato'] === 'si_no'): ?>
                        <div style="padding-top:6px">
                            <input type="hidden" name="valores[<?= $p['id'] ?>]" value="0">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
                                <input type="checkbox" name="valores[<?= $p['id'] ?>]" value="1"
                                       style="width:16px;height:16px;accent-color:#4f8ef7">
                                Sí
                            </label>
                        </div>

                    <?php elseif ($p['tipo_dato'] === 'seleccion'):
                        $opciones = json_decode($p['opciones_json'] ?? '[]', true) ?: [];
                    ?>
                        <select name="valores[<?= $p['id'] ?>]" class="form-control"
                                <?= $p['obligatorio'] ? 'required' : '' ?>>
                            <option value="">—</option>
                            <?php foreach ($opciones as $op): ?>
                            <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                            <?php endforeach ?>
                        </select>

                    <?php else: // texto ?>
                        <input type="text" class="form-control"
                               name="valores[<?= $p['id'] ?>]"
                               <?= $p['obligatorio'] ? 'required' : '' ?>>
                    <?php endif ?>
                </div>
                <?php endforeach ?>
            </div>
            <div style="text-align:right;margin-top:4px">
                <button type="submit" class="btn" style="background:<?= $cfg['color'] ?>;color:#fff;border:none">
                    <i class="bi bi-floppy"></i> Guardar <?= htmlspecialchars($etapaLabel) ?>
                </button>
            </div>
            </form>
        </div>
        <?php endif ?>
    <?php endif ?>

    <!-- ═══════════ Parámetros numéricos SPC (X̄-R/S o X-MR) ═══════════ -->
    <?php if (!empty($spcNum)): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr">
    <?php $idxSpc = 0; foreach ($spcNum as $p):
        $pid       = (int)$p['id'];
        $n         = max(1, (int)$p['tamanio_subgrupo']);
        $subgrupos = $subgrupos_por_parametro[$pid] ?? [];
        $lim       = $limites_por_parametro[$pid] ?? ['ucl_xbar'=>null,'lcl_xbar'=>null,'cl_xbar'=>null];
        $senales   = count(array_filter($subgrupos, fn($s) => $s['fuera_de_control']));
        $chartsAInicializar[$pid] = $lim;
        $bordes = 'border-top:1px solid #f1f5f9;' . ($idxSpc % 2 === 1 ? 'border-left:1px solid #f1f5f9;' : '');
        $idxSpc++;
    ?>
    <div style="<?= $bordes ?>padding:16px;min-width:0">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
            <div style="font-size:13px;font-weight:700;color:#374151">
                <i class="bi bi-bar-chart-line" style="color:<?= $cfg['color'] ?>"></i>
                <?= htmlspecialchars($p['nombre']) ?>
                <span style="color:#94a3b8;font-weight:400">
                    (<?= htmlspecialchars($p['unidad']) ?> · <?= $n > 1 ? "n={$n}, carta X̄-" . ($n >= 6 ? 'S' : 'R') : 'n=1, carta X-MR' ?>)
                </span>
            </div>
            <div style="display:flex;gap:8px">
                <span class="badge badge-info"><?= count($subgrupos) ?> registro(s)</span>
                <?php if ($senales > 0): ?>
                <span class="badge badge-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= $senales ?> señal(es)
                </span>
                <?php endif ?>
            </div>
        </div>

        <canvas id="grafico-<?= $pid ?>" height="90"></canvas>

        <?php if (!empty($subgrupos)): ?>
        <div class="table-wrap" style="margin-top:10px">
            <table class="data-table" style="font-size:11px">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Lecturas</th>
                        <th style="text-align:center;color:#1d4ed8">X̄</th>
                        <?php if ($n > 1): ?><th style="text-align:center;color:#7c3aed">R</th><?php endif ?>
                        <th style="text-align:center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subgrupos as $sg): ?>
                <tr style="<?= $sg['fuera_de_control'] ? 'background:#fef2f2' : '' ?>">
                    <td><?= htmlspecialchars($sg['hora']) ?></td>
                    <td><?= implode(' / ', array_map(fn($v) => number_format((float)$v,2), $sg['valores'])) ?></td>
                    <td style="text-align:center;font-weight:700;color:#1d4ed8">
                        <?= $sg['promedio_xbar'] !== null ? number_format((float)$sg['promedio_xbar'],2) : '—' ?>
                    </td>
                    <?php if ($n > 1): ?>
                    <td style="text-align:center;font-weight:700;color:#7c3aed">
                        <?= $sg['rango_r'] !== null ? number_format((float)$sg['rango_r'],2) : '—' ?>
                    </td>
                    <?php endif ?>
                    <td style="text-align:center">
                        <?php if ($sg['fuera_de_control']): ?>
                        <span class="badge badge-danger" title="<?= htmlspecialchars($sg['regla_violada'] ?? '') ?>">
                            <i class="bi bi-exclamation-triangle"></i> Señal
                        </span>
                        <?php else: ?>
                        <span class="badge badge-success"><i class="bi bi-check"></i></span>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>

        <?php if ($canWrite): ?>
        <div style="margin-top:10px;padding:12px;background:#f8fafc;border-radius:8px">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:8px">
                <i class="bi bi-plus-circle" style="color:<?= $cfg['color'] ?>"></i>
                Registrar nuevo <?= $n > 1 ? "subgrupo (n={$n})" : 'dato' ?>
            </div>
            <form method="POST" action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/subgrupo"
                  class="form-subgrupo" data-parametro-id="<?= $pid ?>">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="parametro_id" value="<?= $pid ?>">
            <div style="display:grid;grid-template-columns:120px repeat(<?= $n ?>,1fr);gap:6px;margin-bottom:8px;align-items:end">
                <div>
                    <label class="form-label" style="font-size:11px">Hora</label>
                    <input type="time" name="hora" class="form-control" value="<?= date('H:i') ?>" style="font-size:11px;padding:4px">
                </div>
                <?php for ($i=1;$i<=$n;$i++): ?>
                <div>
                    <label class="form-label" style="font-size:11px">L<?= $i ?></label>
                    <input type="number" name="lecturas[]" step="any" class="form-control lectura-input"
                           data-pid="<?= $pid ?>" style="font-size:11px;padding:4px;text-align:center">
                </div>
                <?php endfor ?>
            </div>
            <?php if ($n > 1): ?>
            <div style="display:flex;gap:16px;align-items:center;margin-bottom:8px;padding:6px 10px;
                        background:#fff;border-radius:6px;font-size:12px;border:1px solid #e2e8f0">
                <span>X̄: <strong id="xbar-<?= $pid ?>" style="color:#1d4ed8">—</strong></span>
                <span>R: <strong id="rango-<?= $pid ?>" style="color:#7c3aed">—</strong></span>
            </div>
            <?php endif ?>
            <div style="text-align:right">
                <button type="submit" class="btn" style="background:<?= $cfg['color'] ?>;color:#fff;border:none">
                    <i class="bi bi-floppy"></i> Registrar
                </button>
            </div>
            </form>
        </div>
        <?php endif ?>
    </div>
    <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- ═══════════ Parámetros de atributo SPC (Carta p) ═══════════ -->
    <?php foreach ($spcAttr as $p):
        $pid  = (int)$p['id'];
        $insp = $inspeccionPorParametro[$pid] ?? null;
        $n    = max(1, (int)$p['tamanio_subgrupo']);
    ?>
    <div style="border-top:1px solid #f1f5f9;padding:16px">
        <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:8px">
            <i class="bi bi-clipboard2-check" style="color:<?= $cfg['color'] ?>"></i>
            <?= htmlspecialchars($p['nombre']) ?>
            <span style="color:#94a3b8;font-weight:400">(Carta p, n=<?= $n ?>)</span>
        </div>

        <?php if ($insp): ?>
        <div style="display:flex;gap:24px;align-items:center;padding:10px 14px;background:#f5f3ff;border-radius:8px;font-size:13px">
            <span>n inspeccionado: <strong><?= $insp['n_inspeccionado'] ?></strong></span>
            <span>No conformes: <strong style="color:<?= $insp['n_no_conformes']>0?'#dc2626':'#15803d' ?>"><?= $insp['n_no_conformes'] ?></strong></span>
            <span>Proporción p: <strong><?= number_format($insp['proporcion_p'],4) ?></strong> (<?= number_format($insp['proporcion_p']*100,2) ?>%)</span>
            <?php if (!empty($insp['observaciones'])): ?>
            <span style="color:#64748b">— <?= htmlspecialchars($insp['observaciones']) ?></span>
            <?php endif ?>
        </div>
        <?php endif ?>

        <?php if ($canWrite): ?>
        <form method="POST" action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/inspeccion-atributos"
              style="margin-top:<?= $insp ? '10px' : '0' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="parametro_id" value="<?= $pid ?>">
        <div class="form-row cols-3">
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">N inspeccionado</label>
                <input type="number" name="n_inspeccionado" class="form-control"
                       value="<?= $insp['n_inspeccionado'] ?? $n ?>" min="1" max="500">
            </div>
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">N° no conformes <span class="form-required">*</span></label>
                <input type="number" name="n_no_conformes" id="inp-no-conf-<?= $pid ?>"
                       class="form-control" value="<?= $insp['n_no_conformes'] ?? 0 ?>" min="0" required
                       oninput="calcularP(<?= $pid ?>)">
            </div>
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control"
                       value="<?= htmlspecialchars($insp['observaciones'] ?? '') ?>">
            </div>
        </div>
        <div id="preview-p-<?= $pid ?>" style="background:#f5f3ff;border:1px solid #ddd6fe;
             border-radius:6px;padding:8px 12px;margin-bottom:8px;font-size:13px;display:none">
            Proporción p = <strong id="val-p-<?= $pid ?>" style="color:#7c3aed">0.0000</strong>
            (<span id="val-p-pct-<?= $pid ?>">0.00</span>%)
        </div>
        <div style="text-align:right">
            <button type="submit" class="btn" style="background:<?= $cfg['color'] ?>;color:#fff;border:none">
                <i class="bi bi-floppy"></i> <?= $insp ? 'Actualizar' : 'Registrar' ?> inspección
            </button>
        </div>
        </form>
        <?php endif ?>
    </div>
    <?php endforeach ?>

</div>
<?php endforeach ?>

<!-- ═══════════════════════════════════════════════════════════
     ANÁLISIS Y LIBERACIÓN DE PRODUCTO TERMINADO
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header" style="background:#ede9fe;border-bottom:2px solid #7c3aed">
        <span class="card-title">
            <i class="bi bi-patch-check" style="color:#7c3aed"></i>
            <?= $numSeccion + 1 ?>. Análisis y Liberación de Producto Terminado
        </span>
        <?php if ($sesion['liberacion']): ?>
        <?php $decLib = $sesion['liberacion']['decision_final']; ?>
        <span class="badge <?= $decLib==='liberado' ? 'badge-success' : 'badge-warning' ?>">
            <?= $decLib==='liberado' ? 'LIBERADO' : 'CUARENTENA' ?>
        </span>
        <?php endif ?>
    </div>

    <?php if ($sesion['liberacion']): ?>
    <?php $lib = $sesion['liberacion']; ?>
    <div style="padding:16px">
        <div style="text-align:center;padding:12px;border-radius:8px;border:2px solid;
                    border-color:<?= $lib['decision_final']==='liberado'?'#86efac':'#fde68a' ?>;
                    background:<?= $lib['decision_final']==='liberado'?'#f0fdf4':'#fffbeb' ?>">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase">Decisión final</div>
            <div style="font-size:20px;font-weight:900;color:<?= $lib['decision_final']==='liberado'?'#15803d':'#d97706' ?>">
                <?= $lib['decision_final']==='liberado' ? 'PRODUCTO LIBERADO' : 'PRODUCTO EN CUARENTENA' ?>
            </div>
            <div style="font-size:11px;margin-top:4px;color:#64748b">
                Supervisor: <?= htmlspecialchars($lib['supervisor_cal_nombre'].' '.$lib['supervisor_cal_apellidos']) ?>
            </div>
            <?php if (!empty($lib['observaciones'])): ?>
            <div style="font-size:12px;margin-top:8px;color:#475569;font-style:italic">
                "<?= htmlspecialchars($lib['observaciones']) ?>"
            </div>
            <?php endif ?>
        </div>
    </div>

    <?php elseif ($canWrite): ?>
    <div style="padding:16px">
        <form method="POST" action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/liberacion">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div style="background:#fafafa;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:12px">
            <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;text-transform:uppercase">
                <i class="bi bi-lock"></i> Decisión final — Obligatorio SIREMU
            </div>
            <div style="display:flex;gap:16px;margin-bottom:10px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500">
                    <input type="radio" name="decision_final" value="liberado" required
                           style="width:16px;height:16px;accent-color:#15803d">
                    <span style="color:#15803d">LIBERAR producto</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500">
                    <input type="radio" name="decision_final" value="cuarentena"
                           style="width:16px;height:16px;accent-color:#d97706">
                    <span style="color:#d97706">Enviar a CUARENTENA</span>
                </label>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2"
                          placeholder="Observaciones de la liberación..."></textarea>
            </div>
        </div>

        <div style="text-align:right">
            <button type="submit" class="btn btn-primary"
                    data-confirm="¿Confirmar la decisión de liberación? Esta acción actualizará el estado del lote.">
                <i class="bi bi-lock"></i> Registrar liberación
            </button>
        </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card-body text-center text-muted" style="padding:20px">
        Liberación pendiente — solo Control de Calidad puede registrarla.
    </div>
    <?php endif ?>
</div>

<script src="<?= APP_URL ?>/assets/vendor/chart.min.js"></script>
<script>
const sesionId = <?= $sesion['id'] ?>;
const appUrl   = '<?= APP_URL ?>';

// ── Gráficos X̄ por parámetro SPC ──────────────────────────
const chartsConfig = <?= json_encode($chartsAInicializar) ?>;
const charts = {};

function renderGrafico(pid, datos, lim) {
    const canvas = document.getElementById('grafico-' + pid);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const labels = datos.puntos.map((_, i) => 'Sg ' + (i + 1));
    const vals   = datos.puntos.map(p => p.promedio_xbar);
    const colors = datos.puntos.map(p => p.fuera_de_control ? '#dc2626' : '#2563eb');

    const ucl = parseFloat(lim.ucl_xbar);
    const lcl = parseFloat(lim.lcl_xbar);
    const cl  = parseFloat(lim.cl_xbar);

    if (charts[pid]) charts[pid].destroy();
    charts[pid] = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [
            { label:'X̄', data:vals, borderColor:'#2563eb', backgroundColor:'transparent',
              pointBackgroundColor:colors, pointRadius:5, tension:0.3 },
            { label:'UCL', data:Array(vals.length).fill(ucl),
              borderColor:'#dc2626', borderDash:[5,3], pointRadius:0, fill:false },
            { label:'CL',  data:Array(vals.length).fill(cl),
              borderColor:'#15803d', borderDash:[3,3], pointRadius:0, fill:false },
            { label:'LCL', data:Array(vals.length).fill(lcl),
              borderColor:'#dc2626', borderDash:[5,3], pointRadius:0, fill:false },
        ]},
        options: { responsive:true, plugins:{ legend:{ position:'top' } },
                   scales:{ y:{ title:{ display:true, text:'X̄' } } } }
    });
}

Object.keys(chartsConfig).forEach(pid => {
    fetch(`${appUrl}/m2/sesion/${sesionId}/subgrupo/${pid}/datos`)
        .then(r => r.json())
        .then(d => { if (d.data) renderGrafico(pid, d.data, d.data.limites || chartsConfig[pid]); })
        .catch(() => {});
});

// ── Preview X̄/R en tiempo real para subgrupos n>1 ─────────
document.querySelectorAll('.lectura-input').forEach(inp => {
    inp.addEventListener('input', function () {
        calcularPreviewSubgrupo(this.dataset.pid);
    });
});

function calcularPreviewSubgrupo(pid) {
    const xbarEl  = document.getElementById('xbar-' + pid);
    const rangoEl = document.getElementById('rango-' + pid);
    if (!xbarEl) return;

    const vals = [];
    document.querySelectorAll('.lectura-input[data-pid="' + pid + '"]').forEach(inp => {
        if (inp.value !== '') vals.push(parseFloat(inp.value));
    });
    if (!vals.length) {
        xbarEl.textContent  = '—';
        rangoEl.textContent = '—';
        return;
    }
    const xbar  = vals.reduce((a,b)=>a+b,0) / vals.length;
    const rango = Math.max(...vals) - Math.min(...vals);
    xbarEl.textContent  = xbar.toFixed(2);
    rangoEl.textContent = rango.toFixed(2);
}

// ── Preview carta p ────────────────────────────────────────
function calcularP(pid) {
    const form = document.getElementById('inp-no-conf-' + pid).closest('form');
    const n    = parseInt(form.querySelector('input[name="n_inspeccionado"]')?.value || 1);
    const noC  = parseInt(document.getElementById('inp-no-conf-' + pid)?.value || 0);
    const p    = n > 0 ? noC / n : 0;
    const prev = document.getElementById('preview-p-' + pid);
    if (!prev) return;
    prev.style.display = 'block';
    document.getElementById('val-p-' + pid).textContent     = p.toFixed(4);
    document.getElementById('val-p-pct-' + pid).textContent = (p*100).toFixed(2);
}
</script>