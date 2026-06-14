<?php

declare(strict_types=1);

class ClientEntity extends Model
{
    protected string $table = 'client_entities';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT ce.*,
                    COUNT(c.id) AS company_count,
                    COALESCE(SUM(company_staff.employee_count), 0) AS employee_count
             FROM client_entities ce
             LEFT JOIN companies c ON c.client_entity_id = ce.id
             LEFT JOIN (
                SELECT company_id, COUNT(*) AS employee_count
                FROM employees
                WHERE archived_at IS NULL
                GROUP BY company_id
             ) company_staff ON company_staff.company_id = c.id
             GROUP BY ce.id
             ORDER BY ce.name ASC"
        );

        return $stmt->fetchAll();
    }

    public function activeOptions(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name, code FROM client_entities WHERE is_active = 1 ORDER BY name ASC"
        );

        return $stmt->fetchAll();
    }

    public function companies(int $entityId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id AND e.archived_at IS NULL) AS employee_count,
                    (SELECT COUNT(*) FROM branches b WHERE b.company_id = c.id AND b.is_active = 1) AS branch_count
             FROM companies c
             WHERE c.client_entity_id = :entity_id
             ORDER BY c.name ASC"
        );
        $stmt->execute(['entity_id' => $entityId]);

        return $stmt->fetchAll();
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM client_entities WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromName(string $name): int
    {
        $name = trim($name);
        $existing = $this->findByName($name);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->insert([
            'name' => $name,
            'code' => $this->generateNextCode(),
            'is_active' => 1,
        ]);
    }

    public function generateNextCode(): string
    {
        $stmt = $this->db->prepare(
            "SELECT code FROM client_entities
             WHERE code REGEXP :pattern
             ORDER BY code DESC
             LIMIT 1"
        );
        $stmt->execute(['pattern' => '^ENT[0-9]{4,}$']);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, 3)) + 1 : 1;

        return 'ENT' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS client_entities (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL,
                code VARCHAR(40) NULL,
                entity_type VARCHAR(80) NOT NULL DEFAULT 'Group',
                contact_person VARCHAR(190) NULL,
                email VARCHAR(190) NULL,
                phone VARCHAR(80) NULL,
                address TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_client_entities_name (name),
                UNIQUE KEY uq_client_entities_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->addColumnIfMissing('companies', 'client_entity_id', 'BIGINT UNSIGNED NULL AFTER id');
        $this->addIndexIfMissing('companies', 'idx_companies_client_entity_id', 'client_entity_id');
        $this->seedExistingCompanies();
    }

    private function seedExistingCompanies(): void
    {
        $companies = $this->db->query(
            'SELECT id, name, email, phone FROM companies WHERE client_entity_id IS NULL ORDER BY id ASC'
        )->fetchAll();

        foreach ($companies as $company) {
            $existing = $this->findByName((string) $company['name']);
            $entityId = $existing
                ? (int) $existing['id']
                : $this->insert([
                    'name' => (string) $company['name'],
                    'code' => $this->generateNextCode(),
                    'entity_type' => 'Single Company',
                    'email' => $company['email'] ?? null,
                    'phone' => $company['phone'] ?? null,
                    'is_active' => 1,
                ]);
            $stmt = $this->db->prepare('UPDATE companies SET client_entity_id = :entity_id WHERE id = :id');
            $stmt->execute(['entity_id' => $entityId, 'id' => (int) $company['id']]);
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column"
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndexIfMissing(string $table, string $index, string $columns): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name"
        );
        $stmt->execute(['table' => $table, 'index_name' => $index]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        }
    }
}
