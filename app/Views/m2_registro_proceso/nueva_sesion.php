<?php
/** @var array $lotes @var array $supervisores @var array $turnos
 *  @var string $fecha_hoy @var string $hora_ahora */
?>
<?php require __DIR__ . '/_nav.php'; ?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>

<div class="page-header">
    <div class="page-title">➕ Nueva Sesión de Registro</div>
    <div class="page-sub">
        Vincula la sesión a un lote activo de producción.
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">Datos de la sesión</span>
    </div>
    <form method="POST" action="<?= APP_URL ?>/m2/nueva-sesion">
    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
    <div class="card-body">

        <div class="form-group">
            <label class="form-label">
                Lote de producción activo <span class="form-required">*</span>
            </label>
            <select name="lote_id" class="form-control" required>
                <option value="">Seleccione el lote en proceso...</option>
                <?php if (empty($lotes)): ?>
                <option value="" disabled>
                    No hay lotes en proceso. Abra un lote en M4 primero.
                </option>
                <?php else: ?>
                <?php foreach ($lotes as $id => $label): ?>
                <option value="<?= $id ?>">
                    <?= htmlspecialchars($label) ?>
                </option>
                <?php endforeach ?>
                <?php endif ?>
            </select>
            <?php if (empty($lotes)): ?>
            <div class="form-hint" style="color:#dc2626">
                ⚠️ No hay lotes en proceso.
                <a href="<?= APP_URL ?>/m4/nuevo-lote">Abrir un lote primero</a>
            </div>
            <?php endif ?>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">
                    Turno <span class="form-required">*</span>
                </label>
                <select name="turno" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($turnos as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control"
                        value="<?= $fecha_hoy ?>"
                        max="<?= $fecha_hoy ?>">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label class="form-label">Supervisor responsable</label>
                <select name="supervisor_id" class="form-control">
                    <?php foreach ($supervisores as $id => $nombre): ?>
                    <option value="<?= $id ?>"
                        <?= $id == \App\Core\Auth::id() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Nivel / Destino</label>
                <input type="text" name="nivel" class="form-control"
                        value="Secundaria y Ed. Especial">
            </div>
        </div>

    </div>
    <div class="card-footer">
        <a href="<?= APP_URL ?>/m2" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"
                <?= empty($lotes) ? 'disabled' : '' ?>>
            📋 Crear sesión de registro
        </button>
    </div>
    </form>
</div>