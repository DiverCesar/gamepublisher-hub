<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => env('APP_NAME', 'GamePublisherHub'),
        'env' => env('APP_ENV', 'production'),
        'url' => rtrim((string) env('APP_URL', ''), '/'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    ],

'db' => [
    'driver' => env('DB_CONNECTION', 'pgsql'),
    'host' => env('DB_HOST', ''),
    'port' => (int) env('DB_PORT', 5432),
    'database' => env('DB_DATABASE', ''),
    'username' => env('DB_USERNAME', ''),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'sslmode' => env('DB_SSLMODE', 'require'),
],

'uploads' => [
    'max_size' => (int) env('UPLOAD_MAX_SIZE', 5242880), // 5 MB
    'allowed_image_mimes' => [
        'image/jpeg',
'image/png',
'image/webp',
    ],
'public_disk' => BASE_PATH . '/public/uploads',
'storage_disk' => BASE_PATH . '/storage/uploads',
],
];
