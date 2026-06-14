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

define('DB_HOST', db_env('DB_HOST', '127.0.0.1'));
define('DB_NAME', db_env('DB_NAME', 'school_payroll'));
define('DB_USER', db_env('DB_USER', 'root'));
define('DB_PASS', db_env('DB_PASS', ''));
define('DB_CHARSET', db_env('DB_CHARSET', 'utf8mb4'));

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
