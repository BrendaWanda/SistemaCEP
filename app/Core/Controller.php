<?php
// =============================================================================
//  SIACEP — Controlador base
//  Archivo: app/Core/Controller.php
// =============================================================================

namespace App\Core;

abstract class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Renderizar una vista ──────────────────────────────────────────────────
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            if (APP_DEBUG) {
                die("Vista no encontrada: {$viewFile}");
            }
            http_response_code(500);
            exit;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    // ── Vista sin layout ──────────────────────────────────────────────────────
    protected function renderPlain(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    // ── Respuesta JSON ────────────────────────────────────────────────────────
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonSuccess(mixed $data = null, string $message = 'OK'): void
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function jsonError(string $message, int $code = 400, mixed $errors = null): void
    {
        $this->json(['success' => false, 'message' => $message, 'errors' => $errors], $code);
    }

    // ── Redirecciones ─────────────────────────────────────────────────────────
    /**
     * Redirige a una ruta del sistema.
     * Acepta rutas relativas (/dashboard) o URLs completas (http://...).
     * NUNCA duplica el basePath.
     */
    protected function redirect(string $path): void
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            // URL absoluta — usarla tal cual
            $url = $path;
        } else {
            // Ruta relativa — anteponer APP_URL una sola vez
            $url = rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
        }
        header('Location: ' . $url);
        exit;
    }

    protected function redirectBack(): void
    {
        $back = $_SERVER['HTTP_REFERER'] ?? APP_URL;
        // Evitar redirect a URLs externas
        if (!str_starts_with($back, APP_URL)) {
            $back = APP_URL;
        }
        header('Location: ' . $back);
        exit;
    }

    // ── Mensajes flash ────────────────────────────────────────────────────────
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = compact('type', 'message');
    }

    public static function getFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    // ── Redirección con flash ─────────────────────────────────────────────────
    protected function redirectWithSuccess(string $path, string $message): void
    {
        $this->flash('success', $message);
        $this->redirect($path);
    }

    protected function redirectWithError(string $path, string $message): void
    {
        $this->flash('error', $message);
        $this->redirect($path);
    }

    // ── Inputs ────────────────────────────────────────────────────────────────
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        // CORREGIDO: antes esto aplicaba htmlspecialchars() aquí mismo,
        // y las vistas TAMBIÉN aplican htmlspecialchars() al mostrar los
        // datos (patrón usado en absolutamente todas las vistas del
        // sistema) — esto causaba doble escape: un "&" guardado como
        // "&amp;" se volvía a escapar a "&amp;amp;" al mostrarse, y el
        // navegador terminaba mostrando literalmente "&amp;" en pantalla
        // en vez del símbolo "&". El escape de HTML debe pasar en la
        // SALIDA (que ya está cubierta en las vistas), no en la entrada.
        // La seguridad contra inyección SQL no depende de esto — todas
        // las consultas ya usan parámetros preparados (Database::query()).
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    protected function inputInt(string $key, int $default = 0): int
    {
        $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        return (int) $val;
    }

    protected function inputFloat(string $key, float $default = 0.0): float
    {
        $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        return (float) str_replace(',', '.', (string) $val);
    }

    protected function allInput(): array
    {
        $sanitized = [];
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    // ── CSRF ──────────────────────────────────────────────────────────────────
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            // Hallazgo de auditoría: esto SIEMPRE redirigía, incluso en
            // peticiones AJAX (fetch/JSON) — el navegador seguía la
            // redirección y el .json() del cliente fallaba con un error
            // confuso ("Unexpected token '<'") en vez de un mensaje claro
            // de "sesión expirada, recargue la página". Se detectó en
            // varios módulos durante esta auditoría pero no se podía
            // corregir sin tener este archivo.
            if ($this->isAjax()) {
                $this->jsonError('Token de seguridad inválido o sesión expirada. Recargue la página.', 419);
                return;
            }
            $this->flash('error', 'Token de seguridad inválido. Recargue la página.');
            $this->redirectBack();
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    protected function currentUser(): array  { return Auth::user(); }
    protected function currentUserId(): ?int { return Auth::id(); }
    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
    /**
     * true si la petición actual es a un endpoint /api/... (datos de
     * apoyo para selects dinámicos). Estos endpoints los consumen
     * formularios de otros módulos (ej. M1 usa /api/parametros-insumo,
     * M4 usa /api/recetas-por-producto), por lo que solo deben exigir
     * sesión iniciada, no permiso sobre el módulo dueño del controlador.
     */
    protected function isApiRequest(): bool
    {
        $uri      = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
        $basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        return str_starts_with($uri, '/api/');
    }

    /**
     * Control de acceso para el constructor de un controlador: en rutas
     * /api/ basta con estar autenticado; en el resto se exige acceso al
     * módulo indicado.
     */
    protected function requireModuloOApi(string $modulo): void
    {
        if ($this->isApiRequest()) {
            if (!Auth::check()) {
                $this->jsonError('Sesión expirada. Inicie sesión nuevamente.', 401);
            }
            return;
        }
        Auth::requireAccess($modulo);
    }

    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
    protected function isGet(): bool  { return $_SERVER['REQUEST_METHOD'] === 'GET'; }
}