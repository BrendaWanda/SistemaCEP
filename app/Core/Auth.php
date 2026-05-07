<?php
// =============================================================================
//  SIACEP — Autenticación y control de acceso por roles
//  Archivo: app/Core/Auth.php
// =============================================================================

namespace App\Core;

class Auth
{
    const ROL_ADMIN      = 1;
    const ROL_GERENTE    = 2;
    const ROL_PRODUCCION = 3;
    const ROL_CALIDAD    = 4;

    const PERMISOS = [
        'm0_configuracion'     => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => null, self::ROL_CALIDAD => null],
        'usuarios'             => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => null, self::ROL_PRODUCCION => null, self::ROL_CALIDAD => null],
        'm1_recepcion_mp'      => [self::ROL_ADMIN => 'r',  self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'rw', self::ROL_CALIDAD => 'rw'],
        'm2_registro_proceso'  => [self::ROL_ADMIN => 'r',  self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => null, self::ROL_CALIDAD => 'rw'],
        'm3_mantenimiento'     => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'rw', self::ROL_CALIDAD => 'r'],
        'm4_seguimiento'       => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'rw', self::ROL_CALIDAD => 'r'],
        'm5_trazabilidad'      => [self::ROL_ADMIN => 'r',  self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'r',  self::ROL_CALIDAD => 'r'],
        'm6_spc'               => [self::ROL_ADMIN => 'r',  self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'r',  self::ROL_CALIDAD => 'r'],
        'm7_dashboard'         => [self::ROL_ADMIN => 'r',  self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'r',  self::ROL_CALIDAD => 'r'],
        'reportes'             => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => 'rw', self::ROL_PRODUCCION => 'rw', self::ROL_CALIDAD => 'rw'],
        'menu_trimestral'      => [self::ROL_ADMIN => 'rw', self::ROL_GERENTE => 'r',  self::ROL_PRODUCCION => 'r',  self::ROL_CALIDAD => null],
    ];

    // ── Login ─────────────────────────────────────────────────────────────────
    public static function login(string $email, string $password): bool
    {
        $db   = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT u.*, r.nombre AS rol_nombre
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            WHERE u.email = ? AND u.activo = 1
            LIMIT 1",
            [strtolower(trim($email))]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => $user['id'],
            'nombre'     => $user['nombre'],
            'apellidos'  => $user['apellidos'],
            'email'      => $user['email'],
            'rol_id'     => $user['rol_id'],
            'rol_nombre' => $user['rol_nombre'],
            'cargo'      => $user['cargo'],
        ];

        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();

        return true;
    }

    // ── Logout ────────────────────────────────────────────────────────────────
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // ── Check sesión activa ───────────────────────────────────────────────────
    public static function check(): bool
    {
        if (empty($_SESSION['user'])) return false;

        if (isset($_SESSION['last_activity'])) {
            if ((time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }
        $_SESSION['last_activity'] = time();
        return true;
    }

    // ── Require login — redirige al login si no hay sesión ───────────────────
    public static function require(): void
    {
        if (!self::check()) {
            // Guardar solo la ruta relativa (sin el basePath)
            // para evitar duplicación al redirigir después del login
            $basePath   = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
            $requestUri = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
            if ($basePath && str_starts_with($requestUri, $basePath)) {
                $requestUri = substr($requestUri, strlen($basePath));
            }
            $_SESSION['redirect_after_login'] = $requestUri ?: '/dashboard';

            header('Location: ' . rtrim(APP_URL, '/') . '/auth/login');
            exit;
        }
    }

    // ── Getters ───────────────────────────────────────────────────────────────
    public static function user(): array    { return $_SESSION['user'] ?? []; }
    public static function id(): ?int       { return $_SESSION['user']['id'] ?? null; }
    public static function rolId(): ?int    { return $_SESSION['user']['rol_id'] ?? null; }
    public static function nombreCompleto(): string
    {
        $u = self::user();
        return trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
    }

    // ── Permisos ──────────────────────────────────────────────────────────────
    public static function canAccess(string $modulo): bool
    {
        $rolId = self::rolId();
        if (!$rolId) return false;
        return (self::PERMISOS[$modulo][$rolId] ?? null) !== null;
    }

    public static function canWrite(string $modulo): bool
    {
        $rolId = self::rolId();
        if (!$rolId) return false;
        return (self::PERMISOS[$modulo][$rolId] ?? null) === 'rw';
    }

    public static function requireAccess(string $modulo): void
    {
        self::require();
        if (!self::canAccess($modulo)) {
            http_response_code(403);
            require VIEWS_PATH . '/layouts/error_403.php';
            exit;
        }
    }

    public static function requireWrite(string $modulo): void
    {
        self::require();
        if (!self::canWrite($modulo)) {
            http_response_code(403);
            require VIEWS_PATH . '/layouts/error_403.php';
            exit;
        }
    }

    public static function modulosAccesibles(): array
    {
        $rolId = self::rolId();
        if (!$rolId) return [];
        $accesibles = [];
        foreach (self::PERMISOS as $modulo => $roles) {
            if (($roles[$rolId] ?? null) !== null) {
                $accesibles[$modulo] = $roles[$rolId];
            }
        }
        return $accesibles;
    }

    public static function isAdmin(): bool      { return self::rolId() === self::ROL_ADMIN; }
    public static function isGerente(): bool    { return self::rolId() === self::ROL_GERENTE; }
    public static function isProduccion(): bool { return self::rolId() === self::ROL_PRODUCCION; }
    public static function isCalidad(): bool    { return self::rolId() === self::ROL_CALIDAD; }
}
