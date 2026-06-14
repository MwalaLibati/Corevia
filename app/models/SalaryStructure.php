<?php

declare(strict_types=1);

/**
 * Salary structure model for pay configuration.
 */

class SalaryStructure extends Model
{
    protected string $table = 'salary_structures';
    protected bool $tenantScoped = true;

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM salary_structures WHERE (name LIKE :keyword OR grade_level LIKE :keyword)$and ORDER BY id DESC");
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM salary_structures WHERE name = :name'
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
