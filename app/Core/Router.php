<?php
// =============================================================================
//  SIACEP — Router
//  Archivo: app/Core/Router.php
//  Mapea URLs a controladores y métodos.
//  Soporta: GET, POST, parámetros dinámicos (:id, :codigo).
//  Uso en config/routes.php:
//      $router->get('/m1/recepcion', 'M1_RecepcionMP\RecepcionController@index');
//      $router->post('/m1/recepcion/guardar', 'M1_RecepcionMP\RecepcionController@guardar');
// =============================================================================

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $params = [];

    // ── Registro de rutas ─────────────────────────────────────────────────────
    public function get(string $uri, string $action): void
    {
        $this->add('GET', $uri, $action);
    }

    public function post(string $uri, string $action): void
    {
        $this->add('POST', $uri, $action);
    }

    private function add(string $method, string $uri, string $action): void
    {
        // Convertir parámetros dinámicos :id → named capture group
        $pattern = preg_replace('/:([a-zA-Z_]+)/', '(?P<$1>[^/]+)', $uri);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = compact('method', 'uri', 'pattern', 'action');
    }

    // ── Despacho de la petición actual ───────────────────────────────────────
    public function dispatch(string $uri, string $method): void
    {
        // Limpiar la URI: quitar query string y slash final
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        // Soportar _method en formularios HTML (PUT, DELETE via POST)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraer parámetros nombrados
                $this->params = array_filter(
                    $matches,
                    fn($k) => !is_numeric($k),
                    ARRAY_FILTER_USE_KEY
                );
                $this->callAction($route['action']);
                return;
            }
        }

        // Ruta no encontrada → 404
        http_response_code(404);
        require VIEWS_PATH . '/layouts/error_404.php';
    }

    // ── Llamada al controlador y método ──────────────────────────────────────
    private function callAction(string $action): void
    {
        // Formato esperado: 'SubCarpeta\NombreController@metodo'
        [$controllerPath, $method] = explode('@', $action);

        $class = 'App\\Controllers\\' . str_replace('/', '\\', $controllerPath);

        if (!class_exists($class)) {
            if (APP_DEBUG) {
                die("❌ Controlador no encontrado: {$class}");
            }
            http_response_code(500);
            require VIEWS_PATH . '/layouts/error_500.php';
            exit;
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            if (APP_DEBUG) {
                die("❌ Método no encontrado: {$class}::{$method}()");
            }
            http_response_code(500);
            require VIEWS_PATH . '/layouts/error_500.php';
            exit;
        }

        $controller->$method($this->params);
    }

    // ── Utilidad: obtener parámetros de la ruta actual ───────────────────────
    public function getParams(): array
    {
        return $this->params;
    }
}