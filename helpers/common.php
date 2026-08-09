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
    return asset('assets/img/stonesoft-email-logo.png');
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
    $address = e((string) ($options['address'] ?? 'Kitwe, Zambia'));
    $phone = e((string) ($options['phone'] ?? '+260 768 296216'));
    $email = e((string) ($options['email'] ?? corevia_sender_email()));

    return <<<HTML
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            a { color:#2563eb; word-break:break-word; overflow-wrap:anywhere; }
            .cv-content table { width:100%; table-layout:fixed; }
            .cv-content td { word-break:break-word; overflow-wrap:anywhere; }
            @media only screen and (max-width:520px) {
                .cv-wrap { padding:12px !important; }
                .cv-card { border-radius:10px !important; }
                .cv-header { padding:18px !important; text-align:center !important; }
                .cv-content { padding:18px !important; font-size:14px !important; }
                .cv-header-table, .cv-header-table tbody, .cv-header-table tr, .cv-header-logo, .cv-header-text { display:block !important; width:100% !important; }
                .cv-header-logo { padding:0 0 12px 0 !important; }
                .cv-header-logo img { margin:0 auto !important; max-width:82px !important; max-height:82px !important; }
                .cv-title { font-size:19px !important; line-height:1.25 !important; }
                .cv-content table, .cv-content tbody, .cv-content tr, .cv-content td { display:block !important; width:100% !important; box-sizing:border-box !important; }
                .cv-content td { padding:8px 10px !important; }
                .cv-content td:first-child { padding-bottom:2px !important; color:#475569 !important; }
                .cv-footer-table, .cv-footer-table tbody, .cv-footer-table tr, .cv-footer-logo, .cv-footer-text { display:block !important; width:100% !important; text-align:center !important; }
                .cv-footer-logo { padding:0 0 10px 0 !important; }
                .cv-footer-logo img { margin:0 auto !important; }
            }
        </style>
    </head>
    <body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a">
        <div class="cv-wrap" style="max-width:680px;margin:0 auto;padding:24px 14px">
            <div class="cv-card" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden">
                <div class="cv-header" style="background:#0f172a;color:#ffffff;padding:22px 24px">
                    <table class="cv-header-table" role="presentation" style="border-collapse:collapse;width:100%">
                        <tr>
                            <td class="cv-header-logo" style="width:82px;vertical-align:middle;padding-right:14px">
                                <img src="{$logo}" alt="Stonesoft logo" style="max-width:72px;max-height:72px;display:block;border:0">
                            </td>
                            <td class="cv-header-text" style="vertical-align:middle">
                                <h1 class="cv-title" style="font-size:22px;line-height:1.25;margin:0">{$titleHtml}</h1>
                                <p style="margin:8px 0 0;color:#cbd5e1;font-size:14px">{$introHtml}</p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="cv-content" style="padding:24px;line-height:1.6;font-size:15px">
                    {$bodyHtml}
                    <div style="border-top:1px solid #e5e7eb;margin-top:26px;padding-top:18px">
                        <table class="cv-footer-table" role="presentation" style="border-collapse:collapse;width:100%">
                            <tr>
                                <td class="cv-footer-logo" style="width:72px;vertical-align:top;padding-right:12px">
                                    <img src="{$logo}" alt="Stonesoft logo" style="max-width:58px;max-height:58px;display:block;border:0">
                                </td>
                                <td class="cv-footer-text" style="vertical-align:top;color:#334155;font-size:13px;line-height:1.5">
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
