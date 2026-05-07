<?php
// =============================================================================
//  SIACEP — Punto de entrada único (Front Controller)
//  Archivo: public/index.php
// =============================================================================

define('DS', DIRECTORY_SEPARATOR);
$rootPath = dirname(__DIR__);

// 1. Configuración global
require $rootPath . '/config/app.php';

// 2. Autoloader PSR-4
spl_autoload_register(function (string $class) use ($rootPath): void {
    if (!str_starts_with($class, 'App\\')) return;
    $relative = str_replace(['App\\', '\\'], ['', DS], $class);
    $file     = $rootPath . DS . 'app' . DS . $relative . '.php';
    if (file_exists($file)) require_once $file;
});

// 3. Sesión
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 4. Router
use App\Core\Router;
use App\Core\Auth;

$router = new Router();
require CONFIG_PATH . '/routes.php';

// 5. Obtener URI limpia
// REQUEST_URI ejemplo: /siacep/public/auth/login?foo=bar
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Quitar query string
$requestUri = explode('?', $requestUri)[0];

// Quitar el basePath (/siacep/public) para que el router
// solo vea la parte relativa (/auth/login)
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}
$requestUri = $requestUri ?: '/';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 6. Middleware de autenticación
$esRutaPublica = str_starts_with($requestUri, '/auth');

if (!$esRutaPublica) {
    Auth::require();

    // Verificar permisos de módulo
    $permisosMapa    = require CONFIG_PATH . '/permisos.php';
    $moduloRequerido = null;
    foreach ($permisosMapa as $prefijo => $modulo) {
        if ($prefijo === null || $modulo === null) continue;
        if (str_starts_with($requestUri, $prefijo)) {
            $moduloRequerido = $modulo;
            break;
        }
    }
    if ($moduloRequerido && !Auth::canAccess($moduloRequerido)) {
        http_response_code(403);
        require VIEWS_PATH . '/layouts/error_403.php';
        exit;
    }
}

// 7. Despachar
$router->dispatch($requestUri, $method);