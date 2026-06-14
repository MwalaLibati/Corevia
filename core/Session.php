<?php

declare(strict_types=1);

/**
 * Session management including flash and CSRF support.
 */

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            $secureCookie = filter_var(getenv('SESSION_SECURE') ?: ($https ? '1' : '0'), FILTER_VALIDATE_BOOL);

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secureCookie,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $timeout = (int) (getenv('SESSION_IDLE_TIMEOUT') ?: 1800);
        $now = time();
        if (!empty($_SESSION['_last_activity']) && $timeout > 0 && ($now - (int) $_SESSION['_last_activity']) > $timeout) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }

    public static function csrfToken(): string
    {
        return $_SESSION['_csrf_token'] ?? '';
    }

    public static function verifyCsrf(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        return is_string($sessionToken) && hash_equals($sessionToken, $token);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
