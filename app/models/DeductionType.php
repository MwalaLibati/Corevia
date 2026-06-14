<?php

declare(strict_types=1);

/**
 * Deduction type model for statutory/custom deductions.
 */

class DeductionType extends Model
{
    protected string $table = 'deduction_types';
    protected bool $tenantScoped = true;

    public function generateNextCode(): string
    {
        $cid = Tenant::id();
        $sql = "SELECT code FROM deduction_types WHERE code REGEXP :pattern"
             . ($cid > 0 ? ' AND company_id = :cid' : '')
             . ' ORDER BY code DESC LIMIT 1';
        $params = ['pattern' => '^DED[0-9]{3,}$'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, 3)) + 1 : 1;

        return 'DED' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function statutoryTypes(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM deduction_types WHERE is_statutory = 1$and ORDER BY name ASC");
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM deduction_types WHERE (name LIKE :keyword OR code LIKE :keyword)$and ORDER BY id DESC");
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function codeExists(?string $code, ?int $excludeId = null): bool
    {
        if ($code === null || $code === '') {
            return false;
        }
        $cid = Tenant::id();
        $sql = 'SELECT id FROM deduction_types WHERE code = :code'
             . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['code' => $code];
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

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM deduction_types WHERE name = :name'
             . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['name' => $name];
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
}
