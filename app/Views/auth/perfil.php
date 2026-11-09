<?php
/** @var array $usuario */
// =============================================================================
//  SIACEP — Mi perfil
//  Archivo: app/Views/auth/perfil.php
//  Formularios: POST /auth/perfil (datos) y POST /auth/password (contraseña)
// =============================================================================
use App\Core\Auth;
use App\Core\Controller;

$csrfToken = Controller::csrfToken();
$v = fn(string $campo) => htmlspecialchars((string)($usuario[$campo] ?? ''));
$rolNombre = Auth::user()['rol_nombre'] ?? '';
?>

<div class="page-header">
    <div class="page-title">
        <i class="bi bi-person-circle"></i> Mi perfil
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;align-items:start">

    <!-- Datos personales -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-person-vcard"></i> Datos personales</span>
        </div>
        <form method="POST" action="<?= APP_URL ?>/auth/perfil">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nombre <span class="form-required">*</span></label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= $v('nombre') ?>" required maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellidos</label>
                        <input type="text" name="apellidos" class="form-control"
                               value="<?= $v('apellidos') ?>" maxlength="100">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" class="form-control"
                           value="<?= $v('cargo') ?>" maxlength="100">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" value="<?= $v('email') ?>" disabled>
                        <div style="font-size:var(--fs-dense-xs);color:#94a3b8;margin-top:4px">
                            El correo solo lo puede cambiar un administrador.
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rol</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($rolNombre) ?>" disabled>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Cambio de contraseña -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-shield-lock"></i> Cambiar contraseña</span>
        </div>
        <form method="POST" action="<?= APP_URL ?>/auth/password">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Contraseña actual <span class="form-required">*</span></label>
                    <input type="password" name="password_actual" class="form-control"
                           required autocomplete="current-password">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nueva contraseña <span class="form-required">*</span></label>
                        <input type="password" name="password_nueva" class="form-control"
                               required minlength="8" autocomplete="new-password"
                               placeholder="Mín. 8 caracteres">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar <span class="form-required">*</span></label>
                        <input type="password" name="password_confirm" class="form-control"
                               required minlength="8" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-key"></i> Actualizar contraseña
                </button>
            </div>
        </form>
    </div>

</div>
