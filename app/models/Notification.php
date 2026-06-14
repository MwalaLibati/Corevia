<?php

declare(strict_types=1);

/**
 * In-app notification model.
 */

class Notification extends Model
{
    protected string $table = 'notifications';
    protected bool $tenantScoped = true;

    public function unreadCountForUser(int $userId): int
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND (company_id = :cid OR company_id IS NULL)' : '';
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id = :uid OR user_id IS NULL)$and"
        );
        $params = ['uid' => $userId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function recentForUser(int $userId, int $limit = 12): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND (company_id = :cid OR company_id IS NULL)' : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE (user_id = :uid OR user_id IS NULL)$and
             ORDER BY created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        if ($cid > 0) { $stmt->bindValue(':cid', $cid, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = :id AND (user_id = :uid OR user_id IS NULL)'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1 WHERE is_read = 0 AND (user_id = :uid OR user_id IS NULL)'
        );
        $stmt->execute(['uid' => $userId]);
    }

    public function createBroadcast(string $message, string $type = 'info', ?string $link = null): void
    {
        $cid = Tenant::id() > 0 ? Tenant::id() : null;
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (company_id, user_id, message, type, link) VALUES (:cid, NULL, :msg, :type, :link)'
        );
        $stmt->execute(['cid' => $cid, 'msg' => $message, 'type' => $type, 'link' => $link]);
    }

    public function createForUser(int $userId, string $message, string $type = 'info', ?string $link = null): void
    {
        $cid = Tenant::id() > 0 ? Tenant::id() : null;
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (company_id, user_id, message, type, link) VALUES (:cid, :uid, :msg, :type, :link)'
        );
        $stmt->execute(['cid' => $cid, 'uid' => $userId, 'msg' => $message, 'type' => $type, 'link' => $link]);
    }

    public function broadcastExists(string $message): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM notifications WHERE user_id IS NULL AND message = :msg AND created_at >= CURDATE() LIMIT 1"
        );
        $stmt->execute(['msg' => $message]);
        return (bool) $stmt->fetchColumn();
    }
}
