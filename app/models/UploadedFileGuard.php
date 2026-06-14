<?php

declare(strict_types=1);

class UploadedFileGuard
{
    public const DOCUMENT_MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function validate(array $file, array $allowedMimes, int $maxBytes): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No file uploaded or the upload failed.');
        }

        if ((int) ($file['size'] ?? 0) <= 0) {
            throw new RuntimeException('The uploaded file is empty.');
        }

        if ((int) $file['size'] > $maxBytes) {
            throw new RuntimeException('The uploaded file exceeds the allowed size.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('The uploaded file could not be verified.');
        }

        $mime = function_exists('finfo_open')
            ? self::detectMimeWithFinfo($tmp)
            : (string) mime_content_type($tmp);

        if (!isset($allowedMimes[$mime])) {
            throw new RuntimeException('This file type is not allowed.');
        }

        if (str_starts_with($mime, 'image/') && @getimagesize($tmp) === false) {
            throw new RuntimeException('The uploaded image could not be validated.');
        }

        return $mime;
    }

    public static function safeStoredName(string $prefix, string $mime, array $allowedMimes): string
    {
        $extension = $allowedMimes[$mime] ?? 'bin';
        $safePrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefix) ?: 'upload';

        return trim($safePrefix, '_') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
    }

    private static function detectMimeWithFinfo(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return (string) mime_content_type($path);
        }

        $mime = (string) finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime;
    }
}
