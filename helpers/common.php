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

function corevia_sender_email(): string
{
    return 'corevia@stonesoftzambia.com';
}

function corevia_brand_logo_url(): string
{
    return asset('assets/img/Logo.png');
}

function corevia_default_mail_settings(): array
{
    return [
        'email_notifications_enabled' => '1',
        'smtp_from_email' => corevia_sender_email(),
        'smtp_from_name' => 'Corevia HR & Payroll',
        'smtp_hr_email' => corevia_sender_email(),
    ];
}

function corevia_email_shell(string $title, string $intro, string $bodyHtml, array $options = []): string
{
    $titleHtml = e($title);
    $introHtml = e($intro);
    $product = e(app_product_name());
    $vendor = e(app_vendor_name());
    $logo = e(corevia_brand_logo_url());
    $address = e((string) ($options['address'] ?? 'Lusaka, Zambia'));
    $phone = e((string) ($options['phone'] ?? '+260 768 296216'));
    $email = e((string) ($options['email'] ?? corevia_sender_email()));

    return <<<HTML
    <html>
    <body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
        <div style="max-width:680px;margin:0 auto;padding:28px 18px">
            <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div style="background:#0f172a;color:#ffffff;padding:24px 28px">
                    <table role="presentation" style="border-collapse:collapse;width:100%">
                        <tr>
                            <td style="width:86px;vertical-align:middle">
                                <img src="{$logo}" alt="Stonesoft logo" style="max-width:72px;max-height:72px;display:block">
                            </td>
                            <td style="vertical-align:middle">
                                <h1 style="font-size:22px;line-height:1.25;margin:0">{$titleHtml}</h1>
                                <p style="margin:8px 0 0;color:#cbd5e1;font-size:14px">{$introHtml}</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="padding:28px;line-height:1.6;font-size:15px">
                    {$bodyHtml}
                    <div style="border-top:1px solid #e5e7eb;margin-top:26px;padding-top:18px">
                        <table role="presentation" style="border-collapse:collapse;width:100%">
                            <tr>
                                <td style="width:72px;vertical-align:top">
                                    <img src="{$logo}" alt="Stonesoft logo" style="max-width:58px;max-height:58px;display:block">
                                </td>
                                <td style="vertical-align:top;color:#334155;font-size:13px;line-height:1.5">
                                    <strong style="font-size:14px;color:#0f172a">{$vendor}</strong><br>
                                    {$product}<br>
                                    {$address}<br>
                                    {$phone} | {$email}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <p style="font-size:12px;color:#64748b;margin:18px 0 0">This is an automated Corevia notification. Please do not share temporary passwords with anyone.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    HTML;
}
