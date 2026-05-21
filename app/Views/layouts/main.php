<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'SIACEP' ?> — Industrias Alimenticias Gustossi SRL</title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css?v=<?= time() ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php
use App\Core\Auth;
use App\Core\Controller;
$user      = Auth::user();
$modulos   = Auth::modulosAccesibles();
$flashes   = Controller::getFlash();
$csrfToken = Controller::csrfToken();

$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$uriRaw   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
$uri      = ($basePath && str_starts_with($uriRaw, $basePath))
            ? substr($uriRaw, strlen($basePath))
            : $uriRaw;
$uri = $uri ?: '/';

$active = fn(string $p) => str_starts_with($uri, $p) ? 'active' : '';
$tip    = fn(string $t) => 'data-tip="'.$t.'"';

$nombreCompleto = trim(($user['nombre'] ?? '').' '.($user['apellidos'] ?? ''));
$inicial        = strtoupper(substr($user['nombre'] ?? 'U', 0, 1));
$rol            = $user['rol_nombre'] ?? '';
?>

<aside class="sidebar" id="sidebar">

    <!-- 1. LOGO -->
    <div class="sb-logo">
        <a href="<?= APP_URL ?>/dashboard" class="sb-logo-inner">
            <div class="sb-logo-img">
                <img src="<?= APP_URL ?>/assets/img/gustossilogo.png"
                    alt="Gustossi"
                    onerror="this.parentElement.innerHTML=
                        '<i class=\'bi bi-buildings-fill\' style=\'font-size:22px;color:#4f8ef7\'></i>'">
            </div>
            <div class="sb-logo-text">
                <div class="sb-logo-name">SISTEMA</div>
                <div class="sb-logo-sub">Gustossi SRL</div>
            </div>
        </a>
        <button class="sb-toggle" id="sidebarToggle" title="Colapsar">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    <!-- 2. PERFIL -->
    <a href="<?= APP_URL ?>/auth/perfil" class="sb-profile">
        <div class="sb-avatar"><?= $inicial ?></div>
        <div class="sb-user-info">
            <div class="sb-user-name"><?= htmlspecialchars($nombreCompleto) ?></div>
            <div class="sb-user-role"><?= htmlspecialchars($rol) ?></div>
        </div>
    </a>

    <!-- 3. NAV -->
    <nav class="sb-nav">

        <a href="<?= APP_URL ?>/dashboard"
            class="sb-item <?= ($uri==='/' || str_starts_with($uri,'/dashboard')) ? 'active' : '' ?>"
            <?= $tip('Dashboard') ?>>
            <span class="sb-icon"><i class="bi bi-speedometer2"></i></span>
            <span class="sb-label">Dashboard</span>
        </a>

        <?php if (isset($modulos['m0_configuracion'])): ?>
        <a href="<?= APP_URL ?>/m0/lineas"
            class="sb-item <?= $active('/m0') ?>"
            <?= $tip('Config. maestra') ?>>
            <span class="sb-icon"><i class="bi bi-gear-fill"></i></span>
            <span class="sb-label">Config. general</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['usuarios'])): ?>
        <a href="<?= APP_URL ?>/usuarios"
            class="sb-item <?= $active('/usuarios') ?>"
            <?= $tip('Usuarios') ?>>
            <span class="sb-icon"><i class="bi bi-people-fill"></i></span>
            <span class="sb-label">Usuarios</span>
        </a>
        <?php endif ?>

        <?php if (
            isset($modulos['m1_recepcion_mp'])    ||
            isset($modulos['m4_seguimiento'])      ||
            isset($modulos['m2_registro_proceso']) ||
            isset($modulos['m3_mantenimiento'])
        ): ?>
        <?php endif ?>

        <?php if (isset($modulos['m1_recepcion_mp'])): ?>
        <a href="<?= APP_URL ?>/m1"
            class="sb-item <?= $active('/m1') ?>"
            <?= $tip('Recepción MP') ?>>
            <span class="sb-icon"><i class="bi bi-truck-front-fill"></i></span>
            <span class="sb-label">Recepción MP</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m4_seguimiento'])): ?>
        <a href="<?= APP_URL ?>/m4"
            class="sb-item <?= $active('/m4') ?>"
            <?= $tip('Seguim. producción') ?>>
            <span class="sb-icon"><i class="bi bi-building-fill-gear"></i></span>
            <span class="sb-label">Seguim. producción</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m2_registro_proceso'])): ?>
        <a href="<?= APP_URL ?>/m2"
            class="sb-item <?= $active('/m2') ?>"
            <?= $tip('Registro proceso') ?>>
            <span class="sb-icon"><i class="bi bi-clipboard2-check-fill"></i></span>
            <span class="sb-label">Registro proceso</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m3_mantenimiento'])): ?>
        <a href="<?= APP_URL ?>/m3"
            class="sb-item <?= $active('/m3') ?>"
            <?= $tip('Mantenimiento') ?>>
            <span class="sb-icon"><i class="bi bi-wrench-adjustable-circle-fill"></i></span>
            <span class="sb-label">Mantenimiento</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m5_trazabilidad']) || isset($modulos['m6_spc'])): ?>
        <?php endif ?>

        <?php if (isset($modulos['m5_trazabilidad'])): ?>
        <a href="<?= APP_URL ?>/m5"
            class="sb-item <?= $active('/m5') ?>"
            <?= $tip('Trazabilidad') ?>>
            <span class="sb-icon"><i class="bi bi-diagram-3-fill"></i></span>
            <span class="sb-label">Trazabilidad</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m6_spc'])): ?>
        <a href="<?= APP_URL ?>/m6"
            class="sb-item <?= $active('/m6') ?>"
            <?= $tip('SPC') ?>>
            <span class="sb-icon"><i class="bi bi-graph-up-arrow"></i></span>
            <span class="sb-label">SPC</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['m7_dashboard'])): ?>
        <a href="<?= APP_URL ?>/m7"
            class="sb-item <?= $active('/m7') ?>"
            <?= $tip('KPIs y OEE') ?>>
            <span class="sb-icon"><i class="bi bi-bullseye"></i></span>
            <span class="sb-label">KPIs</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['menu_trimestral'])): ?>
        <a href="<?= APP_URL ?>/menu-trimestral"
            class="sb-item <?= $active('/menu-trimestral') ?>"
            <?= $tip('Menú trimestral') ?>>
            <span class="sb-icon"><i class="bi bi-calendar3-week-fill"></i></span>
            <span class="sb-label">Menú trimestral</span>
        </a>
        <?php endif ?>

        <?php if (isset($modulos['reportes'])): ?>
        <a href="<?= APP_URL ?>/reportes"
            class="sb-item <?= $active('/reportes') ?>"
            <?= $tip('Reportes') ?>>
            <span class="sb-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></span>
            <span class="sb-label">Reportes</span>
        </a>
        <?php endif ?>

    </nav>

    <!-- 4. LOGOUT -->
    <div class="sb-footer">
        <a href="<?= APP_URL ?>/auth/logout"
            class="sb-logout"
            onclick="return confirm('¿Desea cerrar sesión?')"
            <?= $tip('Cerrar sesión') ?>>
            <i class="bi bi-box-arrow-right"></i>
            <span class="sb-label">Cerrar sesión</span>
        </a>
    </div>

