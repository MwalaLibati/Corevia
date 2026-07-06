<?php

declare(strict_types=1);

/**
 * Zambia statutory tax and levy calculator.
 * Reads bands from tax_bands table; falls back to hard-coded 2024/25 bands.
 * Covers: PAYE, NAPSA (employee portion), NHIMA (employee portion).
 */

class TaxCalculator
{
    private static ?array $cachedBands       = null;
    private static ?array $cachedDeductions  = null;

    private const NAPSA_MAX_PENSIONABLE = 24436.00;

    private const FALLBACK_BANDS = [
        ['from' => 0.00,     'to' => 4800.00,  'rate' => 0.00],
        ['from' => 4800.01,  'to' => 9600.00,  'rate' => 0.25],
        ['from' => 9600.01,  'to' => 25600.00, 'rate' => 0.30],
        ['from' => 25600.01, 'to' => null,      'rate' => 0.375],
    ];

    private const FALLBACK_RATES = [
        'NAPSA' => ['rate' => 0.05, 'active' => true],
        'NHIMA' => ['rate' => 0.01, 'active' => true],
        'PAYE'  => ['rate' => 0.00, 'active' => true],
    ];

    /**
     * Load statutory config from deduction_types (PAYE/NAPSA/NHIMA rows).
     * Returns ['CODE' => ['rate' => float, 'active' => bool], ...]
     */
    private static function deductionConfig(): array
    {
        if (self::$cachedDeductions !== null) {
            return self::$cachedDeductions;
        }

        self::$cachedDeductions = self::FALLBACK_RATES;

        try {
            $cid = Tenant::id();
            $sql = "SELECT code, default_value, is_active FROM deduction_types WHERE code IN ('PAYE','NAPSA','NHIMA')";
            $params = [];
            if ($cid > 0) {
                $sql .= ' AND company_id = :cid';
                $params['cid'] = $cid;
            }
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $code = strtoupper((string) $row['code']);
                self::$cachedDeductions[$code] = [
                    'rate'   => (float) $row['default_value'] / 100,
                    'active' => (bool) (int) $row['is_active'],
                ];
            }
        } catch (Throwable) {}

        return self::$cachedDeductions;
    }

    public static function isEnabled(string $code): bool
    {
        return (bool) (self::deductionConfig()[strtoupper($code)]['active'] ?? false);
    }

    private static function bands(): array
    {
        if (self::$cachedBands !== null) {
            return self::$cachedBands;
        }

        try {
            $cid = Tenant::id();
            $sql = "SELECT band_from, band_to, rate_percent FROM tax_bands WHERE tax_name = 'PAYE'";
            $params = [];
            if ($cid > 0) {
                $sql .= ' AND company_id = :cid';
                $params['cid'] = $cid;
            }
            $sql .= ' ORDER BY band_from ASC';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                self::$cachedBands = array_map(static fn(array $r): array => [
                    'from' => (float) $r['band_from'],
                    'to'   => $r['band_to'] !== null ? (float) $r['band_to'] : null,
                    'rate' => (float) $r['rate_percent'] / 100,
                ], $rows);
                return self::$cachedBands;
            }
        } catch (Throwable) {}

        self::$cachedBands = self::FALLBACK_BANDS;
        return self::$cachedBands;
    }

    private static function deductionRate(string $code): float
    {
        return self::deductionConfig()[strtoupper($code)]['rate'] ?? 0.0;
    }

    /**
     * Calculate PAYE on gross pay using progressive tax bands.
     * Returns 0 if PAYE deduction type is disabled.
     */
    public static function paye(float $grossPay): float
    {
        if (!self::isEnabled('PAYE')) {
            return 0.0;
        }

        $tax       = 0.0;
        $remaining = $grossPay;

        foreach (self::bands() as $band) {
            if ($remaining <= 0) {
                break;
            }

            $bandStart = (float) $band['from'];
            $bandEnd   = $band['to'];
            $rate      = (float) $band['rate'];

            if ($grossPay <= $bandStart) {
                break;
            }

            $taxableInBand = $bandEnd !== null
                ? min($remaining, $bandEnd - $bandStart)
                : $remaining;

            $taxableInBand = max(0.0, min($taxableInBand, $grossPay - $bandStart));
            $tax += $taxableInBand * $rate;
            $remaining -= $taxableInBand;
        }

        return round($tax, 2);
    }

    /**
     * NAPSA employee contribution — rate read from deduction_types, capped at pensionable ceiling.
     * Returns 0 if NAPSA deduction type is disabled.
     */
    public static function napsa(float $basicPay): float
    {
        if (!self::isEnabled('NAPSA')) {
            return 0.0;
        }
        $rate        = self::deductionRate('NAPSA');
        $pensionable = min($basicPay, self::NAPSA_MAX_PENSIONABLE);
        return round($pensionable * $rate, 2);
    }

    /**
     * NHIMA employee contribution — rate read from deduction_types.
     * Returns 0 if NHIMA deduction type is disabled.
     */
    public static function nhima(float $grossPay): float
    {
        if (!self::isEnabled('NHIMA')) {
            return 0.0;
        }
        return round($grossPay * self::deductionRate('NHIMA'), 2);
    }

    /**
     * Returns all enabled statutory deductions as a labelled array.
     */
    public static function compute(float $grossPay, float $basicPay): array
    {
        $result = [];
        $paye   = self::paye($grossPay);
        $napsa  = self::napsa($basicPay);
        $nhima  = self::nhima($grossPay);

        if ($paye  > 0) $result[] = ['label' => 'PAYE',  'code' => 'PAYE',  'amount' => $paye,  'statutory' => true];
        if ($napsa > 0) $result[] = ['label' => 'NAPSA', 'code' => 'NAPSA', 'amount' => $napsa, 'statutory' => true];
        if ($nhima > 0) $result[] = ['label' => 'NHIMA', 'code' => 'NHIMA', 'amount' => $nhima, 'statutory' => true];

        return $result;
    }

    public static function employerContributions(float $grossPay, float $basicPay): array
    {
        $result = [];

        foreach (['NAPSA', 'NHIMA'] as $code) {
            if (!self::isEnabled($code)) {
                continue;
            }

            $rate = self::employerRate($code);
            if ($rate <= 0) {
                continue;
            }

            $base = $code === 'NAPSA'
                ? min($basicPay, self::NAPSA_MAX_PENSIONABLE)
                : $grossPay;

            $result[] = [
                'label' => $code . ' Employer',
                'code' => $code,
                'amount' => round($base * $rate, 2),
                'rate_percent' => $rate * 100,
                'base' => $base,
                'statutory' => true,
                'employer' => true,
            ];
        }

        return $result;
    }

    public static function total(float $grossPay, float $basicPay): float
    {
        return self::paye($grossPay) + self::napsa($basicPay) + self::nhima($grossPay);
    }

    private static function employerRate(string $code): float
    {
        try {
            $cid = Tenant::id();
            $sql = 'SELECT employer_rate FROM statutory_rates WHERE code = :code';
            $params = ['code' => strtoupper($code)];
            if ($cid > 0) {
                $sql .= ' AND company_id = :cid';
                $params['cid'] = $cid;
            }
            $sql .= ' AND effective_from <= CURDATE() AND (effective_to IS NULL OR effective_to >= CURDATE()) ORDER BY effective_from DESC LIMIT 1';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $rate = $stmt->fetchColumn();
            if ($rate !== false) {
                return (float) $rate / 100;
            }
        } catch (Throwable) {}

        return strtoupper($code) === 'NAPSA' ? self::deductionRate('NAPSA') : 0.0;
    }
}
