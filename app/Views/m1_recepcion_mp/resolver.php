<?php
/** @var array $recepcion */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <i class="bi bi-question-diamond-fill"></i> Resolver Recepción <?= htmlspecialchars($recepcion['codigo_recepcion']) ?>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($recepcion['insumo_nombre']) ?> ·
            <?= htmlspecialchars($recepcion['proveedor_nombre']) ?> ·
            Estado actual:
            <span class="badge badge-warning"><?= strtoupper($recepcion['decision']) ?></span>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>" class="btn btn-secondary">← Volver</a>
    </div>
</div>

<div class="card" style="max-width:650px">
    <div class="card-header">
        <span class="card-title">Decisión final</span>
    </div>
    <form method="POST" action="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>/resolver">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;
                    padding:12px 14px;margin-bottom:16px;font-size:var(--fs-dense-sm);color:#92400e">
            <i class="bi bi-info-circle"></i>
            Esta recepción quedó como <strong><?= strtoupper($recepcion['decision']) ?></strong> pendiente
            de resultados adicionales. El stock físico ya está reservado
            (<?= number_format($recepcion['stock_disponible_kg'] ?? 0, 3) ?>
            <?= htmlspecialchars($recepcion['unidad_medida']) ?>) pero no disponible para uso —
            al resolver, pasará a <strong>disponible</strong> si se aprueba, o a
            <strong>rechazado</strong> si no.
        </div>

        <div class="form-group">
            <label class="form-label">
                Resolución final <span class="form-required">*</span>
            </label>
            <select name="nueva_decision" class="form-control" required>
                <option value="">— Seleccione —</option>
                <option value="aprobado">Aprobado</option>
                <option value="rechazado">Rechazado</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label">
                Nota de resolución <span class="form-required">*</span>
            </label>
            <textarea name="nota_resolucion" class="form-control" rows="3" required
                placeholder="Motivo de la resolución (ej: resultado de laboratorio externo, nueva muestra, etc.)"></textarea>
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m1/<?= $recepcion['id'] ?>" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"
                data-confirm="Esta acción cambia la decisión final de la recepción y no se puede deshacer. ¿Continuar?">
            <i class="bi bi-check-circle-fill"></i> Confirmar resolución
        </button>
    </div>
    </form>
</div>