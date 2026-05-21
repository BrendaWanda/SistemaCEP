<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIACEP — Iniciar sesión</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    height: 100%;
    min-height: 100vh;
    font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
    font-size: 14px;
}

/* ── WRAPPER PRINCIPAL ───────────────────────────────────────────────────── */
.login-wrapper {
    display: flex;
    flex-direction: row;
    min-height: 100vh;
    width: 100%;
}

/* ── PANEL IZQUIERDO — formulario ────────────────────────────────────────── */
.login-left {
    width: 440px;
    min-width: 440px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 48px 48px;
    position: relative;
    z-index: 2;
}

.login-brand { margin-bottom: 40px; }
.login-brand-name {
    font-size: 20px;
    font-weight: 800;
    color: #09090b;
}
.login-brand-sub {
    font-size: 12px;
    color: #71717a;
    margin-top: 2px;
}

.login-heading {
    font-size: 28px;
    font-weight: 700;
    color: #09090b;
    margin-bottom: 6px;
}
.login-desc {
    font-size: 14px;
    color: #71717a;
    margin-bottom: 32px;
    line-height: 1.5;
}

.form-group { margin-bottom: 20px; }
.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #3f3f46;
    margin-bottom: 7px;
}
.input-wrap { position: relative; }
.input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #a1a1aa;
    font-size: 15px;
    pointer-events: none;
}
.form-input {
    width: 100%;
    padding: 11px 12px 11px 38px;
    border: 1px solid #e4e4e7;
    border-radius: 7px;
    font-size: 14px;
    color: #09090b;
    background: #fafafa;
    outline: none;
    transition: border-color .12s, background .12s, box-shadow .12s;
    font-family: inherit;
}
.form-input:focus {
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.form-input::placeholder { color: #a1a1aa; }

.toggle-pass {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #a1a1aa;
    font-size: 16px;
    padding: 2px;
    transition: color .12s;
}
.toggle-pass:hover { color: #71717a; }

.btn-login {
    width: 100%;
    padding: 12px;
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 7px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background .12s;
    margin-top: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-login:hover { background: #1d4ed8; }

.login-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #f4f4f5;
    font-size: 12px;
    color: #a1a1aa;
    text-align: center;
    line-height: 1.7;
}

.flash-msg {
    padding: 10px 14px;
    border-radius: 7px;
    margin-bottom: 20px;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    border-left: 3px solid;
}
.flash-msg.error   { background: #fef2f2; color: #991b1b; border-color: #dc2626; }
.flash-msg.success { background: #f0fdf4; color: #14532d; border-color: #16a34a; }
.flash-msg.warning { background: #fffbeb; color: #78350f; border-color: #d97706; }

/* ── PANEL DERECHO — visual ──────────────────────────────────────────────── */
.login-right {
    flex: 1;
    background: #09090b;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

/* Gradiente azul de fondo */
.login-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 25% 35%, rgba(37,99,235,.3) 0%, transparent 55%),
        radial-gradient(circle at 75% 70%, rgba(59,130,246,.18) 0%, transparent 50%),
        radial-gradient(circle at 65% 20%, rgba(99,102,241,.15) 0%, transparent 45%);
    z-index: 0;
}
/* Grid pattern */
.login-right::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size: 44px 44px;
    z-index: 0;
}

.login-right-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    max-width: 400px;
}

.login-logo-wrap {
    width: 130px;
    height: 130px;
    border-radius: 22px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    overflow: hidden;
}
.login-logo-wrap img {
    width: 110px;
    height: 110px;
    object-fit: contain;
}

.login-right-title {
    font-size: 28px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.25;
    margin-bottom: 14px;
}
.login-right-sub {
    font-size: 14px;
    color: #71717a;
    line-height: 1.65;
    margin-bottom: 36px;
}

.login-chips {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}
.chip {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px;
    text-align: left;
}
.chip-icon {
    width: 34px; height: 34px;
    border-radius: 7px;
    background: rgba(37,99,235,.3);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.chip-icon i { font-size: 15px; color: #93c5fd; }
.chip-title  { font-size: 13px; font-weight: 600; color: #e4e4e7; }
.chip-desc   { font-size: 12px; color: #71717a; margin-top: 1px; }

.login-version {
    position: absolute;
    bottom: 18px;
    font-size: 11px;
    color: #27272a;
    z-index: 1;
}

/* ── RESPONSIVE ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .login-wrapper { flex-direction: column; }
    .login-left    { width: 100%; min-width: unset; padding: 40px 32px; justify-content: flex-start; padding-top: 60px; }
    .login-right   { min-height: 300px; padding: 32px; }
    .login-right-title { font-size: 22px; }
    .login-logo-wrap   { width: 90px; height: 90px; margin-bottom: 20px; }
    .login-logo-wrap img { width: 76px; height: 76px; }
    .login-chips { display: none; }
}
</style>
</head>
<body>

<div class="login-wrapper">

    <!-- ── PANEL IZQUIERDO ──────────────────────────────────────────────── -->
    <div class="login-left">

        <div class="login-brand">
            <div class="login-brand-name">SISTEMA</div>
            <div class="login-brand-sub">CONTROL DEL PROCESO</div>
        </div>

        <?php
        use App\Core\Controller;
        $flashes = Controller::getFlash();
        foreach ($flashes as $flash):
            $icon = match($flash['type']) {
                'success' => 'bi-check-circle-fill',
                'error'   => 'bi-x-circle-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                default   => 'bi-info-circle-fill',
            };
        ?>
        <div class="flash-msg <?= $flash['type'] ?>">
            <i class="bi <?= $icon ?>"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endforeach ?>

        <div class="login-heading">Bienvenido</div>
        <div class="login-desc">
            Ingrese sus credenciales para acceder al sistema.
        </div>

        <form method="POST" action="<?= APP_URL ?>/auth/login">
            <input type="hidden" name="_token"
                    value="<?= Controller::csrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="email">
                    Correo electrónico
                </label>
                <div class="input-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" id="email" name="email"
                            class="form-input"
                            placeholder="usuario@gustossi.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" id="password" name="password"
                            class="form-input"
                            placeholder="••••••••"
                            required>
                    <button type="button" class="toggle-pass"
                            onclick="togglePass()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Ingresar al sistema
            </button>
        </form>

        <div class="login-footer">
            Programa Municipal del Desayuno Escolar<br>
            <strong style="color:#52525b">
                GAMLP — Lote N°2 Secundaria 2026
            </strong>
        </div>

    </div>

    <!-- ── PANEL DERECHO ────────────────────────────────────────────────── -->
    <div class="login-right">

        <div class="login-right-inner">

            <div class="login-logo-wrap">
                <img src="<?= APP_URL ?>/assets/img/gustossilogo.png"
                    alt="Gustossi SRL"
                    onerror="this.parentElement.innerHTML=
                        '<i class=\'bi bi-buildings-fill\' style=\'font-size:56px;color:#3b82f6\'></i>'">
            </div>

            <div class="login-right-title">
                Industrias Alimenticias<br>Gustossi SRL
            </div>
            <div class="login-right-sub">
                Control integral de producción, calidad y trazabilidad
                para el Desayuno Escolar Municipal.
            </div>

            <div class="login-chips">
                <div class="chip">
                    <div class="chip-icon">
                        <i class="bi bi-clipboard2-check-fill"></i>
                    </div>
                    <div>
                        <div class="chip-title">Control de proceso</div>
                        <div class="chip-desc">SPC · Gráfico X̄-R · Western Electric</div>
                    </div>
                </div>
                <div class="chip">
                    <div class="chip-icon">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <div class="chip-title">Trazabilidad completa</div>
                        <div class="chip-desc">MP → Proceso → Producto terminado</div>
                    </div>
                </div>
                <div class="chip">
                    <div class="chip-icon">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <div>
                        <div class="chip-title">KPIs y OEE</div>
                        <div class="chip-desc">Disponibilidad · Rendimiento · Calidad</div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>