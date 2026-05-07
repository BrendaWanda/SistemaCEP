<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'SIACEP' ?> — Gustossi SRL</title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body>

<?php
use App\Core\Auth;
use App\Core\Controller;
$user      = Auth::user();
$modulos   = Auth::modulosAccesibles();
$flashes   = Controller::getFlash();
$csrfToken = Controller::csrfToken();

// URI relativa sin basePath ni query string
$basePath  = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw    = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri       = ($basePath && str_starts_with($uriRaw, $basePath))
                ? substr($uriRaw, strlen($basePath))
                : $uriRaw;
$uri = $uri ?: '/';

// Helper para marcar item activo
$active = fn(string $prefijo) => str_starts_with($uri, $prefijo) ? 'active' : '';
?>

<!-- ── SIDEBAR ──────────────────────────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span class="logo-icon">⚙</span>
            <div>
                <div class="logo-title">SIACEP</div>
                <div class="logo-sub">Gustossi SRL</div>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Contraer menú">‹</button>
    </div>

    <nav class="sidebar-nav">

        <!-- Dashboard -->
        <a href="<?= APP_URL ?>/dashboard"
            class="nav-item <?= ($uri === '/' || str_starts_with($uri, '/dashboard')) ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>

        <?php if (isset($modulos['m0_configuracion'])): ?>
        <div class="nav-section">Configuración</div>
        <a href="<?= APP_URL ?>/m0/lineas" class="nav-item <?= $active('/m0') ?>">
            <span class="nav-icon">⚙️</span>
            <span class="nav-label">Config. maestra</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['usuarios'])): ?>
        <a href="<?= APP_URL ?>/usuarios" class="nav-item <?= $active('/usuarios') ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-label">Usuarios</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m1_recepcion_mp']) || isset($modulos['m4_seguimiento']) || isset($modulos['m2_registro_proceso']) || isset($modulos['m3_mantenimiento'])): ?>
        <div class="nav-section">Operaciones</div>
        <?php endif ?>

        <?php if (isset($modulos['m1_recepcion_mp'])): ?>
        <a href="<?= APP_URL ?>/m1" class="nav-item <?= $active('/m1') ?>">
            <span class="nav-icon">🚛</span>
            <span class="nav-label">Recepción MP</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m4_seguimiento'])): ?>
        <a href="<?= APP_URL ?>/m4" class="nav-item <?= $active('/m4') ?>">
            <span class="nav-icon">🏭</span>
            <span class="nav-label">Seguim. producción</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m2_registro_proceso'])): ?>
        <a href="<?= APP_URL ?>/m2" class="nav-item <?= $active('/m2') ?>">
            <span class="nav-icon">📋</span>
            <span class="nav-label">Registro proceso</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m3_mantenimiento'])): ?>
        <a href="<?= APP_URL ?>/m3" class="nav-item <?= $active('/m3') ?>">
            <span class="nav-icon">🔧</span>
            <span class="nav-label">Mantenimiento</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m5_trazabilidad']) || isset($modulos['m6_spc'])): ?>
        <div class="nav-section">Análisis</div>
        <?php endif ?>

        <?php if (isset($modulos['m5_trazabilidad'])): ?>
        <a href="<?= APP_URL ?>/m5" class="nav-item <?= $active('/m5') ?>">
            <span class="nav-icon">🔗</span>
            <span class="nav-label">Trazabilidad</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m6_spc'])): ?>
        <a href="<?= APP_URL ?>/m6" class="nav-item <?= $active('/m6') ?>">
            <span class="nav-icon">📈</span>
            <span class="nav-label">SPC</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m7_dashboard'])): ?>
        <div class="nav-section">Indicadores</div>
        <a href="<?= APP_URL ?>/m7" class="nav-item <?= $active('/m7') ?>">
            <span class="nav-icon">🎯</span>
            <span class="nav-label">KPIs y OEE</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['menu_trimestral'])): ?>
        <div class="nav-section">Planificación</div>
        <a href="<?= APP_URL ?>/menu" class="nav-item <?= $active('/menu') ?>">
            <span class="nav-icon">📅</span>
            <span class="nav-label">Menú trimestral</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['reportes'])): ?>
        <div class="nav-section">Documentos</div>
        <a href="<?= APP_URL ?>/reportes" class="nav-item <?= $active('/reportes') ?>">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Reportes</span>
        </a>
        <?php endif ?>

    </nav>

    <!-- Footer sidebar: usuario + acciones -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <div class="user-name">
                    <?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?>
                </div>
                <div class="user-role"><?= htmlspecialchars($user['rol_nombre'] ?? '') ?></div>
            </div>
        </div>
        <div class="sidebar-actions">
            <a href="<?= APP_URL ?>/auth/perfil" title="Mi perfil">👤</a>
            <a href="<?= APP_URL ?>/auth/logout" title="Cerrar sesión"
                onclick="return confirm('¿Desea cerrar sesión?')">🚪</a>
        </div>
    </div>

</aside>

<!-- ── ÁREA PRINCIPAL ────────────────────────────────────────────────────────── -->
<main class="main-content" id="mainContent">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" id="topbarToggle">☰</button>
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <?php if (!empty($breadcrumb)): ?>
                    <?php foreach ($breadcrumb as $i => $crumb): ?>
                        <?php if ($i < count($breadcrumb) - 1): ?>
                            <a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                            <span class="bc-sep">›</span>
                        <?php else: ?>
                            <span class="bc-current"><?= htmlspecialchars($crumb['label']) ?></span>
                        <?php endif ?>
                    <?php endforeach ?>
                <?php else: ?>
                    <span class="bc-current"><?= htmlspecialchars($pageTitle ?? 'SIACEP') ?></span>
                <?php endif ?>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-empresa"><?= EMPRESA_NOMBRE ?></span>
            <span class="topbar-fecha"><?= date('d/m/Y H:i') ?></span>
            <!-- Cerrar sesión también en topbar -->
            <a href="<?= APP_URL ?>/auth/logout"
                onclick="return confirm('¿Desea cerrar sesión?')"
                style="color:#dc2626;text-decoration:none;font-size:12px;font-weight:600;
                    padding:4px 10px;border:1px solid #fca5a5;border-radius:6px;
                    background:#fff5f5"
                title="Cerrar sesión">
                🚪 Salir
            </a>
        </div>
    </header>

    <!-- Mensajes Flash -->
    <?php if (!empty($flashes)): ?>
    <div class="flash-container">
        <?php foreach ($flashes as $flash): ?>
        <div class="flash flash-<?= $flash['type'] ?>" role="alert">
            <span><?= htmlspecialchars($flash['message']) ?></span>
            <button class="flash-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Contenido de la vista -->
    <div class="page-content">
        <?php /** @var string $content */ ?>
        <?= $content ?>
    </div>

</main>

<!-- CSRF Token para AJAX -->
<meta name="csrf-token" content="<?= $csrfToken ?>">

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>