<?php
// =============================================================================
//  SIACEP — Configuración global de la aplicación
//  Archivo: config/app.php
//  Carga las variables del .env y define constantes globales del sistema.
// =============================================================================

// ── Carga del archivo .env ────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

// ── Helper para leer variables de entorno con fallback ────────────────────────
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// ── Constantes de la aplicación ───────────────────────────────────────────────
define('APP_NAME',    env('APP_NAME',    'SIACEP'));
define('APP_ENV',     env('APP_ENV',     'production'));
define('APP_DEBUG',   env('APP_DEBUG',   false));
define('APP_URL',     env('APP_URL',     'http://localhost/siacep/public'));
define('APP_KEY',     env('APP_KEY',     ''));

// ── Rutas del sistema de archivos ─────────────────────────────────────────────
define('ROOT_PATH',       dirname(__DIR__));
define('APP_PATH',        ROOT_PATH . '/app');
define('CONFIG_PATH',     ROOT_PATH . '/config');
define('PUBLIC_PATH',     ROOT_PATH . '/public');
define('VIEWS_PATH',      APP_PATH  . '/Views');
define('UPLOADS_PATH',    PUBLIC_PATH . '/uploads');

// ── Configuración de sesión ───────────────────────────────────────────────────
define('SESSION_NAME',     env('SESSION_NAME',     'SIACEP_SESSION'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 7200));

// ── Información de la empresa (para encabezados de reportes y formularios) ────
define('EMPRESA_NOMBRE',   'Industrias Alimenticias Gustossi SRL');
define('EMPRESA_RUC',      '');   // Completar con NIT real
define('EMPRESA_CIUDAD',   'La Paz, Bolivia');
define('EMPRESA_TELEFONO', '');
define('EMPRESA_EMAIL',    '');
define('EMPRESA_REGISTRO_SANITARIO', ''); // Para el SIREMU

// ── Parámetros del programa Desayuno Escolar ──────────────────────────────────
define('PROGRAMA_NOMBRE',  'Programa Municipal del Desayuno Escolar');
define('ENTIDAD_CONTRATANTE', 'GAMLP - Gobierno Autónomo Municipal de La Paz');
define('ENTIDAD_SUPERVISORA', 'SIREMU');
define('LOTE_CONTRATO',    'Lote N°2 - Secundaria y Educación Especial');

// ── Configuración SPC ─────────────────────────────────────────────────────────
// Constantes para gráficos de control X̄-R
// Valores de d2, D3, D4, A2 según tamaño de subgrupo n
// Fuente: Montgomery - Introduction to Statistical Quality Control
define('SPC_CONSTANTS', [
    2  => ['d2'=>1.128, 'D3'=>0,     'D4'=>3.267, 'A2'=>1.880],
    3  => ['d2'=>1.693, 'D3'=>0,     'D4'=>2.574, 'A2'=>1.023],
    4  => ['d2'=>2.059, 'D3'=>0,     'D4'=>2.282, 'A2'=>0.729],
    5  => ['d2'=>2.326, 'D3'=>0,     'D4'=>2.114, 'A2'=>0.577],
    6  => ['d2'=>2.534, 'D3'=>0,     'D4'=>2.004, 'A2'=>0.483],
    7  => ['d2'=>2.704, 'D3'=>0.076, 'D4'=>1.924, 'A2'=>0.419],
    8  => ['d2'=>2.847, 'D3'=>0.136, 'D4'=>1.864, 'A2'=>0.373],
    9  => ['d2'=>2.970, 'D3'=>0.184, 'D4'=>1.816, 'A2'=>0.337],
    10 => ['d2'=>3.078, 'D3'=>0.223, 'D4'=>1.777, 'A2'=>0.308],
]);

// ── Tolerancias del DBC ───────────────────────────────────────────────────────
define('DBC_TOLERANCIA_PCT', 1.00); // ±1% sobre peso nominal

// ── Configuración de errores (según entorno) ──────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Zona horaria Bolivia ──────────────────────────────────────────────────────
date_default_timezone_set('America/La_Paz');