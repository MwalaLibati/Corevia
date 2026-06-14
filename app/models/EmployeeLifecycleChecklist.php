<?php

declare(strict_types=1);

class EmployeeLifecycleChecklist extends Model
{
    protected string $table = 'employee_checklist_items';
    protected bool $tenantScoped = true;

    private const DEFAULTS = [
        'Onboarding' => [
            'Employee profile verified',
            'NRC/ID document uploaded',
            'Bank details confirmed',
            'Contract prepared and approved',
            'Portal access issued',
            'Policy orientation completed',
            'Department handover completed',
        ],
        'Exit' => [
            'Termination/exit date confirmed',
            'Company property returned',
            'Portal access disabled',
            'Final dues calculated',
            'Exit interview completed',
            'Certificate/letter generated',
            'Payroll notified',
        ],
    ];

    public function ensureDefaults(int $employeeId, string $type): void
    {
        if (!isset(self::DEFAULTS[$type])) {
            return;
        }
        $existing = $this->db->prepare('SELECT COUNT(*) FROM employee_checklist_items WHERE company_id = :cid AND employee_id = :eid AND checklist_type = :type');
        $existing->execute(['cid' => Tenant::id(), 'eid' => $employeeId, 'type' => $type]);
        if ((int) $existing->fetchColumn() > 0) {
            return;
        }
        foreach (self::DEFAULTS[$type] as $index => $label) {
            $this->insert([
                'employee_id' => $employeeId,
                'checklist_type' => $type,
                'item_label' => $label,
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function forEmployee(int $employeeId, string $type): array
    {
        $this->ensureDefaults($employeeId, $type);
        $stmt = $this->db->prepare(
            'SELECT eci.*, u.full_name AS completed_by_name
             FROM employee_checklist_items eci
             LEFT JOIN users u ON u.id = eci.completed_by
             WHERE eci.company_id = :cid AND eci.employee_id = :eid AND eci.checklist_type = :type
             ORDER BY eci.sort_order ASC, eci.id ASC'
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId, 'type' => $type]);
        return $stmt->fetchAll();
    }

    public function toggle(int $id): void
    {
        $item = $this->find($id);
        if (!$item) {
            throw new RuntimeException('Checklist item not found.');
        }
        $done = (int) ($item['is_completed'] ?? 0) === 1 ? 0 : 1;
        $this->update($id, [
            'is_completed' => $done,
            'completed_by' => $done ? ((int) (current_user()['id'] ?? 0) ?: null) : null,
            'completed_at' => $done ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
