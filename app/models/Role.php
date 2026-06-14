<?php

declare(strict_types=1);

class Role extends Model
{
    protected string $table = 'roles';
    protected bool $tenantScoped = true;

    public const ACCESS_LEVELS = ['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer'];

    public function companyRoles(): array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT r.*, COALESCE(rmp.module_count, 0) AS module_count
             FROM roles r
             LEFT JOIN (
                SELECT role_id, COUNT(module_key) AS module_count
                FROM role_module_permissions
                GROUP BY role_id
             ) rmp ON rmp.role_id = r.id
             WHERE r.company_id = :cid
             ORDER BY r.name ASC'
        );
        $stmt->execute(['cid' => $cid]);

        return $stmt->fetchAll();
    }

    public function findCompanyRole(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE id = :id AND company_id = :cid LIMIT 1');
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM roles WHERE name = :name AND company_id = :cid';
        $params = ['name' => $name, 'cid' => Tenant::id()];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function modulePermissions(int $roleId): array
    {
        $stmt = $this->db->prepare('SELECT module_key FROM role_module_permissions WHERE role_id = :role_id ORDER BY module_key ASC');
        $stmt->execute(['role_id' => $roleId]);

        return array_values(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }

    public function syncModulePermissions(int $roleId, array $moduleKeys): void
    {
        $catalog = module_catalog();
        $allowed = array_values(array_unique(array_filter(
            array_map('strval', $moduleKeys),
            static fn(string $key): bool => isset($catalog[$key])
        )));

        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM role_module_permissions WHERE role_id = :role_id')
                ->execute(['role_id' => $roleId]);

            if ($allowed !== []) {
                $stmt = $this->db->prepare(
                    'INSERT INTO role_module_permissions (role_id, module_key) VALUES (:role_id, :module_key)'
                );
                foreach ($allowed as $key) {
                    $stmt->execute(['role_id' => $roleId, 'module_key' => $key]);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
