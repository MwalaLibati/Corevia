<?php

declare(strict_types=1);

class PayrollTaxYear extends Model
{
    protected string $table = 'payroll_tax_years';
    protected bool $tenantScoped = true;

    public function listAll(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payroll_tax_years WHERE company_id = :cid ORDER BY starts_on DESC');
        $stmt->execute(['cid' => Tenant::id()]);

        return $stmt->fetchAll();
    }
}
