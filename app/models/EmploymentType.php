<?php

declare(strict_types=1);

class EmploymentType extends Model
{
    protected string $table = 'employment_types';
    protected bool $tenantScoped = true;

    public function generateNextCode(): string
    {
        if (!$this->tableExists()) {
            return 'ET001';
        }

        return $this->nextCode('ET');
    }

    public function active(): array
    {
        if (!$this->tableExists()) {
            return $this->fallback();
        }

        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT * FROM employment_types WHERE is_active = 1' . ($cid > 0 ? ' AND company_id = :cid' : '') . ' ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        $rows = $stmt->fetchAll();

        return $rows !== [] ? $rows : $this->fallback();
    }

    public function names(): array
    {
        return array_map(static fn(array $row): string => (string) $row['name'], $this->active());
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM employment_types WHERE (name LIKE :keyword OR code LIKE :keyword)$and ORDER BY sort_order ASC, name ASC"
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
        $cid = Tenant::id();
        $sql = 'SELECT id FROM employment_types WHERE name = :name' . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['name' => $name];
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

    private function tableExists(): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'employment_types'"
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function fallback(): array
    {
        return [
            ['name' => 'Permanent', 'code' => 'PERM', 'is_active' => 1],
            ['name' => 'Contract', 'code' => 'CONT', 'is_active' => 1],
            ['name' => 'Part-Time', 'code' => 'PART', 'is_active' => 1],
            ['name' => 'Temporary', 'code' => 'TEMP', 'is_active' => 1],
        ];
    }

    private function nextCode(string $prefix): string
    {
        $cid = Tenant::id();
        $sql = "SELECT code FROM employment_types WHERE code REGEXP :pattern"
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
