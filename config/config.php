<?php

declare(strict_types=1);

/**
 * Custom lightweight environment variable loader
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes if they exist
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load env
loadEnv(dirname(__DIR__) . '/.env');

return [
    'base_path' => dirname(__DIR__),

    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'CRX CRM',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        'trusted_proxies' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($_ENV['TRUSTED_PROXIES'] ?? ''))
        ), fn(string $p): bool => $p !== '')),
    ],
    'db' => [
        'connection' => $_ENV['DB_CONNECTION'] ?? 'sqlite',
        'host'       => $_ENV['DB_HOST']       ?? '127.0.0.1',
        'port'       => $_ENV['DB_PORT']       ?? '3306',
        'database'   => $_ENV['DB_DATABASE']   ?? (dirname(__DIR__) . '/storage/database.sqlite'),
        'username'   => $_ENV['DB_USERNAME']   ?? 'root',
        'password'   => $_ENV['DB_PASSWORD']   ?? '',
        'max_lifetime' => (int) ($_ENV['DB_MAX_LIFETIME'] ?? 3600),
        'read_write_split' => ($_ENV['DB_READ_WRITE_SPLIT'] ?? 'false') === 'true',
        'read' => [],
    ],
    'cache' => [
        'driver'      => $_ENV['CACHE_DRIVER']   ?? 'file',
        'path'        => $_ENV['CACHE_PATH']     ?? dirname(__DIR__) . '/storage/cache',
        'redis_host'  => $_ENV['REDIS_HOST']     ?? '127.0.0.1',
        'redis_port'  => $_ENV['REDIS_PORT']     ?? '6379',
        'redis_password' => $_ENV['REDIS_PASSWORD'] ?? '',
        'redis_db'    => $_ENV['REDIS_DB']       ?? '0',
    ],
    'queue' => [
        'driver' => $_ENV['QUEUE_DRIVER'] ?? 'database',
    ],
    'logging' => [
        'format'    => $_ENV['LOG_FORMAT']  ?? 'text',
        'channel'   => $_ENV['LOG_CHANNEL'] ?? 'crx',
        'min_level' => $_ENV['LOG_LEVEL']   ?? 'DEBUG',
    ],
    'rate_limit' => [
        'default_limit'  => (int) ($_ENV['RATE_LIMIT_DEFAULT'] ?? 120),
        'default_window' => (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
    ],
    'storage' => [
        'uploads' => $_ENV['UPLOAD_PATH'] ?? dirname(__DIR__) . '/public/uploads',
    ],
    'views' => [
        'cache_enabled' => ($_ENV['VIEW_CACHE_ENABLED'] ?? 'false') === 'true',
    ],
    'router' => [
        'cache_enabled' => ($_ENV['ROUTE_CACHE_ENABLED'] ?? 'false') === 'true',
        'cache_file'    => $_ENV['ROUTE_CACHE_FILE'] ?? dirname(__DIR__) . '/storage/cache/routes.php',
    ],
    'auth' => [
        'model' => 'App\\Models\\User',
    ],
    'locale' => [
        'default'  => $_ENV['DEFAULT_LOCALE'] ?? 'en',
        'fallback' => $_ENV['FALLBACK_LOCALE'] ?? 'en',
        'rtl'      => ['ar', 'he', 'fa', 'ur'],
    ],
    'mail' => [
        'driver'      => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host'        => $_ENV['MAIL_HOST'] ?? '127.0.0.1',
        'port'        => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'encryption'  => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'username'    => $_ENV['MAIL_USERNAME'] ?? '',
        'password'    => $_ENV['MAIL_PASSWORD'] ?? '',
        'from'        => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'notifications@crx.local',
            'name'    => $_ENV['MAIL_FROM_NAME'] ?? 'CRX CRM Alerts',
        ],
        'debug'       => ($_ENV['MAIL_DEBUG'] ?? 'false') === 'true',
    ],
    'ai' => [
        'provider'        => $_ENV['AI_PROVIDER'] ?? 'gemini',
        'gemini_api_key'  => $_ENV['GEMINI_API_KEY'] ?? '',
        'gemini_model'    => $_ENV['GEMINI_MODEL'] ?? 'gemini-flash-latest',
        'nvidia_api_key'  => $_ENV['NVIDIA_API_KEY'] ?? '',
        'nvidia_base_url' => $_ENV['NVIDIA_BASE_URL'] ?? 'https://integrate.api.nvidia.com/v1',
        'nvidia_model'    => $_ENV['NVIDIA_MODEL'] ?? 'deepseek-ai/deepseek-v4-flash-0731',
    ],
];
