<?php

declare(strict_types=1);

class Announcement extends Model
{
    protected string $table = 'announcements';
    protected bool $tenantScoped = true;

    public function listWithDetails(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE a.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS posted_by_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.posted_by
             $where
             ORDER BY a.created_at DESC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function published(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? 'AND a.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS posted_by_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.posted_by
             WHERE a.is_published = 1
               AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
               $and
             ORDER BY a.created_at DESC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }
}
