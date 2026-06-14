<?php

declare(strict_types=1);

class WorkflowEvent extends Model
{
    protected string $table = 'workflow_events';
    protected bool $tenantScoped = true;

    public static function record(string $workflowType, string $entityType, int $entityId, ?string $fromStatus, string $toStatus, string $action, ?string $notes = null): void
    {
        (new self())->insert([
            'workflow_type' => $workflowType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'notes' => $notes,
            'actor_user_id' => (int) (current_user()['id'] ?? 0) ?: null,
        ]);
    }

    public function forEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->db->prepare(
            'SELECT we.*, u.full_name AS actor_name
             FROM workflow_events we
             LEFT JOIN users u ON u.id = we.actor_user_id
             WHERE we.company_id = :cid AND we.entity_type = :entity_type AND we.entity_id = :entity_id
             ORDER BY we.id DESC'
        );
        $stmt->execute(['cid' => Tenant::id(), 'entity_type' => $entityType, 'entity_id' => $entityId]);

        return $stmt->fetchAll();
    }
}
