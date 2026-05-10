<?php
/** @var bool $canWrite @var string $accion @var array|null $linea */
?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php require __DIR__ . '/../_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Línea' : '➕ Nueva Línea de Producción' ?>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">
            <?= $esEditar ? 'Modificar datos de la línea' : 'Datos de la nueva línea' ?>
        </span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/m0/lineas/<?= $esEditar ? $linea['id'].'/editar' : 'nueva' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Código <span class="form-required">*</span></label>
                    <input type="text" name="codigo" class="form-control"
                            value="<?= htmlspecialchars($linea['codigo'] ?? '') ?>"
                            placeholder="Ej: LIN-GAL" maxlength="20" required
                            style="text-transform:uppercase">
                    <div class="form-hint">Identificador único. Ej: LIN-PAN, LIN-GAL</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre <span class="form-required">*</span></label>
                    <input type="text" name="nombre" class="form-control"
                            value="<?= htmlspecialchars($linea['nombre'] ?? '') ?>"
                            placeholder="Ej: Línea de Galletería" maxlength="100" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"
                        placeholder="Descripción de la línea y su propósito...">
<?= htmlspecialchars($linea['descripcion'] ?? '') ?></textarea>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/m0/lineas" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear línea' ?>
            </button>
        </div>
    </form>
</div>