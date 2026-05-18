<?php
/** @var array $sesion @var array $limites @var array $estados
 *  @var array $confOpc @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<?php
$est = $estados[$sesion['estado']]
        ?? ['label'=>$sesion['estado'],'badge'=>'badge-muted'];
?>

<div class="page-header">
    <div>
        <div class="page-title">
            📋 Sesión — Lote
            <span style="font-family:monospace">
                <?= htmlspecialchars($sesion['codigo_lote']) ?>
            </span>
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
            class="btn btn-secondary" target="_blank">🖨️ Imprimir</a>
        <a href="<?= APP_URL ?>/m2" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<!-- Info del producto para referencia -->
<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
            padding:10px 16px;margin-bottom:16px;font-size:12px;
            display:flex;gap:24px;flex-wrap:wrap">
    <span>📦 <strong>Peso nominal:</strong>
        <?= number_format($sesion['peso_nominal_g'],1) ?>g</span>
    <span style="color:#15803d">⬆️ <strong>LSE:</strong>
        <?= number_format($sesion['lse_g'],3) ?>g</span>
    <span style="color:#dc2626">⬇️ <strong>LIE:</strong>
        <?= number_format($sesion['lie_g'],3) ?>g</span>
    <span>📊 <strong>UCL X̄:</strong>
        <?= number_format($limites['ucl_xbar'],3) ?></span>
    <span>📊 <strong>LCL X̄:</strong>
        <?= number_format($limites['lcl_xbar'],3) ?></span>
    <span>📊 <strong>CL X̄:</strong>
        <?= number_format($limites['cl_xbar'],3) ?></span>
</div>

<!-- ═══════════════════════════════════════════════════════════
    SECCIÓN 1: AMASADO
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"
        style="background:#fef3c7;border-bottom:2px solid #d97706">
        <span class="card-title">🥣 1. Control de proceso — Amasado</span>
        <span class="badge badge-warning">
            <?= count($sesion['amasados']) ?> registros
        </span>
    </div>

    <?php if (!empty($sesion['amasados'])): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th style="text-align:center">T° masa (°C)</th>
                    <th style="text-align:center">pH masa</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['amasados'] as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['hora']) ?></td>
                <td style="text-align:center;font-weight:600">
                    <?= $a['temperatura_masa_c'] !== null
                        ? number_format($a['temperatura_masa_c'],1).'°C'
                        : '—' ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $a['ph_masa'] !== null
                        ? number_format($a['ph_masa'],2)
                        : '—' ?>
                </td>
                <td style="font-size:12px;color:#64748b">
                    <?= htmlspecialchars($a['observaciones_masa'] ?? '—') ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>

    <?php if ($canWrite): ?>
    <div style="padding:14px 16px;border-top:1px solid #f1f5f9">
        <form method="POST"
                action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/amasado"
                id="formAmasado">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="form-row cols-3">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Hora</label>
                <input type="time" name="hora" class="form-control"
                        value="<?= date('H:i') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">T° de la masa (°C)</label>
                <input type="number" name="temperatura_masa_c"
                        class="form-control" step="0.1"
                        placeholder="Ej: 26.5">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">pH de la masa</label>
                <input type="number" name="ph_masa" class="form-control"
                        step="0.01" placeholder="Ej: 5.80">
            </div>
        </div>
        <div style="margin-top:8px;display:flex;gap:8px;align-items:center">
            <input type="text" name="observaciones_masa"
                    class="form-control" placeholder="Observaciones..."
                    style="flex:1">
            <button type="submit" class="btn btn-warning">
                + Guardar amasado
            </button>
        </div>
        </form>
    </div>
    <?php endif ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
    SECCIÓN 2: PESOS (SPC) — Gráfico X̄ en tiempo real
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"
        style="background:#fee2e2;border-bottom:2px solid #dc2626">
        <span class="card-title">
            ⚖️ 2. Control de pesos — Gráfico X̄-R (SPC)
        </span>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge badge-danger">
                <?= count($sesion['pesos']) ?> subgrupos
            </span>
            <?php
            $senales = count(array_filter($sesion['pesos'],
                fn($p) => $p['fuera_de_control']));
            if ($senales > 0):
            ?>
            <span class="badge badge-danger">
                ⚠️ <?= $senales ?> señal(es)
            </span>
            <?php endif ?>
        </div>
    </div>

    <!-- Gráfico X̄ en tiempo real -->
    <div style="padding:16px">
        <canvas id="graficoXbar" height="120"></canvas>
    </div>

    <?php if (!empty($sesion['pesos'])): ?>
    <div class="table-wrap">
        <table class="data-table" style="font-size:11px">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th style="text-align:center">P1</th>
                    <th style="text-align:center">P2</th>
                    <th style="text-align:center">P3</th>
                    <th style="text-align:center">P4</th>
                    <th style="text-align:center">P5</th>
                    <th style="text-align:center">P6</th>
                    <th style="text-align:center">P7</th>
                    <th style="text-align:center">P8</th>
                    <th style="text-align:center">P9</th>
                    <th style="text-align:center">P10</th>
                    <th style="text-align:center;color:#1d4ed8">X̄</th>
                    <th style="text-align:center;color:#7c3aed">R</th>
                    <th style="text-align:center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['pesos'] as $p): ?>
            <tr style="<?= $p['fuera_de_control']
                ? 'background:#fef2f2' : '' ?>">
                <td><?= htmlspecialchars($p['hora']) ?></td>
                <?php for ($i = 1; $i <= 10; $i++):
                    $k = 'peso_'.str_pad($i,2,'0',STR_PAD_LEFT);
                    $v = $p[$k];
                    $fuera = $v !== null
                        && ((float)$v > (float)$sesion['lse_g']
                            || (float)$v < (float)$sesion['lie_g']);
                ?>
                <td style="text-align:center;
                            color:<?= $fuera ? '#dc2626' : 'inherit' ?>;
                            font-weight:<?= $fuera ? '700' : '400' ?>">
                    <?= $v !== null ? number_format((float)$v,1) : '—' ?>
                </td>
                <?php endfor ?>
                <td style="text-align:center;font-weight:700;color:#1d4ed8">
                    <?= $p['promedio_xbar'] !== null
                        ? number_format((float)$p['promedio_xbar'],2)
                        : '—' ?>
                </td>
                <td style="text-align:center;font-weight:700;color:#7c3aed">
                    <?= $p['rango_r'] !== null
                        ? number_format((float)$p['rango_r'],2)
                        : '—' ?>
                </td>
                <td style="text-align:center">
                    <?php if ($p['fuera_de_control']): ?>
                    <span class="badge badge-danger"
                            title="<?= htmlspecialchars($p['regla_violada'] ?? '') ?>">
                        ⚠️ Señal
                    </span>
                    <?php else: ?>
                    <span class="badge badge-success">✓</span>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>

    <?php if ($canWrite): ?>
    <div style="padding:14px 16px;border-top:1px solid #f1f5f9">
        <div style="font-size:12px;font-weight:700;color:#374151;
                    margin-bottom:10px">
            ➕ Registrar nuevo subgrupo (n=10)
        </div>
        <form method="POST"
                action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/pesos"
                id="formPesos">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="etapa" value="formado_boleado">
        <div style="display:grid;grid-template-columns:repeat(11,1fr);
                    gap:6px;margin-bottom:8px">
            <div style="font-size:11px;font-weight:600;color:#64748b;
                        text-align:center">Hora</div>
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <div style="font-size:11px;font-weight:600;color:#64748b;
                        text-align:center">
                P<?= $i ?>
            </div>
            <?php endfor ?>
            <input type="time" name="hora" class="form-control"
                    value="<?= date('H:i') ?>"
                    style="font-size:11px;padding:4px">
            <?php for ($i = 1; $i <= 10; $i++):
                $k = 'peso_'.str_pad($i,2,'0',STR_PAD_LEFT);
            ?>
            <input type="number" name="<?= $k ?>"
                    class="form-control peso-input"
                    step="0.1" min="0"
                    placeholder="0.0"
                    style="font-size:11px;padding:4px;text-align:center">
            <?php endfor ?>
        </div>
        <!-- Preview X̄ en tiempo real -->
        <div style="display:flex;gap:16px;align-items:center;
                    margin-bottom:8px;padding:8px;background:#f0f9ff;
                    border-radius:6px;font-size:13px">
            <span>X̄ estimado:
                <strong id="xbarPreview" style="color:#1d4ed8">—</strong>
            </span>
            <span>R estimado:
                <strong id="rangoPreview" style="color:#7c3aed">—</strong>
            </span>
            <span>Estado:
                <strong id="estadoPreview">—</strong>
            </span>
        </div>
        <div style="display:flex;gap:8px">
            <input type="text" name="observaciones"
                    class="form-control"
                    placeholder="Observaciones del subgrupo..."
                    style="flex:1">
            <button type="submit" class="btn btn-danger"
                    id="btnGuardarPesos">
                ⚖️ Registrar pesos
            </button>
        </div>
        </form>
    </div>
    <?php endif ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
    SECCIÓN 3: HORNEADO
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"
        style="background:#fff7ed;border-bottom:2px solid #f97316">
        <span class="card-title">🔥 3. Control de proceso — Horneado</span>
        <span class="badge badge-warning">
            <?= count($sesion['horneados']) ?> registros
        </span>
    </div>

    <?php if (!empty($sesion['horneados'])): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th style="text-align:center">Fermentación (min)</th>
                    <th style="text-align:center">T° horno (°C)</th>
                    <th style="text-align:center">Tiempo horno (min)</th>
                    <th style="text-align:center">Color</th>
                    <th style="text-align:center">Olor</th>
                    <th style="text-align:center">Textura</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['horneados'] as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['hora']) ?></td>
                <td style="text-align:center;font-weight:600">
                    <?= $h['tiempo_fermentacion_min'] ?? '—' ?> min
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $h['temperatura_horno_c'] !== null
                        ? number_format($h['temperatura_horno_c'],1).'°C'
                        : '—' ?>
                </td>
                <td style="text-align:center;font-weight:600">
                    <?= $h['tiempo_horneado_min'] ?? '—' ?> min
                </td>
                <?php foreach (['conf_color','conf_olor','conf_textura'] as $c): ?>
                <td style="text-align:center">
                    <?php if ($h[$c]):
                        $conf = $confOpc[$h[$c]] ?? null;
                        if ($conf):
                    ?>
                    <span class="badge <?= $conf['badge'] ?>">
                        <?= $conf['icon'] ?>
                    </span>
                    <?php endif; endif ?>
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
        <form method="POST"
                action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/horneado">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="form-row cols-4">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Hora</label>
                <input type="time" name="hora" class="form-control"
                        value="<?= date('H:i') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Fermentación (min)</label>
                <input type="number" name="tiempo_fermentacion_min"
                        class="form-control" min="0" placeholder="60">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">T° horno (°C)</label>
                <input type="number" name="temperatura_horno_c"
                        class="form-control" step="0.1" placeholder="200">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Tiempo horno (min)</label>
                <input type="number" name="tiempo_horneado_min"
                        class="form-control" min="0" placeholder="18">
            </div>
        </div>
        <div class="form-row cols-3" style="margin-top:8px">
            <?php foreach ([
                'conf_color'   => 'Color',
                'conf_olor'    => 'Olor',
                'conf_textura' => 'Textura',
            ] as $campo => $label): ?>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label"><?= $label ?></label>
                <select name="<?= $campo ?>" class="form-control">
                    <option value="">—</option>
                    <?php foreach ($confOpc as $val => $info): ?>
                    <option value="<?= $val ?>"><?= $info['label'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php endforeach ?>
        </div>
        <div style="margin-top:8px;text-align:right">
            <button type="submit" class="btn btn-warning">
                🔥 Guardar horneado
            </button>
        </div>
        </form>
    </div>
    <?php endif ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
    SECCIÓN 4: ENVASADO
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"
        style="background:#f0fdf4;border-bottom:2px solid #22c55e">
        <span class="card-title">📦 4. Control de envasado</span>
        <span class="badge badge-success">
            <?= count($sesion['envasados']) ?> registros
        </span>
    </div>

    <?php if (!empty($sesion['envasados'])): ?>
    <div class="table-wrap">
        <table class="data-table" style="font-size:12px">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th style="text-align:center">T° prod (°C)</th>
                    <th style="text-align:center">Pesos (g)</th>
                    <th style="text-align:center">X̄ envasado</th>
                    <th style="text-align:center">Codif H</th>
                    <th style="text-align:center">Codif V</th>
                    <th style="text-align:center">Sell H</th>
                    <th style="text-align:center">Sell V</th>
                    <th style="text-align:center">Total und</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sesion['envasados'] as $e): ?>
            <tr style="<?= $e['fuera_especificacion']
                ? 'background:#fef2f2' : '' ?>">
                <td><?= htmlspecialchars($e['hora']) ?></td>
                <td style="text-align:center">
                    <?= $e['temperatura_producto_c'] !== null
                        ? number_format($e['temperatura_producto_c'],1).'°C'
                        : '—' ?>
                </td>
                <td style="text-align:center;font-size:11px">
                    <?php
                    $ps = [];
                    foreach ([1,2,3,4] as $i) {
                        $k = "peso_unidad_{$i}";
                        if ($e[$k] !== null) $ps[] = number_format($e[$k],1);
                    }
                    echo implode(' / ', $ps) ?: '—';
                    ?>
                </td>
                <td style="text-align:center;font-weight:700;color:#1d4ed8">
                    <?= $e['promedio_peso_unidad'] !== null
                        ? number_format($e['promedio_peso_unidad'],2)
                        : '—' ?>
                </td>
                <?php foreach ([
                    'codif_horizontal','codif_vertical',
                    'sellado_horizontal','sellado_vertical'
                ] as $c):
                    $confV = $confOpc[$e[$c] ?? ''] ?? null;
                ?>
                <td style="text-align:center">
                    <?= $confV
                        ? "<span class=\"badge {$confV['badge']}\">{$confV['icon']}</span>"
                        : '—' ?>
                </td>
                <?php endforeach ?>
                <td style="text-align:center;font-weight:600">
                    <?= $e['total_unidades'] ?? '—' ?>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>

    <?php if ($canWrite): ?>
    <div style="padding:14px 16px;border-top:1px solid #f1f5f9">
        <form method="POST"
                action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/envasado">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="form-row cols-3">
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">Hora</label>
                <input type="time" name="hora" class="form-control"
                        value="<?= date('H:i') ?>">
            </div>
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">T° producto (°C)</label>
                <input type="number" name="temperatura_producto_c"
                        class="form-control" step="0.1" placeholder="25">
            </div>
            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label">Total unidades envasadas</label>
                <input type="number" name="total_unidades"
                        class="form-control" min="0">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);
                    gap:6px;margin-bottom:8px">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Peso unidad <?= $i ?> (g)</label>
                <input type="number" name="peso_unidad_<?= $i ?>"
                        class="form-control" step="0.1" placeholder="0.0">
            </div>
            <?php endfor ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);
                    gap:6px;margin-bottom:8px">
            <?php foreach ([
                'codif_horizontal' => 'Codif. Horiz.',
                'codif_vertical'   => 'Codif. Vert.',
                'sellado_horizontal'=> 'Sellado Horiz.',
                'sellado_vertical' => 'Sellado Vert.',
            ] as $campo => $label): ?>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label"><?= $label ?></label>
                <select name="<?= $campo ?>" class="form-control">
                    <option value="">—</option>
                    <?php foreach ($confOpc as $val => $info): ?>
                    <option value="<?= $val ?>"><?= $info['label'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php endforeach ?>
        </div>
        <div style="text-align:right">
            <button type="submit" class="btn btn-success">
                📦 Guardar envasado
            </button>
        </div>
        </form>
    </div>
    <?php endif ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
    SECCIÓN 5: LIBERACIÓN DE PRODUCTO TERMINADO
════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom:14px">
    <div class="card-header"
        style="background:#ede9fe;border-bottom:2px solid #7c3aed">
        <span class="card-title">
            ✅ 5. Análisis y Liberación de Producto Terminado
        </span>
        <?php if ($sesion['liberacion']): ?>
        <?php $decLib = $sesion['liberacion']['decision_final']; ?>
        <span class="badge <?= $decLib === 'liberado'
            ? 'badge-success' : 'badge-warning' ?>">
            <?= $decLib === 'liberado' ? '✅ LIBERADO' : '⚠️ CUARENTENA' ?>
        </span>
        <?php endif ?>
    </div>

    <?php if ($sesion['liberacion']): ?>
    <!-- Ya hay liberación registrada -->
    <?php $lib = $sesion['liberacion']; ?>
    <div style="padding:16px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:8px">
                    Verificación de codificado y envase
                </div>
                <?php
                $checkCampos = [
                    'codif_lote_legible'   => 'Codif. lote legible',
                    'codif_lote_correcto'  => 'Codif. lote correcto',
                    'codif_fvenc_legible'  => 'F.venc. legible',
                    'codif_fvenc_correcto' => 'F.venc. correcta',
                    'envase_primario'      => 'Envase primario',
                    'envase_secundario'    => 'Envase secundario',
                    'inocuo'              => 'Inocuidad',
                ];
                foreach ($checkCampos as $campo => $label):
                    $confV = $confOpc[$lib[$campo] ?? ''] ?? null;
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:4px 0;border-bottom:1px solid #f1f5f9;
                            font-size:12px">
                    <span><?= $label ?></span>
                    <?= $confV
                        ? "<span class=\"badge {$confV['badge']}\">{$confV['label']}</span>"
                        : '<span style="color:#94a3b8">—</span>' ?>
                </div>
                <?php endforeach ?>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:#64748b;
                            text-transform:uppercase;margin-bottom:8px">
                    Organolépticos PT
                </div>
                <?php
                $orgCampos = [
                    'conf_color'    => 'Color',
                    'conf_olor'     => 'Olor',
                    'conf_sabor'    => 'Sabor',
                    'conf_textura'  => 'Textura',
                    'conf_apariencia'=> 'Apariencia',
                ];
                foreach ($orgCampos as $campo => $label):
                    $confV = $confOpc[$lib[$campo] ?? ''] ?? null;
                ?>
                <div style="display:flex;justify-content:space-between;
                            padding:4px 0;border-bottom:1px solid #f1f5f9;
                            font-size:12px">
                    <span><?= $label ?></span>
                    <?= $confV
                        ? "<span class=\"badge {$confV['badge']}\">{$confV['label']}</span>"
                        : '<span style="color:#94a3b8">—</span>' ?>
                </div>
                <?php endforeach ?>
                <?php if ($lib['resultado_humedad_pct']
                            || $lib['resultado_ph']): ?>
                <div style="margin-top:8px;padding:8px;background:#f8fafc;
                            border-radius:6px;font-size:12px">
                    <?php if ($lib['resultado_humedad_pct']): ?>
                    <div>Humedad:
                        <strong>
                            <?= number_format($lib['resultado_humedad_pct'],2) ?>%
                        </strong>
                    </div>
                    <?php endif ?>
                    <?php if ($lib['resultado_ph']): ?>
                    <div>pH:
                        <strong>
                            <?= number_format($lib['resultado_ph'],2) ?>
                        </strong>
                    </div>
                    <?php endif ?>
                </div>
                <?php endif ?>
            </div>
        </div>
        <div style="margin-top:12px;text-align:center;padding:12px;
                    border-radius:8px;border:2px solid;
                    border-color:<?= $lib['decision_final']==='liberado'
                        ? '#86efac' : '#fde68a' ?>;
                    background:<?= $lib['decision_final']==='liberado'
                        ? '#f0fdf4' : '#fffbeb' ?>">
            <div style="font-size:11px;font-weight:700;color:#64748b;
                        text-transform:uppercase">Decisión final</div>
            <div style="font-size:20px;font-weight:900;
                        color:<?= $lib['decision_final']==='liberado'
                            ? '#15803d' : '#d97706' ?>">
                <?= $lib['decision_final'] === 'liberado'
                    ? '✅ PRODUCTO LIBERADO'
                    : '⚠️ PRODUCTO EN CUARENTENA' ?>
            </div>
            <div style="font-size:11px;margin-top:4px;color:#64748b">
                Supervisor: <?= htmlspecialchars($lib['supervisor_cal_nombre']
                    .' '.$lib['supervisor_cal_apellidos']) ?>
            </div>
        </div>
    </div>

    <?php elseif ($canWrite): ?>
    <!-- Formulario de liberación -->
    <div style="padding:16px">
        <form method="POST"
                action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/liberacion">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;
                    gap:16px;margin-bottom:16px">
            <div>
                <div style="font-size:12px;font-weight:700;color:#374151;
                            margin-bottom:8px">
                    Verificación de codificado y envase
                </div>
                <?php
                $libCampos = [
                    'codif_lote_legible'   => 'Codif. lote legible',
                    'codif_lote_correcto'  => 'Codif. lote correcto',
                    'codif_fvenc_legible'  => 'F.venc. legible',
                    'codif_fvenc_correcto' => 'F.venc. correcta',
                    'envase_primario'      => 'Envase primario',
                    'envase_secundario'    => 'Envase secundario',
                    'inocuo'              => 'Inocuidad',
                ];
                foreach ($libCampos as $campo => $label):
                ?>
                <div style="display:flex;justify-content:space-between;
                            align-items:center;padding:4px 0;
                            border-bottom:1px solid #f1f5f9;font-size:12px">
                    <span><?= $label ?></span>
                    <select name="<?= $campo ?>"
                            style="font-size:12px;padding:2px 6px;
                                    border:1px solid #e2e8f0;
                                    border-radius:4px">
                        <option value="">—</option>
                        <?php foreach ($confOpc as $val => $info): ?>
                        <option value="<?= $val ?>"><?= $info['label'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endforeach ?>
            </div>
            <div>
                <div style="font-size:12px;font-weight:700;color:#374151;
                            margin-bottom:8px">
                    Organolépticos y fisicoquímico PT
                </div>
                <?php
                $orgLibCampos = [
                    'conf_color'    => 'Color',
                    'conf_olor'     => 'Olor',
                    'conf_sabor'    => 'Sabor',
                    'conf_textura'  => 'Textura',
                    'conf_apariencia'=> 'Apariencia',
                ];
                foreach ($orgLibCampos as $campo => $label):
                ?>
                <div style="display:flex;justify-content:space-between;
                            align-items:center;padding:4px 0;
                            border-bottom:1px solid #f1f5f9;font-size:12px">
                    <span><?= $label ?></span>
                    <select name="<?= $campo ?>"
                            style="font-size:12px;padding:2px 6px;
                                    border:1px solid #e2e8f0;
                                    border-radius:4px">
                        <option value="">—</option>
                        <?php foreach ($confOpc as $val => $info): ?>
                        <option value="<?= $val ?>"><?= $info['label'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endforeach ?>
                <div class="form-row cols-2" style="margin-top:8px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Humedad PT (%)</label>
                        <input type="number" name="resultado_humedad_pct"
                                class="form-control" step="0.01">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">pH PT</label>
                        <input type="number" name="resultado_ph"
                                class="form-control" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        <div style="background:#fafafa;border:1px solid #e2e8f0;
                    border-radius:8px;padding:14px;margin-bottom:12px">
            <div style="font-size:12px;font-weight:700;color:#374151;
                        margin-bottom:10px;text-transform:uppercase">
                🔏 Decisión final — Obligatorio SIREMU
            </div>
            <div style="display:flex;gap:16px;margin-bottom:10px">
                <label style="display:flex;align-items:center;gap:8px;
                            cursor:pointer;font-size:13px;font-weight:500">
                    <input type="radio" name="decision_final"
                        value="liberado" required
                        style="width:16px;height:16px;accent-color:#15803d">
                    <span style="color:#15803d">✅ LIBERAR producto</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px;
                            cursor:pointer;font-size:13px;font-weight:500">
                    <input type="radio" name="decision_final"
                        value="cuarentena"
                        style="width:16px;height:16px;accent-color:#d97706">
                    <span style="color:#d97706">⚠️ Enviar a CUARENTENA</span>
                </label>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control"
                        rows="2" placeholder="Observaciones de la liberación...">
                </textarea>
            </div>
        </div>

        <div style="text-align:right">
            <button type="submit" class="btn btn-primary"
                    data-confirm="¿Confirmar la decisión de liberación? Esta acción actualizará el estado del lote.">
                🔏 Registrar liberación
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
const sesionId  = <?= $sesion['id'] ?>;
const appUrl    = '<?= APP_URL ?>';
const uclXbar   = <?= (float)($limites['ucl_xbar'] ?? 0) ?>;
const lclXbar   = <?= (float)($limites['lcl_xbar'] ?? 0) ?>;
const clXbar    = <?= (float)($limites['cl_xbar'] ?? 0) ?>;

// ── Gráfico X̄ ────────────────────────────────────────────────────────────────
const ctx = document.getElementById('graficoXbar').getContext('2d');
let grafico = null;

function renderGrafico(datos) {
    const labels = datos.puntos.map((_, i) => 'Sg '+(i+1));
    const vals   = datos.puntos.map(p => p.promedio_xbar);
    const colors = datos.puntos.map(p =>
        p.fuera_de_control ? '#dc2626' : '#2563eb'
    );

    if (grafico) grafico.destroy();

    grafico = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'X̄', data: vals,
                    borderColor: '#2563eb', backgroundColor: 'transparent',
                    pointBackgroundColor: colors,
                    pointRadius: 5, tension: 0.3,
                },
                {
                    label: 'UCL', data: Array(vals.length).fill(uclXbar),
                    borderColor: '#dc2626', borderDash: [5,3],
                    pointRadius: 0, fill: false,
                },
                {
                    label: 'CL', data: Array(vals.length).fill(clXbar),
                    borderColor: '#15803d', borderDash: [3,3],
                    pointRadius: 0, fill: false,
                },
                {
                    label: 'LCL', data: Array(vals.length).fill(lclXbar),
                    borderColor: '#dc2626', borderDash: [5,3],
                    pointRadius: 0, fill: false,
                },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { title: { display: true, text: 'X̄ (g)' } } }
        }
    });
}

// Cargar gráfico al inicio
fetch(`${appUrl}/m2/sesion/${sesionId}/pesos/datos`)
    .then(r => r.json())
    .then(d => { if (d.data) renderGrafico(d.data); })
    .catch(() => {});

// ── Preview X̄ en tiempo real al llenar pesos ─────────────────────────────────
document.querySelectorAll('.peso-input').forEach(inp => {
    inp.addEventListener('input', calcularPreview);
});

function calcularPreview() {
    const vals = [];
    document.querySelectorAll('.peso-input').forEach(inp => {
        if (inp.value !== '') vals.push(parseFloat(inp.value));
    });

    if (vals.length === 0) {
        document.getElementById('xbarPreview').textContent = '—';
        document.getElementById('rangoPreview').textContent = '—';
        document.getElementById('estadoPreview').textContent = '—';
        return;
    }

    const xbar  = vals.reduce((a,b) => a+b, 0) / vals.length;
    const rango = Math.max(...vals) - Math.min(...vals);
    const fuera = xbar > uclXbar || xbar < lclXbar;

    document.getElementById('xbarPreview').textContent =
        xbar.toFixed(2) + ' g';
    document.getElementById('xbarPreview').style.color =
        fuera ? '#dc2626' : '#15803d';

    document.getElementById('rangoPreview').textContent =
        rango.toFixed(2) + ' g';

    document.getElementById('estadoPreview').textContent =
        fuera ? '⚠️ FUERA DE CONTROL' : '✓ En control';
    document.getElementById('estadoPreview').style.color =
        fuera ? '#dc2626' : '#15803d';
}

// ── Envío del formulario de pesos via AJAX ────────────────────────────────────
document.getElementById('formPesos')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn  = document.getElementById('btnGuardarPesos');
    const form = this;
    const fd   = new FormData(form);

    btn.disabled = true;
    btn.textContent = '⏳ Guardando...';

    fetch(form.action, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Mostrar alerta si hay señal SPC
                if (data.data?.fuera_de_control) {
                    alert('⚠️ SEÑAL SPC DETECTADA:\n' + data.message);
                }
                // Recargar la página para mostrar el nuevo subgrupo
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.textContent = '⚖️ Registrar pesos';
            }
        })
        .catch(() => {
            alert('Error de conexión.');
            btn.disabled = false;
            btn.textContent = '⚖️ Registrar pesos';
        });
});
</script>