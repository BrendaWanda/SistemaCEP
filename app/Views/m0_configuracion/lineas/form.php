<?php
/** @var bool $canWrite @var string $accion @var array|null $linea */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <?= $esEditar ? 'Editar Línea' : 'Nueva Línea de Producción' ?>
        </div>
        <div class="page-sub">
            <?= $esEditar
                ? 'Modifique los datos de la línea de producción'
                : 'Complete los datos para registrar una nueva línea' ?>
        </div>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/m0/lineas" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card" style="max-width:640px">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-diagram-2-fill" style="color:#4f8ef7;margin-right:6px"></i>
            <?= $esEditar ? 'Modificar datos de la línea' : 'Datos de la nueva línea' ?>
        </span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m0/lineas/<?= $esEditar
                ? $linea['id'].'/editar' : 'nueva' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Código <span class="form-required">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control"
                            value="<?= htmlspecialchars($linea['codigo'] ?? '') ?>"
                            placeholder="Ej: LIN-PAN"
                            maxlength="20" required
                            style="text-transform:uppercase;font-family:monospace;
                                font-size:15px;font-weight:600">
                    <div class="form-hint">
                        <i class="bi bi-info-circle"></i>
                        Identificador único. Ej: LIN-PAN, LIN-GAL
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Nombre <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                            value="<?= htmlspecialchars($linea['nombre'] ?? '') ?>"
                            placeholder="Ej: Línea de Panificación"
                            maxlength="100" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"
                            placeholder="Descripción de la línea y su propósito..."><?=
                    htmlspecialchars($linea['descripcion'] ?? '')
                ?></textarea>
                <div class="form-hint">
                    <i class="bi bi-info-circle"></i>
                    Opcional. Describe el propósito y alcance de esta línea.
                </div>
            </div>

            <?php if ($esEditar): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;
                        border-radius:8px;padding:14px">
                <div style="font-size:12px;font-weight:600;color:#64748b;
                            margin-bottom:8px;text-transform:uppercase;
                            letter-spacing:.06em">
                    <i class="bi bi-info-circle" style="margin-right:4px"></i>
                    Información adicional
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;
                            font-size:13px;color:#475569">
                    <div>
                        <span style="color:#94a3b8">Código actual:</span>
                        <code style="background:#f1f5f9;padding:1px 6px;
                                    border-radius:4px;margin-left:4px;font-weight:700">
                            <?= htmlspecialchars($linea['codigo']) ?>
                        </code>
                    </div>
                    <div>
                        <span style="color:#94a3b8">Estado:</span>
                        <span class="badge <?= $linea['activa']
                            ? 'badge-success' : 'badge-muted' ?>" style="margin-left:4px">
                            <?= $linea['activa'] ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif ?>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/lineas" class="btn btn-secondary">
                <i class="bi bi-x-lg"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <?php if ($esEditar): ?>
                    <i class="bi bi-floppy"></i> Guardar cambios
                <?php else: ?>
                    <i class="bi bi-check-lg"></i> Crear línea
                <?php endif ?>
            </button>
        </div>
    </form>
</div>