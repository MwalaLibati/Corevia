<?php

declare(strict_types=1);

class WorkflowDefinition extends Model
{
    protected string $table = 'workflow_definitions';
    protected bool $tenantScoped = true;

    public const TYPES = [
        'employee_onboarding' => 'Employee Onboarding',
        'payroll' => 'Payroll Run Approval',
        'leave' => 'Leave Approval',
        'contract' => 'Contract Approval',
        'salary_change' => 'Salary Change Approval',
        'salary_advance' => 'Salary Advance Approval',
        'employee_termination' => 'Employee Termination Approval',
    ];

    public function allWithSteps(): array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT * FROM workflow_definitions WHERE company_id = :cid ORDER BY name ASC'
        );
        $stmt->execute(['cid' => $cid]);
        $definitions = $stmt->fetchAll();

        foreach ($definitions as &$definition) {
            $definition['steps'] = $this->steps((int) $definition['id']);
        }

        return $definitions;
    }

    public function findByType(string $workflowType): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM workflow_definitions WHERE company_id = :cid AND workflow_type = :type AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['cid' => Tenant::id(), 'type' => $workflowType]);
        $definition = $stmt->fetch();
        if (!$definition) {
            return null;
        }
        $definition['steps'] = $this->steps((int) $definition['id']);
        return $definition;
    }

    public function steps(int $definitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM workflow_steps WHERE workflow_definition_id = :id ORDER BY step_order ASC'
        );
        $stmt->execute(['id' => $definitionId]);
        return $stmt->fetchAll();
    }

    public function updateDefinition(int $definitionId, array $data, array $steps): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE workflow_definitions
                 SET name = :name, description = :description, is_active = :is_active
                 WHERE id = :id AND company_id = :cid'
            );
            $stmt->execute([
                'name' => $data['name'],
                'description' => $data['description'],
                'is_active' => (int) $data['is_active'],
                'id' => $definitionId,
                'cid' => Tenant::id(),
            ]);

            $delete = $this->db->prepare('DELETE FROM workflow_steps WHERE workflow_definition_id = :id');
            $delete->execute(['id' => $definitionId]);

            $insert = $this->db->prepare(
                'INSERT INTO workflow_steps (workflow_definition_id, step_order, step_name, required_role, action_label, is_final)
                 VALUES (:definition_id, :step_order, :step_name, :required_role, :action_label, :is_final)'
            );
            $order = 1;
            foreach ($steps as $step) {
                if (trim((string) ($step['step_name'] ?? '')) === '' || trim((string) ($step['required_role'] ?? '')) === '') {
                    continue;
                }
                $insert->execute([
                    'definition_id' => $definitionId,
                    'step_order' => $order,
                    'step_name' => trim((string) $step['step_name']),
                    'required_role' => trim((string) $step['required_role']),
                    'action_label' => trim((string) ($step['action_label'] ?? 'Approve')) ?: 'Approve',
                    'is_final' => $order === count($steps) ? 1 : 0,
                ]);
                $order++;
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function firstStep(string $workflowType): ?array
    {
        $definition = $this->findByType($workflowType);
        return $definition['steps'][0] ?? null;
    }

    public function stepFor(string $workflowType, int $stepOrder): ?array
    {
        $definition = $this->findByType($workflowType);
        foreach (($definition['steps'] ?? []) as $step) {
            if ((int) ($step['step_order'] ?? 0) === $stepOrder) {
                return $step;
            }
        }

        return null;
    }

    public function stepCount(string $workflowType): int
    {
        $definition = $this->findByType($workflowType);
        return count($definition['steps'] ?? []);
    }

    public function requiredRoleFor(string $workflowType, int $stepOrder, string $fallbackRole): string
    {
        $step = $this->stepFor($workflowType, $stepOrder);
        $role = trim((string) ($step['required_role'] ?? ''));

        return $role !== '' ? $role : $fallbackRole;
    }

    public function actionLabelFor(string $workflowType, int $stepOrder, string $fallbackLabel = 'Approve'): string
    {
        $step = $this->stepFor($workflowType, $stepOrder);
        $label = trim((string) ($step['action_label'] ?? ''));

        return $label !== '' ? $label : $fallbackLabel;
    }

    public function canCurrentUserApprove(string $workflowType, int $stepOrder = 1): bool
    {
        $definition = $this->findByType($workflowType);
        $step = null;
        foreach (($definition['steps'] ?? []) as $candidate) {
            if ((int) $candidate['step_order'] === $stepOrder) {
                $step = $candidate;
                break;
            }
        }

        if (!$step) {
            return true;
        }

        $required = (string) $step['required_role'];
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        return $role === 'Super Admin'
            || $accessLevel === 'Super Admin'
            || $role === $required
            || $accessLevel === $required;
    }
}
