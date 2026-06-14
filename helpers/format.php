<?php

declare(strict_types=1);

/**
 * Formatting helpers for currencies and dates.
 */

function format_currency(float $amount, string $currency = 'ZMW'): string
{
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
