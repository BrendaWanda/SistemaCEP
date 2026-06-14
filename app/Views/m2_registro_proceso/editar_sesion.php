<?php
/** @var array $sesion @var array $supervisores @var array $turnos */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div>
        <div class="page-title">
            <i class="bi bi-pencil-square" style="color:#4f8ef7;margin-right:8px"></i>
            Editar Sesión
            <span style="font-family:monospace"><?= htmlspecialchars($sesion['codigo_lote']) ?></span>
        </div>
        <div class="page-sub">
            <?= htmlspecialchars($sesion['producto_nombre']) ?>
        </div>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">Datos de la sesión</span>
    </div>
    <form method="POST" action="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>/editar">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Lote</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($sesion['codigo_lote']) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Producto</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($sesion['producto_nombre']) ?>" disabled>
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Turno <span class="form-required">*</span>
                </label>
                <select name="turno" class="form-control" required>
                    <?php foreach ($turnos as $val => $label): ?>
                    <option value="<?= $val ?>"
                        <?= $sesion['turno'] === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control"
                        value="<?= htmlspecialchars($sesion['fecha']) ?>">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Supervisor responsable</label>
                <select name="supervisor_id" class="form-control">
                    <?php foreach ($supervisores as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= (int)$id === (int)$sesion['supervisor_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nivel / Destino</label>
                <input type="text" name="nivel" class="form-control"
                        value="<?= htmlspecialchars($sesion['nivel'] ?? '') ?>">
            </div>
        </div>

        <div class="form-hint">
            <i class="bi bi-info-circle"></i>
            El lote y producto de la sesión no se pueden cambiar aquí.
            Para corregir esos datos, elimine esta sesión y cree una nueva
            sobre el lote correcto.
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m2/sesion/<?= $sesion['id'] ?>" class="btn btn-secondary">
            Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> Guardar cambios
        </button>
    </div>
    </form>
</div>