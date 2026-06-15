<?php

declare(strict_types=1);

/**
 * Database configuration and PDO factory.
 */

function db_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return is_string($value) && $value !== '' ? $value : $default;
}

function db_server_config(): array
{
    if (!db_should_use_server_config()) {
        return [];
    }

    $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'server.php';
    if (!is_file($configFile)) {
        return [];
    }

    $config = require $configFile;
    if (!is_array($config)) {
        return [];
    }

    $database = $config['database'] ?? [];
    return is_array($database) ? $database : [];
}

function db_should_use_server_config(): bool
{
    $appEnv = strtolower((string) getenv('APP_ENV'));
    if (in_array($appEnv, ['production', 'prod'], true)) {
        return true;
    }

    if (in_array($appEnv, ['local', 'development', 'dev', 'testing', 'test'], true)) {
        return false;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return false;
    }

    if ($host !== '') {
        return true;
    }

    $configPath = str_replace('\\', '/', __DIR__);
    if (PHP_OS_FAMILY === 'Windows' || stripos($configPath, 'C:/xampp/') === 0) {
        return false;
    }

    return true;
}

$serverDb = db_server_config();

define('DB_HOST', db_env('DB_HOST', (string) ($serverDb['host'] ?? '127.0.0.1')));
define('DB_NAME', db_env('DB_NAME', (string) ($serverDb['name'] ?? 'school_payroll')));
define('DB_USER', db_env('DB_USER', (string) ($serverDb['user'] ?? 'root')));
define('DB_PASS', db_env('DB_PASS', (string) ($serverDb['pass'] ?? '')));
define('DB_CHARSET', db_env('DB_CHARSET', (string) ($serverDb['charset'] ?? 'utf8mb4')));

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
