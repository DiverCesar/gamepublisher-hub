<?php
declare(strict_types=1);

try {
    $basePath = dirname(__DIR__);
    define('BASE_PATH', $basePath);

    /** Autoload de Composer */
    $autoload = BASE_PATH . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    /** Carga de variables de entorno (si existe el archivo físico) */
    if (class_exists(\Dotenv\Dotenv::class)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(BASE_PATH);
        if (file_exists(BASE_PATH . '/.env')) {
            $dotenv->safeLoad();
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    date_default_timezone_set('UTC');

    /** Helpers Globales */
    function env(string $key, mixed $default = null): mixed {
        if (array_key_exists($key, $_ENV)) return $_ENV[$key];
        $value = getenv($key);
        if ($value !== false) return $value;
        return $default;
    }

    function url(string $path = ''): string {
        $appUrl = rtrim((string) env('APP_URL', ''), '/');
        return $appUrl . '/' . ltrim($path, '/');
    }

    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }

    function redirect(string $path): never {
        header('Location: ' . url($path));
        exit;
    }

    /** Carga de Configuración y Rutas */
    $config = require BASE_PATH . '/config/config.php';
    require BASE_PATH . '/routes/web.php';

} catch (\Throwable $e) {
    // --- ESTO ATRAPARÁ CUALQUIER FALLO FATAL Y LO MOSTRARÁ SÍ O SÍ ---
    http_response_code(500);
    echo "<div style='background:#1e1e1e; color:#ff5555; padding:20px; font-family:monospace; border-radius:8px; margin:20px; word-wrap:break-word;'>";
    echo "<h2 style='color:#ff5555; margin-top:0;'>⚠️ ERROR CRÍTICO DETECTADO</h2>";
    echo "<strong>Mensaje:</strong> " . $e->getMessage() . "<br><br>";
    echo "<strong>Archivo:</strong> " . $e->getFile() . " <strong>(Línea " . $e->getLine() . ")</strong><br><br>";
    echo "<strong>Stack Trace:</strong><br><pre style='color:#ccc; overflow-x:auto;'>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    exit;
}
