<?php

declare(strict_types=1);

/**
 * Tenant resolver — determines the active company for every request.
 *
 * Resolution order:
 *  1. Production:  subdomain  (tenant.stonesoft.local -> slug = 'tenant')
 *  2. Development: ?__tenant=slug  query param  (persisted in session)
 *  3. Session:     previously-resolved slug stored in $_SESSION['__tenant_slug']
 */
class Tenant
{
    private static ?array $current = null;

    /**
     * Resolve and cache the active tenant for this request.
     * Must be called after Session::start().
     */
    public static function resolve(): void
    {
        if (self::$current !== null) {
            return;
        }

        // 1. Subdomain resolution (production)
        $host  = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $slug = $parts[0];
            // Reserved subdomains that are NOT tenant slugs
            if (!in_array($slug, ['www', 'admin', 'superadmin', 'api'], true)) {
                $company = self::findBySlug($slug);
                if ($company) {
                    self::setCurrent($company);
                    return;
                }
            }
        }

        // 2. Dev fallback: ?__tenant=slug  (sets session so you only type it once)
        $qSlug = trim((string) ($_GET['__tenant'] ?? ''));
        if ($qSlug !== '') {
            $company = self::findBySlug($qSlug);
            if ($company) {
                self::setCurrent($company);
                return;
            }
        }

        // 3. Session: slug persisted from a previous request
        $sSlug = (string) ($_SESSION['__tenant_slug'] ?? '');
        if ($sSlug !== '') {
            $company = self::findBySlug($sSlug);
            if ($company) {
                self::setCurrent($company);
                return;
            }
        }

        // No tenant resolved — platform landing / superadmin context
        self::$current = null;
    }

    /** Return the resolved company row, or null if not resolved. */
    public static function current(): ?array
    {
        return self::$current;
    }

    /** Return the resolved company ID, or 0 if not resolved. */
    public static function id(): int
    {
        return (int) (self::$current['id'] ?? 0);
    }

    /** Return the resolved company slug, or empty string. */
    public static function slug(): string
    {
        return (string) (self::$current['slug'] ?? '');
    }

    /** Manually set the current tenant (used after login). */
    public static function set(array $company): void
    {
        self::setCurrent($company);
    }

    /** Clear the current tenant (used on logout). */
    public static function clear(): void
    {
        self::$current = null;
        unset($_SESSION['__tenant_slug']);
    }

    /** Whether a tenant is active and valid. */
    public static function resolved(): bool
    {
        return self::$current !== null;
    }

    private static function setCurrent(array $company): void
    {
        self::$current = $company;
        $_SESSION['__tenant_slug'] = $company['slug'];
    }

    private static function findBySlug(string $slug): ?array
    {
        try {
            $stmt = db()->prepare(
                "SELECT * FROM companies WHERE slug = :slug AND is_active = 1 LIMIT 1"
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
