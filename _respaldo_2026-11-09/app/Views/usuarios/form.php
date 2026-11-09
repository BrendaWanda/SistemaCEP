<?php
/** @var array|null $usuario @var array $roles @var string $accion */
?>
<?php $csrfToken = \App\Core\Controller::csrfToken(); ?>
<?php $esEditar  = $accion === 'editar'; ?>
<?php $v = fn($campo, $def='') => htmlspecialchars($usuario[$campo] ?? $def); ?>

<div class="page-header">
    <div class="page-title">
        <?= $esEditar ? '✏️ Editar Usuario' : '➕ Nuevo Usuario' ?>
    </div>
</div>

<div class="card" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">Datos del usuario</span>
    </div>
    <form method="POST"
            action="<?= APP_URL ?>/usuarios/<?= $esEditar ? $usuario['id'].'/editar' : 'nuevo' ?>">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
        <div class="card-body">

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Nombre <span class="form-required">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control"
                            value="<?= $v('nombre') ?>"
                            placeholder="Ej: María" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control"
                            value="<?= $v('apellidos') ?>"
                            placeholder="Ej: García López">
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">
                        Correo electrónico <span class="form-required">*</span>
                    </label>
                    <input type="email" name="email" class="form-control"
                            value="<?= $v('email') ?>"
                            placeholder="usuario@gustossi.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" class="form-control"
                            value="<?= $v('cargo') ?>"
                            placeholder="Ej: Supervisora de Calidad">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Rol <span class="form-required">*</span>
                </label>
                <select name="rol_id" class="form-control" required>
                    <option value="">Seleccione un rol...</option>
                    <?php foreach ($roles as $rol): ?>
                    <option value="<?= $rol['id'] ?>"
                            <?= ($usuario['rol_id'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Contraseña -->
            <div style="border:1px solid #e2e8f0;border-radius:8px;
                        padding:14px;margin-top:8px">
                <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:10px">
                    🔐 Contraseña
                    <?php if ($esEditar): ?>
                    <span style="font-weight:400;color:#94a3b8">
                        — Dejar vacío para no cambiar
                    </span>
                    <?php endif ?>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">
                            <?= $esEditar ? 'Nueva contraseña' : 'Contraseña' ?>
                            <?php if (!$esEditar): ?>
                            <span class="form-required">*</span>
                            <?php endif ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                                placeholder="Mín. 8 caracteres"
                                <?= !$esEditar ? 'required' : '' ?>>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Confirmar contraseña</label>
                        <input type="password" name="password_confirm" class="form-control"
                                placeholder="Repita la contraseña">
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer">
            <a href="<?= APP_URL ?>/usuarios" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $esEditar ? '💾 Guardar cambios' : '✅ Crear usuario' ?>
            </button>
        </div>
    </form>
</div>