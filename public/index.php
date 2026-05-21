<?php
declare(strict_types=1);

/**
 * Front controller
 * Todo el tráfico web entra por este archivo.
 */

// --- MODO DEPURACIÓN FORZADA ---
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// -------------------------------

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);

/**
 * Autoload de Composer si existe.
 */
$autoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/**
 * Carga de variables de entorno.
 */
if (class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(BASE_PATH);
    if (file_exists(BASE_PATH . '/.env')) {
        $dotenv->safeLoad();
    }
}

/**
 * Configuración básica del entorno.
 */
$appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';

ini_set('display_errors', $appEnv !== 'production' ? '1' : '0');
ini_set('display_startup_errors', $appEnv !== 'production' ? '1' : '0');
error_reporting(E_ALL);

/**
 * Sesión.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Zona horaria por defecto.
 */
date_default_timezone_set('UTC');

/**
 * Helpers globales mínimos.
 */
function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    return $default;
}

function url(string $path = ''): string
{
    $appUrl = rtrim((string) env('APP_URL', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $appUrl . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Carga de configuración.
 */
$config = require BASE_PATH . '/config/config.php';

/**
 * Carga de rutas.
 */
require BASE_PATH . '/routes/web.php';
