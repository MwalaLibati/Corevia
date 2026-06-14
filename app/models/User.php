<?php

declare(strict_types=1);

/**
 * User model for authentication and role retrieval.
 */

class User extends Model
{
    protected string $table = 'users';
    protected bool $tenantScoped = false;

    public function roles(): array
    {
        return $this->db->query('SELECT id, name, description, COALESCE(access_level, name) AS access_level FROM roles ORDER BY name ASC')->fetchAll();
    }

    public function companyRoles(): array
    {
        $cid = Tenant::id();
        if ($cid > 0 && $this->columnExists('roles', 'company_id')) {
            $stmt = $this->db->prepare(
                'SELECT id, name, description, COALESCE(access_level, name) AS access_level FROM roles WHERE company_id IS NULL OR company_id = :cid ORDER BY name ASC'
            );
            $stmt->execute(['cid' => $cid]);
            return $stmt->fetchAll();
        }

        return $this->roles();
    }

    public function findActiveByEmail(string $email): ?array
    {
        $sql = 'SELECT u.*
                FROM users u
                WHERE u.email = :email
                  AND u.is_active = 1
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function activeMemberships(int $userId): array
    {
        $sql = 'SELECT m.*, c.name AS company_name, c.slug AS company_slug, c.logo_path,
                       r.name AS role_name, COALESCE(r.access_level, r.name) AS access_level
                FROM company_user_memberships m
                JOIN companies c ON c.id = m.company_id AND c.is_active = 1
                JOIN roles r ON r.id = m.role_id
                WHERE m.user_id = :uid AND m.is_active = 1
                ORDER BY m.is_default DESC, c.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);

        return $stmt->fetchAll();
    }

    public function membershipForCompany(int $userId, int $companyId): ?array
    {
        $sql = 'SELECT m.*, c.name AS company_name, c.slug AS company_slug, c.logo_path,
                       r.name AS role_name, COALESCE(r.access_level, r.name) AS access_level
                FROM company_user_memberships m
                JOIN companies c ON c.id = m.company_id AND c.is_active = 1
                JOIN roles r ON r.id = m.role_id
                WHERE m.user_id = :uid
                  AND m.company_id = :cid
                  AND m.is_active = 1
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId, 'cid' => $companyId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function listWithRoles(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE m.company_id = :cid AND m.is_active = 1' : '';
        $sql = "SELECT u.id, u.full_name, u.email, u.is_active, u.last_login_at,
                       GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles,
                       MIN(r.id) AS role_id
                FROM users u
                LEFT JOIN company_user_memberships m ON m.user_id = u.id
                LEFT JOIN roles r ON r.id = m.role_id
                $where
                GROUP BY u.id, u.full_name, u.email, u.is_active, u.last_login_at
                ORDER BY u.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function findWithRole(int $userId): ?array
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.is_active, u.last_login_at,
                       GROUP_CONCAT(r.id ORDER BY r.id SEPARATOR ",") AS role_ids,
                       GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ", ") AS roles,
                       MIN(r.id) AS role_id
                FROM users u
                LEFT JOIN user_roles ur ON ur.user_id = u.id
                LEFT JOIN roles r ON r.id = ur.role_id
                WHERE u.id = :id
                GROUP BY u.id, u.full_name, u.email, u.is_active, u.last_login_at
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findCompanyUser(int $userId): ?array
    {
        $cid = Tenant::id();
        $sql = 'SELECT u.id, u.full_name, u.email, u.is_active, u.last_login_at,
                       m.role_id, r.name AS role_name, m.is_active AS membership_active
                FROM users u
                JOIN company_user_memberships m ON m.user_id = u.id
                JOIN roles r ON r.id = m.role_id
                WHERE u.id = :id'
                . ($cid > 0 ? ' AND m.company_id = :cid' : '')
                . ' LIMIT 1';

        $params = ['id' => $userId];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function updateCompanyUser(int $userId, array $data, int $roleId, bool $membershipActive): void
    {
        $this->db->beginTransaction();
        try {
            $this->update($userId, $data);
            $this->assignRole($userId, $roleId);

            $cid = Tenant::id();
            if ($cid > 0) {
                $stmt = $this->db->prepare(
                    'UPDATE company_user_memberships
                     SET role_id = :rid, is_active = :active
                     WHERE company_id = :cid AND user_id = :uid'
                );
                $stmt->execute([
                    'rid' => $roleId,
                    'active' => $membershipActive ? 1 : 0,
                    'cid' => $cid,
                    'uid' => $userId,
                ]);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function createWithRole(array $data, int $roleId): int
    {
        $this->db->beginTransaction();

        try {
            $userId = $this->insert($data);
            $this->assignRole($userId, $roleId);
            $this->db->commit();

            return $userId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function updateWithRole(int $userId, array $data, int $roleId): bool
    {
        $this->db->beginTransaction();

        try {
            $updated = $this->update($userId, $data);
            $this->assignRole($userId, $roleId);
            $this->db->commit();

            return $updated;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function deleteWithRoles(int $userId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);

            $deleted = $this->delete($userId);
            $this->db->commit();

            return $deleted;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $delete = $this->db->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        $insert = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        $insert->execute(['user_id' => $userId, 'role_id' => $roleId]);

        $companyId = Tenant::id();
        if ($companyId <= 0) {
            $stmt = $this->db->prepare('SELECT company_id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $companyId = (int) $stmt->fetchColumn();
        }

        if ($companyId > 0) {
            $stmt = $this->db->prepare(
                'INSERT INTO company_user_memberships (company_id, user_id, role_id, is_default, is_active)
                 VALUES (:cid, :uid, :rid, 1, 1)
                 ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), is_active = 1'
            );
            $stmt->execute(['cid' => $companyId, 'uid' => $userId, 'rid' => $roleId]);
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
