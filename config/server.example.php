<?php

declare(strict_types=1);

/**
 * Copy this file to config/server.php on each server and fill in the real
 * values there. config/server.php is intentionally ignored by Git.
 */
return [
    'app_secret' => 'replace-with-a-long-random-production-secret',

    'database' => [
        'host' => '127.0.0.1',
        'name' => 'database_name',
        'user' => 'database_user',
        'pass' => 'database_password',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'email_notifications_enabled' => '1',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => 'system@example.com',
        'smtp_password' => 'smtp-app-password',
        'smtp_from_email' => 'system@example.com',
        'smtp_from_name' => 'Corevia',
        'smtp_hr_email' => 'hr@example.com',
    ],
];
