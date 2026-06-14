<?php

declare(strict_types=1);

/**
 * Application bootstrap and single entry point.
 */

const BASE_PATH = __DIR__ . '/..';

spl_autoload_register(static function (string $className): void {
    $paths = [
        BASE_PATH . '/app/controllers/' . $className . '.php',
        BASE_PATH . '/app/models/' . $className . '.php',
    ];

    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require BASE_PATH . '/helpers/common.php';
require BASE_PATH . '/helpers/auth.php';
require BASE_PATH . '/helpers/format.php';
require BASE_PATH . '/config/app.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Session.php';
require BASE_PATH . '/core/Model.php';
require BASE_PATH . '/core/SecretBox.php';
require BASE_PATH . '/core/MailService.php';
require BASE_PATH . '/core/Controller.php';
require BASE_PATH . '/core/App.php';
require BASE_PATH . '/core/Tenant.php';

$debug = defined('APP_DEBUG') && APP_DEBUG;
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

set_exception_handler(static function (Throwable $exception) use ($debug): void {
    error_log((string) $exception);
    http_response_code(500);

    if ($debug) {
        echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }

    echo 'Something went wrong while processing your request. Please contact support.';
});

register_shutdown_function(static function () use ($debug): void {
    $error = error_get_last();
    if ($error === null || !in_array((int) $error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log(sprintf(
        'Fatal error: %s in %s on line %d',
        (string) $error['message'],
        (string) $error['file'],
        (int) $error['line']
    ));

    if (!$debug && !headers_sent()) {
        http_response_code(500);
    }

    if (!$debug) {
        echo 'Something went wrong while processing your request. Please contact support.';
    }
});

Session::start();
Tenant::resolve();

$app = new App();
$app->run();
