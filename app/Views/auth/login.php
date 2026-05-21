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
    font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
}

/* ── Contenedor principal ────────────────────────────────────────── */
.login-card {
    display: flex;
    width: 860px;
    min-height: 520px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(26,32,53,.25),
                0 8px 20px rgba(26,32,53,.12);
    position: relative;
}

/* ── PANEL IZQUIERDO — formulario ────────────────────────────────── */
.login-left {
    width: 400px;
    flex-shrink: 0;
    background: #ffffff;
    padding: 48px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 1;
}

/* Logo pequeño arriba */
.login-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 32px;
}
.login-brand-logo {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: #1a2035;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.login-brand-logo img {
    width: 32px;
    height: 32px;
    object-fit: contain;
}
.login-brand-text { line-height: 1.2; }
.login-brand-name {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: .2px;
}
.login-brand-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 1px;
}

/* Bienvenido */
.login-welcome {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.login-title {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 6px;
    line-height: 1.2;
}
.login-desc {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 28px;
    line-height: 1.5;
}

/* Flash */
.flash-msg {
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    border-left: 3px solid;
}
.flash-msg.error   { background: #fef2f2; color: #991b1b; border-color: #ef4444; }
.flash-msg.success { background: #f0fdf4; color: #14532d; border-color: #22c55e; }
.flash-msg.warning { background: #fffbeb; color: #78350f; border-color: #f59e0b; }

/* Inputs */
.form-group { margin-bottom: 16px; }
.input-wrap { position: relative; }
.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #cbd5e1;
    font-size: 15px;
    pointer-events: none;
    line-height: 1;
}
.form-input {
    width: 100%;
    padding: 12px 14px 12px 40px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    transition: border-color .15s, background .15s, box-shadow .15s;
    font-family: inherit;
}
.form-input:focus {
    border-color: #4f8ef7;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(79,142,247,.12);
}
.form-input::placeholder { color: #cbd5e1; }
.toggle-pass {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #cbd5e1;
    font-size: 16px;
    padding: 2px;
    transition: color .12s;
    line-height: 1;
}
.toggle-pass:hover { color: #94a3b8; }

/* Botón */
.btn-login {
    width: 100%;
    padding: 13px;
    background: #1a2035;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background .15s;
    margin-top: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: .2px;
}
.btn-login:hover { background: #243050; }

/* Footer */
.login-footer {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
    font-size: 11px;
    color: #cbd5e1;
    text-align: center;
    line-height: 1.7;
}
.login-footer strong { color: #94a3b8; }

/* ── PANEL DERECHO — azul con logo ───────────────────────────────── */
.login-right {
    flex: 1;
    background: linear-gradient(145deg, #1a2035 0%, #1e2746 40%, #2d3a6b 100%);
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 40px;
    overflow: hidden;
}

/* Círculos decorativos de fondo */
.login-right::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -80px;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.login-right::after {
    content: '';
    position: absolute;
    bottom: -60px;
    left: -60px;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.login-right-circle {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 340px;
    height: 340px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.05);
    pointer-events: none;
}
.login-right-circle2 {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 240px;
    height: 240px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.05);
    pointer-events: none;
}

/* Contenido derecho */
.login-right-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

/* Logo grande */
.login-logo-big {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}
.login-logo-big img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    filter: drop-shadow(0 4px 16px rgba(0,0,0,.3));
}
.login-right-title {
    font-size: 22px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.3;
    margin-bottom: 10px;
    letter-spacing: .2px;
}
.login-right-sub {
    font-size: 13px;
    color: rgba(255,255,255,.45);
    line-height: 1.65;
    margin-bottom: 32px;
    max-width: 260px;
}

/* Chips */
.login-chips {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
    max-width: 280px;
}
.login-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 10px;
    text-align: left;
}
.chip-icon {
    width: 30px; height: 30px;
    border-radius: 7px;
    background: rgba(79,142,247,.25);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.chip-icon i { font-size: 14px; color: #93c5fd; }
.chip-title { font-size: 12px; font-weight: 600; color: #e2e8f0; }
.chip-desc  { font-size: 11px; color: rgba(255,255,255,.35); margin-top: 1px; }

/* Versión */
.login-version {
    position: absolute;
    bottom: 16px;
    font-size: 10px;
    color: rgba(255,255,255,.2);
    z-index: 1;
}

/* Responsive */
@media (max-width: 860px) {
    body { align-items: stretch; padding: 0; background: #fff; }
    .login-card {
        flex-direction: column;
        width: 100%;
        min-height: 100vh;
        border-radius: 0;
        box-shadow: none;
    }
    .login-left  { width: 100%; padding: 40px 28px; }
    .login-right { min-height: 280px; padding: 32px; }
    .login-chips { display: none; }
    .login-logo-big { width: 80px; height: 80px; margin-bottom: 16px; }
    .login-logo-big img { width: 64px; height: 64px; }
    .login-right-title { font-size: 18px; }
}
body {
    background:
        linear-gradient(rgba(10,15,30,.78), rgba(10,15,30,.78)),
        var(--bg-img) center/cover no-repeat fixed;
}
</style>
</head>
<body>
<style>
    :root { --bg-img: url('<?= APP_URL ?>/assets/img/GustossiF.jpg'); }
</style>
<div class="login-card">

    <!-- ── PANEL IZQUIERDO ──────────────────────────────────────── -->
    <div class="login-left">

        <!-- Logo pequeño -->
        <div class="login-brand">
            <div class="login-brand-logo">
                <img src="<?= APP_URL ?>/assets/img/gustossilogo.png"
                    alt="Gustossi"
                    onerror="this.parentElement.innerHTML=
                        '<i class=\'bi bi-buildings-fill\' style=\'font-size:18px;color:#4f8ef7\'></i>'">
            </div>
            <div class="login-brand-text">
                <div class="login-brand-name">SIACEP</div>
                <div class="login-brand-sub">Gustossi SRL</div>
            </div>
        </div>

        <!-- Flash messages -->
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

        <!-- Encabezado -->
        <div class="login-welcome">Bienvenido a</div>
        <div class="login-title">Sistema SIACEP</div>
        <div class="login-desc">
            Ingrese sus credenciales para acceder al sistema de control de producción.
        </div>

        <!-- Formulario -->
        <form method="POST" action="<?= APP_URL ?>/auth/login">
            <input type="hidden" name="_token"
                value="<?= Controller::csrfToken() ?>">

            <div class="form-group">
                <div class="input-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email"
                        class="form-input"
                        placeholder="Correo electrónico"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required autofocus>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" id="password" name="password"
                        class="form-input"
                        placeholder="Contraseña"
                        required>
                    <button type="button" class="toggle-pass" onclick="togglePass()">
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
            <strong>GAMLP — Lote N°2 Secundaria 2026</strong>
        </div>

    </div>

    <!-- ── PANEL DERECHO ────────────────────────────────────────── -->
    <div class="login-right">
        <div class="login-right-circle"></div>
        <div class="login-right-circle2"></div>

        <div class="login-right-inner">

            <!-- Logo grande -->
            <div class="login-logo-big">
                <img src="<?= APP_URL ?>/assets/img/gustossilogo.png"
                    alt="Gustossi SRL"
                    onerror="this.parentElement.innerHTML=
                        '<i class=\'bi bi-buildings-fill\' style=\'font-size:48px;color:#4f8ef7\'></i>'">
            </div>

            <div class="login-right-title">
                Industrias Alimenticias<br>Gustossi SRL
            </div>
            <div class="login-right-sub">
                Control integral de producción, calidad y trazabilidad
                para el Desayuno Escolar Municipal.
            </div>

            <div class="login-chips">
                <div class="login-chip">
                    <div class="chip-icon">
                        <i class="bi bi-clipboard2-check-fill"></i>
                    </div>
                    <div>
                        <div class="chip-title">Control de proceso</div>
                        <div class="chip-desc">SPC · Gráfico X̄-R · Western Electric</div>
                    </div>
                </div>
                <div class="login-chip">
                    <div class="chip-icon">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <div class="chip-title">Trazabilidad completa</div>
                        <div class="chip-desc">MP → Proceso → Producto terminado</div>
                    </div>
                </div>
                <div class="login-chip">
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

        <div class="login-version">SIACEP v1.0 · 2026</div>

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