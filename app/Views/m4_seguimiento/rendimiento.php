<?php
/** @var array $lote @var bool $canWrite */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div class="page-title">
        📊 Rendimiento — Lote <?= htmlspecialchars($lote['codigo_lote']) ?>
    </div>
    <div class="page-sub">
        <?= htmlspecialchars($lote['producto_nombre']) ?> ·
        Teórico: <strong><?= number_format($lote['rendimiento_teorico_total'] ?? 0) ?> und</strong>
    </div>
</div>

<div class="card" style="max-width:650px">
    <div class="card-header">
        <span class="card-title">Registro de rendimiento y mermas</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>/rendimiento">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <!-- Rendimiento -->
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;
                    padding:14px;margin-bottom:16px">
            <div style="font-size:12px;font-weight:700;color:#15803d;
                        margin-bottom:10px;text-transform:uppercase">
                📊 Unidades producidas
            </div>
            <div class="form-row cols-2">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Rendimiento teórico (und)</label>
                    <input type="number" name="rendimiento_teorico_total"
                            class="form-control"
                            value="<?= $lote['rendimiento_teorico_total'] ?? 0 ?>"
                            min="0" readonly
                            style="background:#e8f5e9;font-weight:700">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">
                        Rendimiento real (und) <span class="form-required">*</span>
                    </label>
                    <input type="number" name="rendimiento_real_total"
                            class="form-control"
                            value="<?= $lote['rendimiento_real_total'] ?? '' ?>"
                            min="0" required id="rendReal"
                            placeholder="Unidades reales producidas">
                </div>
            </div>
            <div style="margin-top:10px;padding:8px;background:#fff;
                        border-radius:6px;text-align:center">
                <span style="font-size:12px;color:#64748b">Porcentaje de rendimiento: </span>
                <span style="font-size:18px;font-weight:800;color:#15803d"
                    id="pctDisplay">
                    <?= $lote['porcentaje_rendimiento']
                        ? number_format($lote['porcentaje_rendimiento'],1).'%'
                        : '—' ?>
                </span>
            </div>
        </div>

        <!-- Mermas -->
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;
                    padding:14px;margin-bottom:16px">
            <div style="font-size:12px;font-weight:700;color:#dc2626;
                        margin-bottom:10px;text-transform:uppercase">
                ⚠️ Desglose de mermas (kg)
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Merma de producto (kg)</label>
                    <input type="number" name="merma_producto_kg"
                        class="form-control merma-input"
                        value="<?= $lote['merma_producto_kg'] ?? 0 ?>"
                        step="0.001" min="0">
                    <div class="form-hint">Producto defectuoso descartado</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Merma de envase/bobina (kg)</label>
                    <input type="number" name="merma_envase_kg"
                        class="form-control merma-input"
                        value="<?= $lote['merma_envase_kg'] ?? 0 ?>"
                        step="0.001" min="0">
                    <div class="form-hint">Material de empaque desperdiciado</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Merma por reproceso (kg)</label>
                    <input type="number" name="merma_reproceso_kg"
                        class="form-control merma-input"
                        value="<?= $lote['merma_reproceso_kg'] ?? 0 ?>"
                        step="0.001" min="0">
                    <div class="form-hint">Enviado a reproceso</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Merma no conforme (kg)</label>
                    <input type="number" name="merma_no_conforme_kg"
                        class="form-control merma-input"
                        value="<?= $lote['merma_no_conforme_kg'] ?? 0 ?>"
                        step="0.001" min="0">
                    <div class="form-hint">Producto fuera de especificación</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Merma por quemado (kg)</label>
                    <input type="number" name="merma_quemado_kg"
                        class="form-control merma-input"
                        value="<?= $lote['merma_quemado_kg'] ?? 0 ?>"
                        step="0.001" min="0">
                    <div class="form-hint">Producto quemado en horno</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total merma (calculado)</label>
                    <div class="form-control"
                        style="background:#fef2f2;font-weight:700;
                                color:#dc2626;font-size:16px"
                        id="totalMermaDisplay">
                        0.000 kg
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m4/lote/<?= urlencode($lote['codigo_lote']) ?>"
            class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            💾 Guardar rendimiento
        </button>
    </div>
    </form>
</div>

<script>
const teorico = <?= (int)($lote['rendimiento_teorico_total'] ?? 0) ?>;

document.getElementById('rendReal').addEventListener('input', function() {
    const real = parseFloat(this.value) || 0;
    const pct  = teorico > 0 ? ((real / teorico) * 100).toFixed(1) : 0;
    const disp = document.getElementById('pctDisplay');
    disp.textContent = pct + '%';
    disp.style.color = pct >= 95 ? '#15803d' : (pct >= 85 ? '#d97706' : '#dc2626');
});

function actualizarTotalMerma() {
    let total = 0;
    document.querySelectorAll('.merma-input').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('totalMermaDisplay').textContent =
        total.toFixed(3) + ' kg';
}

document.querySelectorAll('.merma-input').forEach(inp => {
    inp.addEventListener('input', actualizarTotalMerma);
});
actualizarTotalMerma();
</script>