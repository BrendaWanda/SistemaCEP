<?php
use App\Core\Controller;
$csrfToken = Controller::csrfToken();
?>
<div class="auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <div class="auth-logo">⚙</div>
            <h1 class="auth-title">SIACEP</h1>
            <p class="auth-sub">Sistema Integrado de Análisis, Control<br>y Ejecución de Producción</p>
            <p class="auth-empresa">Gustossi SRL</p>
        </div>

        <form method="POST" action="<?= APP_URL ?>/auth/login" class="auth-form" autocomplete="off">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="usuario@gustossi.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required>
                    <button type="button" class="input-suffix" onclick="togglePassword()" title="Mostrar/ocultar">
                        <span id="eyeIcon">👁</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Ingresar al sistema
            </button>
        </form>

        <div class="auth-footer">
            <p>Desayuno Escolar Municipal — GAMLP</p>
            <p><?= date('Y') ?></p>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const p = document.getElementById('password');
    const e = document.getElementById('eyeIcon');
    if (p.type === 'password') { p.type = 'text';     e.textContent = '🙈'; }
    else                       { p.type = 'password'; e.textContent = '👁'; }
}
</script>