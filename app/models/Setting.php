<?php

declare(strict_types=1);

/**
 * Setting model for system key-value configuration.
 */

class Setting extends Model
{
    protected string $table = 'settings';
    protected bool $tenantScoped = true;

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM settings WHERE (setting_key LIKE :keyword OR setting_value LIKE :keyword)$and ORDER BY id DESC");
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function keyExists(string $key, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM settings WHERE setting_key = :setting_key'
             . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['setting_key' => $key];
        if ($cid > 0) { $params['cid'] = $cid; }

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function value(string $key, string $default = ''): string
    {
        $cid = Tenant::id();
        $sql = 'SELECT setting_value FROM settings WHERE setting_key = :setting_key'
             . ($cid > 0 ? ' AND company_id = :cid' : '')
             . ' LIMIT 1';
        $params = ['setting_key' => $key];
        if ($cid > 0) { $params['cid'] = $cid; }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function numericValue(string $key, float $default = 0.0): float
    {
        $value = $this->value($key, (string) $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    public function upsert(string $key, string $value): void
    {
        $this->db->prepare(
            'INSERT INTO settings (company_id, setting_key, setting_value)
             VALUES (:cid, :setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([
            'cid' => Tenant::id(),
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }
}
