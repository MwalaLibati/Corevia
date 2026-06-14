<?php

declare(strict_types=1);

/**
 * Application-level configuration.
 *
 * The app secret is used for reversible encryption of operational secrets that
 * the system must use later, such as SMTP app passwords.
 *
 * Preferred setup:
 * - Set LIBOSEC_APP_SECRET in the server environment,
 * - Keep C:\xampp\LibosecMs_app_secret.key outside the web root, or
 * - Keep config/app_secret.key protected by the app/root .htaccess rules.
 */
function app_secret_value(): string
{
    $localConfig = __DIR__ . DIRECTORY_SEPARATOR . 'server.php';
    if (is_file($localConfig)) {
        $serverConfig = require $localConfig;
        $configuredSecret = is_array($serverConfig) ? ($serverConfig['app_secret'] ?? '') : '';
        if (is_string($configuredSecret) && trim($configuredSecret) !== '') {
            return trim($configuredSecret);
        }
    }

    $envSecret = getenv('LIBOSEC_APP_SECRET');
    if (is_string($envSecret) && trim($envSecret) !== '') {
        return trim($envSecret);
    }

    $secretFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'LibosecMs_app_secret.key';
    if (is_file($secretFile)) {
        $fileSecret = trim((string) file_get_contents($secretFile));
        if ($fileSecret !== '') {
            return $fileSecret;
        }
    }

    $localSecretFile = __DIR__ . DIRECTORY_SEPARATOR . 'app_secret.key';
    if (is_file($localSecretFile)) {
        $fileSecret = trim((string) file_get_contents($localSecretFile));
        if ($fileSecret !== '') {
            return $fileSecret;
        }
    }

    throw new RuntimeException('Missing application secret. Set LIBOSEC_APP_SECRET or create the server-local secret file.');
}

define('APP_SECRET', app_secret_value());

const APP_VENDOR_NAME = 'Stonesoft IT Solutions';
const APP_PRODUCT_NAME = 'Stonesoft Payroll & HR';
const APP_PRODUCT_TAGLINE = 'Payroll, HR, contracts, and employee self-service';
const APP_PLATFORM_DOMAIN = 'stonesoft.local';
const APP_PUBLIC_URL = 'https://corevia.stonesoftzambia.com/';

define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL));
