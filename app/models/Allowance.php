<?php

declare(strict_types=1);

class Allowance extends Model
{
    protected string $table = 'allowance_types';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
        $this->migrateLegacyAllowances();
    }

    public function ensureSchema(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS allowance_types (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL, code VARCHAR(30) NOT NULL, calculation_type VARCHAR(20) NOT NULL DEFAULT 'Fixed',
            default_value DECIMAL(14,2) NOT NULL DEFAULT 0, is_taxable TINYINT(1) NOT NULL DEFAULT 1,
            included_in_gross TINYINT(1) NOT NULL DEFAULT 1, included_in_napsa TINYINT(1) NOT NULL DEFAULT 0,
            included_in_nhima TINYINT(1) NOT NULL DEFAULT 1, is_recurring TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_allowance_company_code (company_id, code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->exec("CREATE TABLE IF NOT EXISTS salary_structure_allowances (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
            salary_structure_id BIGINT UNSIGNED NOT NULL, allowance_type_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0, effective_from DATE NULL, effective_to DATE NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_structure_allowance (salary_structure_id, allowance_type_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->exec("CREATE TABLE IF NOT EXISTS employee_allowance_overrides (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
            employee_id BIGINT UNSIGNED NOT NULL, allowance_type_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(14,2) NULL, is_excluded TINYINT(1) NOT NULL DEFAULT 0,
            effective_from DATE NULL, effective_to DATE NULL, reason TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->exec("CREATE TABLE IF NOT EXISTS payroll_item_earnings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
            payroll_run_id BIGINT UNSIGNED NOT NULL, payroll_item_id BIGINT UNSIGNED NOT NULL,
            employee_id BIGINT UNSIGNED NOT NULL, earning_code VARCHAR(30) NOT NULL,
            earning_name VARCHAR(120) NOT NULL, earning_category VARCHAR(30) NOT NULL,
            calculation_type VARCHAR(30) NULL, calculation_base DECIMAL(14,2) NOT NULL DEFAULT 0,
            rate_percent DECIMAL(8,4) NULL, amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            meta_json LONGTEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_payroll_item_earnings_item (payroll_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function active(): array
    {
        return $this->findAll(['is_active' => 1]);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM allowance_types WHERE company_id = :cid AND code = :code';
        $params = ['cid' => Tenant::id(), 'code' => strtoupper($code)];
        if ($excludeId) { $sql .= ' AND id != :exclude'; $params['exclude'] = $excludeId; }
        $stmt = $this->db->prepare($sql . ' LIMIT 1'); $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function structureAssignments(int $structureId): array
    {
        $stmt = $this->db->prepare('SELECT ssa.*, at.name, at.code, at.calculation_type, at.default_value,
            at.is_taxable, at.included_in_gross, at.included_in_napsa, at.included_in_nhima
            FROM salary_structure_allowances ssa JOIN allowance_types at ON at.id=ssa.allowance_type_id
            WHERE ssa.company_id=:cid AND ssa.salary_structure_id=:sid AND ssa.is_active=1 ORDER BY at.name');
        $stmt->execute(['cid'=>Tenant::id(),'sid'=>$structureId]); return $stmt->fetchAll();
    }

    public function syncStructure(int $structureId, array $selectedIds, array $amounts): void
    {
        $cid=Tenant::id();
        $this->db->prepare('DELETE FROM salary_structure_allowances WHERE company_id=:cid AND salary_structure_id=:sid')->execute(['cid'=>$cid,'sid'=>$structureId]);
        $insert=$this->db->prepare('INSERT INTO salary_structure_allowances (company_id,salary_structure_id,allowance_type_id,amount,is_active)
            SELECT :cid,:sid,id,:amount,1 FROM allowance_types WHERE id=:aid AND company_id=:owner_cid AND is_active=1');
        foreach(array_unique(array_map('intval',$selectedIds)) as $aid){ if($aid<=0)continue; $insert->execute(['cid'=>$cid,'sid'=>$structureId,'aid'=>$aid,'owner_cid'=>$cid,'amount'=>max(0,(float)($amounts[$aid]??0))]); }
    }

    public function calculateForEmployee(int $employeeId, int $structureId, float $basicPay, string $date, float $factor=1.0): array
    {
        $stmt=$this->db->prepare("SELECT ssa.*,at.name,at.code,at.calculation_type,at.default_value,at.is_taxable,at.included_in_gross,at.included_in_napsa,at.included_in_nhima
            FROM salary_structure_allowances ssa JOIN allowance_types at ON at.id=ssa.allowance_type_id
            WHERE ssa.company_id=:cid AND ssa.salary_structure_id=:sid AND ssa.is_active=1 AND at.is_active=1
            AND (ssa.effective_from IS NULL OR ssa.effective_from<=:d1) AND (ssa.effective_to IS NULL OR ssa.effective_to>=:d2)");
        $stmt->execute(['cid'=>Tenant::id(),'sid'=>$structureId,'d1'=>$date,'d2'=>$date]); $rows=$stmt->fetchAll();
        $over=$this->db->prepare("SELECT * FROM employee_allowance_overrides WHERE company_id=:cid AND employee_id=:eid AND is_active=1 AND (effective_from IS NULL OR effective_from<=:d1) AND (effective_to IS NULL OR effective_to>=:d2)");
        $over->execute(['cid'=>Tenant::id(),'eid'=>$employeeId,'d1'=>$date,'d2'=>$date]); $overrides=[]; foreach($over->fetchAll() as $o)$overrides[(int)$o['allowance_type_id']]=$o;
        $lines=[];
        foreach($rows as $row){$o=$overrides[(int)$row['allowance_type_id']]??null;if($o&&$o['is_excluded'])continue;$value=$o&&$o['amount']!==null?(float)$o['amount']:(float)$row['amount'];$amount=$row['calculation_type']==='Percent'?$basicPay*($value/100):$value;$amount=round($amount*$factor,2);$lines[]=['code'=>$row['code'],'name'=>$row['name'],'category'=>'allowance','calculation_type'=>$row['calculation_type'],'base'=>$row['calculation_type']==='Percent'?$basicPay:$value,'rate_percent'=>$row['calculation_type']==='Percent'?$value:null,'amount'=>$amount,'included_in_gross'=>(int)$row['included_in_gross'],'is_taxable'=>(int)$row['is_taxable'],'included_in_napsa'=>(int)$row['included_in_napsa'],'included_in_nhima'=>(int)$row['included_in_nhima']];}
        return $lines;
    }

    private function migrateLegacyAllowances(): void
    {
        $cid=Tenant::id(); if($cid<=0)return;
        $defs=['HOUSE'=>['Housing Allowance','housing_allowance'],'TRANS'=>['Transport Allowance','transport_allowance'],'OTHER'=>['Other Allowance','other_allowances']];
        foreach($defs as $code=>[$name,$column]){$stmt=$this->db->prepare("INSERT INTO allowance_types (company_id,name,code,calculation_type,default_value) VALUES (:cid,:name,:code,'Fixed',0) ON DUPLICATE KEY UPDATE name=VALUES(name)");$stmt->execute(['cid'=>$cid,'name'=>$name,'code'=>$code]);$find=$this->db->prepare('SELECT id FROM allowance_types WHERE company_id=:cid AND code=:code');$find->execute(['cid'=>$cid,'code'=>$code]);$aid=(int)$find->fetchColumn();$copy=$this->db->prepare("INSERT IGNORE INTO salary_structure_allowances (company_id,salary_structure_id,allowance_type_id,amount,is_active) SELECT company_id,id,:aid,{$column},1 FROM salary_structures WHERE company_id=:cid AND {$column}>0");$copy->execute(['aid'=>$aid,'cid'=>$cid]);}
    }
}
