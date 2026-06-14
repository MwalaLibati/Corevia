<?php

declare(strict_types=1);

class Department extends Model
{
    protected string $table = 'departments';
    protected bool $tenantScoped = true;

    public function generateNextCode(): string
    {
        return $this->nextCode('DEP');
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM departments WHERE (name LIKE :keyword OR code LIKE :keyword)$and ORDER BY name ASC"
        );
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        return $this->valueExists('name', $name, $excludeId);
    }

    public function codeExists(?string $code, ?int $excludeId = null): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return $this->valueExists('code', $code, $excludeId);
    }

    private function valueExists(string $column, string $value, ?int $excludeId): bool
    {
        $cid = Tenant::id();
        $sql = "SELECT id FROM departments WHERE {$column} = :value" . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['value' => $value];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    private function nextCode(string $prefix): string
    {
        $cid = Tenant::id();
        $sql = "SELECT code FROM departments WHERE code REGEXP :pattern"
             . ($cid > 0 ? ' AND company_id = :cid' : '')
             . ' ORDER BY code DESC LIMIT 1';
        $params = ['pattern' => '^' . $prefix . '[0-9]{3,}$'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
