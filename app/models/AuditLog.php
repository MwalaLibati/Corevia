<?php

declare(strict_types=1);

/**
 * Audit log model — records all significant system events.
 */

class AuditLog extends Model
{
    protected string $table = 'audit_logs';

    public static function record(
        string  $action,
        string  $description = '',
        ?string $model       = null,
        ?int    $recordId    = null,
        string  $userType    = 'admin',
        array   $context     = []
    ): void {
        try {
            $user   = $userType === 'employee' ? current_employee() : current_user();
            $userId = $userType === 'admin' && $user ? (int) ($user['id'] ?? 0) : null;
            $payload = [
                'description' => $description,
                'user_type'   => $userType,
                'model'       => $model,
                'record_id'   => $recordId,
                'context'     => self::sanitizeContext($context),
            ];

            if ($userType === 'employee' && $user) {
                $payload['employee_id'] = (int) ($user['id'] ?? 0);
            }

            $cid = Tenant::id() > 0 ? Tenant::id() : null;
            db()->prepare(
                "INSERT INTO audit_logs (company_id, user_id, module_name, action_name, entity_id, ip_address, user_agent, payload)
                 VALUES (:cid, :uid, :module, :action, :entity, :ip, :agent, :payload)"
            )->execute([
                'cid'     => $cid,
                'uid'     => $userId ?: null,
                'module'  => $model ?: $action,
                'action'  => $action,
                'entity'  => $recordId !== null ? (string) $recordId : null,
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent'   => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
                'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable) {}
    }

    public static function recordChanges(string $action, string $description, ?string $model, ?int $recordId, array $before, array $after, string $userType = 'admin'): void
    {
        self::record($action, $description, $model, $recordId, $userType, [
            'changes' => self::diff($before, $after),
        ]);
    }

    public static function recordPlatform(string $action, string $description = '', ?string $model = null, ?int $recordId = null): void
    {
        try {
            $admin = current_superadmin();
            $payload = [
                'description' => $description,
                'user_type' => 'platform_admin',
                'platform_admin_id' => $admin ? (int) ($admin['id'] ?? 0) : null,
                'platform_admin_email' => $admin ? (string) ($admin['email'] ?? '') : null,
                'model' => $model,
                'record_id' => $recordId,
            ];

            db()->prepare(
                "INSERT INTO audit_logs (company_id, user_id, module_name, action_name, entity_id, ip_address, user_agent, payload)
                 VALUES (NULL, NULL, :module, :action, :entity, :ip, :agent, :payload)"
            )->execute([
                'module' => $model ?: $action,
                'action' => $action,
                'entity' => $recordId !== null ? (string) $recordId : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
                'payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable) {}
    }

    public function recent(int $limit = 100): array
    {
        return $this->search([], $limit);
    }

    public function search(array $filters = [], int $limit = 200): array
    {
        $cid = Tenant::id();
        $where = [];
        $params = [];
        if ($cid > 0) {
            $where[] = 'al.company_id = :cid';
            $params['cid'] = $cid;
        }
        if (!empty($filters['action'])) {
            $where[] = 'al.action_name LIKE :action';
            $params['action'] = '%' . (string) $filters['action'] . '%';
        }
        if (!empty($filters['module'])) {
            $where[] = 'al.module_name LIKE :module';
            $params['module'] = '%' . (string) $filters['module'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(al.created_at) >= :date_from';
            $params['date_from'] = (string) $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(al.created_at) <= :date_to';
            $params['date_to'] = (string) $filters['date_to'];
        }
        $and = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare(
            "SELECT al.*, u.full_name AS admin_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             $and
             ORDER BY al.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll());
    }

    public function forModel(string $model, int $recordId): array
    {
        $stmt = $this->db->prepare(
            "SELECT al.*, u.full_name AS admin_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.module_name = :model AND al.entity_id = :rid
             ORDER BY al.created_at DESC"
        );
        $stmt->execute(['model' => $model, 'rid' => (string) $recordId]);
        return $this->normalizeRows($stmt->fetchAll());
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $payload = [];
            if (!empty($row['payload'])) {
                $decoded = json_decode((string) $row['payload'], true);
                $payload = is_array($decoded) ? $decoded : [];
            }

            $row['action'] = (string) ($row['action_name'] ?? '');
            $row['model'] = (string) ($payload['model'] ?? $row['module_name'] ?? '');
            $row['record_id'] = $payload['record_id'] ?? ($row['entity_id'] ?? null);
            $row['description'] = (string) ($payload['description'] ?? '');
            $row['user_type'] = (string) ($payload['user_type'] ?? 'admin');
            $row['context'] = $payload['context'] ?? [];
            $row['employee_name'] = null;
            $row['employee_number'] = null;
            if ($row['user_type'] === 'employee' && !empty($payload['employee_id'])) {
                $empStmt = $this->db->prepare('SELECT full_name, employee_number FROM employees WHERE id = :id LIMIT 1');
                $empStmt->execute(['id' => (int) $payload['employee_id']]);
                $employee = $empStmt->fetch();
                if ($employee) {
                    $row['employee_name'] = (string) ($employee['full_name'] ?? '');
                    $row['employee_number'] = (string) ($employee['employee_number'] ?? '');
                }
            }
        }

        return $rows;
    }

    private static function diff(array $before, array $after): array
    {
        $hidden = ['password', 'portal_password_hash', 'smtp_password_encrypted'];
        $changes = [];
        foreach ($after as $key => $newValue) {
            if (in_array((string) $key, $hidden, true)) {
                continue;
            }
            $oldValue = $before[$key] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                $changes[(string) $key] = [
                    'from' => is_scalar($oldValue) || $oldValue === null ? $oldValue : '[complex]',
                    'to' => is_scalar($newValue) || $newValue === null ? $newValue : '[complex]',
                ];
            }
        }

        return $changes;
    }

    private static function sanitizeContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (stripos((string) $key, 'password') !== false || stripos((string) $key, 'secret') !== false) {
                $context[$key] = '[hidden]';
            } elseif (is_array($value)) {
                $context[$key] = self::sanitizeContext($value);
            }
        }

        return $context;
    }
}
