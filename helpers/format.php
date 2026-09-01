<?php

declare(strict_types=1);

/**
 * Formatting helpers for currencies and dates.
 */

function corevia_currency_options(): array
{
    return [
        'ZMW' => ['name' => 'Zambian Kwacha', 'symbol' => 'ZMW'],
        'USD' => ['name' => 'United States Dollar', 'symbol' => 'USD'],
        'EUR' => ['name' => 'Euro', 'symbol' => 'EUR'],
        'GBP' => ['name' => 'British Pound', 'symbol' => 'GBP'],
        'ZAR' => ['name' => 'South African Rand', 'symbol' => 'ZAR'],
    ];
}

function app_currency_code(?string $fallback = 'ZMW'): string
{
    static $cache = [];
    $tenantId = 0;
    if (class_exists('Tenant')) {
        try {
            $tenantId = (int) Tenant::id();
        } catch (Throwable) {
            $tenantId = 0;
        }
    }

    if (isset($cache[$tenantId])) {
        return $cache[$tenantId];
    }

    $currency = strtoupper(trim((string) $fallback));
    if (class_exists('Setting')) {
        try {
            $currency = strtoupper(trim((new Setting())->value('payroll_currency', $currency)));
        } catch (Throwable) {
            // Keep formatting available even when settings or database are unavailable.
        }
    }

    $options = corevia_currency_options();
    if (!isset($options[$currency])) {
        $currency = 'ZMW';
    }

    $cache[$tenantId] = $currency;
    return $currency;
}

function app_currency_name(?string $currency = null): string
{
    $currency = strtoupper(trim((string) ($currency ?? app_currency_code())));
    $options = corevia_currency_options();
    return (string) ($options[$currency]['name'] ?? $currency);
}

function format_currency(float $amount, ?string $currency = null): string
{
    $currency = $currency !== null && trim($currency) !== '' ? strtoupper(trim($currency)) : app_currency_code();
    return $currency . ' ' . number_format($amount, 2);
}

function format_date(string $date): string
{
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d M Y', $timestamp);
}
