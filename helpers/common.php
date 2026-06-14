<?php

declare(strict_types=1);

/**
 * Generic helper functions for URLs, escaping, and redirects.
 */

function base_url(string $path = ''): string
{
    $publicBase = defined('APP_PUBLIC_URL') ? trim((string) APP_PUBLIC_URL) : '';
    if ($publicBase !== '' && should_use_configured_public_url()) {
        $normalizedPath = ltrim($path, '/');
        return rtrim($publicBase, '/') . ($normalizedPath === '' ? '/' : '/' . $normalizedPath);
    }

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = (string) preg_replace('#/public/index\.php$#', '', $scriptName);
    $basePath = rtrim($basePath, '/');

    $normalizedPath = ltrim($path, '/');

    if ($normalizedPath === '') {
        return ($basePath === '' ? '' : $basePath) . '/';
    }

    return ($basePath === '' ? '' : $basePath) . '/' . $normalizedPath;
}

function asset(string $path): string
{
    return base_url(ltrim($path, '/'));
}

function public_url(string $path = ''): string
{
    $base = defined('APP_PUBLIC_URL') ? (string) APP_PUBLIC_URL : '';
    $base = trim($base);

    if ($base === '' || !should_use_configured_public_url()) {
        return base_url($path);
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function should_use_configured_public_url(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    return !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function internal_app_url(?string $link, string $fallback = 'dashboard/index'): string
{
    $link = trim((string) $link);
    if ($link === '') {
        return base_url($fallback);
    }

    $publicBase = defined('APP_PUBLIC_URL') ? rtrim((string) APP_PUBLIC_URL, '/') : '';
    if ($publicBase !== '' && str_starts_with($link, $publicBase . '/LibosecMs/')) {
        $link = substr($link, strlen($publicBase . '/LibosecMs/'));
    }

    if (str_starts_with($link, '/LibosecMs/')) {
        $link = substr($link, strlen('/LibosecMs/'));
    }

    if (str_starts_with($link, '/')) {
        return base_url(ltrim($link, '/'));
    }

    if (preg_match('#^https?://#i', $link) === 1) {
        $parts = parse_url($link);
        $baseParts = $publicBase !== '' ? parse_url($publicBase) : [];
        if (($parts['host'] ?? '') === ($baseParts['host'] ?? '')) {
            $path = ltrim((string) ($parts['path'] ?? ''), '/');
            $query = isset($parts['query']) ? '?' . (string) $parts['query'] : '';
            return base_url($path . $query);
        }

        return base_url($fallback);
    }

    return base_url($link);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function app_vendor_name(): string
{
    return defined('APP_VENDOR_NAME') ? (string) APP_VENDOR_NAME : 'Stonesoft IT Solutions';
}

function app_product_name(): string
{
    return defined('APP_PRODUCT_NAME') ? (string) APP_PRODUCT_NAME : 'Stonesoft Payroll & HR';
}

function app_product_tagline(): string
{
    return defined('APP_PRODUCT_TAGLINE') ? (string) APP_PRODUCT_TAGLINE : 'Payroll, HR, contracts, and employee self-service';
}

function app_platform_domain(): string
{
    return defined('APP_PLATFORM_DOMAIN') ? (string) APP_PLATFORM_DOMAIN : 'stonesoft.local';
}

function company_logo_url(?array $company = null): string
{
    $company = $company ?? (function_exists('current_company') ? current_company() : null);
    $logoPath = trim((string) ($company['logo_path'] ?? ''));

    if ($logoPath !== '') {
        return asset($logoPath);
    }

    return asset('assets/img/Logo.png');
}
