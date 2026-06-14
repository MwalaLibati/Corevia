<?php

declare(strict_types=1);

/**
 * Reversible encryption helper for secrets that must be used by the app.
 */
class SecretBox
{
    private const PREFIX = 'enc:v1:';
    private const METHOD = 'AES-256-CBC';

    public static function encrypt(string $plainText): string
    {
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($plainText, self::METHOD, self::key(), OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) {
            throw new RuntimeException('Unable to encrypt secret.');
        }

        $mac = hash_hmac('sha256', $iv . $cipherText, self::key(), true);

        return self::PREFIX . base64_encode($iv . $mac . $cipherText);
    }

    public static function decryptOrPlain(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $payload = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) <= 48) {
            return '';
        }

        $iv = substr($payload, 0, 16);
        $mac = substr($payload, 16, 32);
        $cipherText = substr($payload, 48);
        $expectedMac = hash_hmac('sha256', $iv . $cipherText, self::key(), true);

        if (!hash_equals($expectedMac, $mac)) {
            return '';
        }

        $plainText = openssl_decrypt($cipherText, self::METHOD, self::key(), OPENSSL_RAW_DATA, $iv);

        return $plainText === false ? '' : $plainText;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    private static function key(): string
    {
        if (!defined('APP_SECRET') || trim((string) APP_SECRET) === '') {
            throw new RuntimeException('Missing application secret.');
        }

        $secret = (string) APP_SECRET;
        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded !== false && $decoded !== '') {
                $secret = $decoded;
            }
        }

        return hash('sha256', $secret, true);
    }
}