</aside>

<main class="main-content" id="mainContent">

    <header class="topbar">
        <div class="topbar-left">
            <button class="topbar-toggle" id="topbarToggle">
                <i class="bi bi-list"></i>
            </button>
            <nav class="breadcrumb-nav" aria-label="breadcrumb">
                <?php if (!empty($breadcrumb)): ?>
                    <?php foreach ($breadcrumb as $i => $crumb): ?>
                        <?php if ($i < count($breadcrumb) - 1): ?>
                            <a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                            <span class="bc-sep"><i class="bi bi-chevron-right"></i></span>
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
            <span class="topbar-empresa">
                <i class="bi bi-building" style="margin-right:3px"></i>
                <?= EMPRESA_NOMBRE ?>
            </span>
            <span class="topbar-fecha">
                <i class="bi bi-clock" style="margin-right:3px"></i>
                <?= date('d/m/Y H:i') ?>
            </span>
            <a href="<?= APP_URL ?>/auth/logout"
                class="topbar-salir"
                onclick="return confirm('¿Desea cerrar sesión?')">
                <i class="bi bi-box-arrow-right"></i> Salir
            </a>
        </div>
    </header>

    <?php if (!empty($flashes)): ?>
    <div class="flash-container">
        <?php foreach ($flashes as $flash):
            $icon = match($flash['type']) {
                'success' => 'bi-check-circle-fill',
                'error'   => 'bi-x-circle-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                default   => 'bi-info-circle-fill',
            };
        ?>
        <div class="flash flash-<?= $flash['type'] ?>" role="alert">
            <span>
                <i class="bi <?= $icon ?>" style="margin-right:6px"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </span>
            <button class="flash-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="page-content">
        <?php /** @var string $content */ ?>
        <?= $content ?>
    </div>

</main>

<meta name="csrf-token" content="<?= $csrfToken ?>">
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>