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
];
