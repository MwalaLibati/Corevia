<?php

declare(strict_types=1);

class WorkflowController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $this->render('workflows/index', [
            'title' => 'Workflow Settings',
            'definitions' => (new WorkflowDefinition())->allWithSteps(),
            'types' => WorkflowDefinition::TYPES,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        $definitionId = (int) $id;
        $model = new WorkflowDefinition();
        $definition = $model->find($definitionId);
        if (!$definition) {
            Session::flash('error', 'Workflow not found.');
            redirect('workflow/index');
        }

        $this->render('workflows/edit', [
            'title' => 'Edit Workflow',
            'definition' => $definition,
            'steps' => $model->steps($definitionId),
            'roles' => $this->roles(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        $definitionId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request.');
            redirect('workflow/edit/' . $definitionId);
        }

        $steps = [];
        $names = $_POST['step_name'] ?? [];
        $roles = $_POST['required_role'] ?? [];
        $labels = $_POST['action_label'] ?? [];
        if (is_array($names)) {
            foreach ($names as $index => $name) {
                $steps[] = [
                    'step_name' => (string) $name,
                    'required_role' => (string) ($roles[$index] ?? ''),
                    'action_label' => (string) ($labels[$index] ?? 'Approve'),
                ];
            }
        }

        if (count(array_filter($steps, static fn(array $step): bool => trim((string) $step['step_name']) !== '' && trim((string) $step['required_role']) !== '')) === 0) {
            Session::flash('error', 'At least one workflow step is required.');
            redirect('workflow/edit/' . $definitionId);
        }

        try {
            (new WorkflowDefinition())->updateDefinition($definitionId, [
                'name' => trim((string) $this->input('name', '')),
                'description' => trim((string) $this->input('description', '')),
                'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
            ], $steps);
            AuditLog::record('workflow_update', 'Updated workflow settings.', 'WorkflowDefinition', $definitionId);
            Session::flash('success', 'Workflow updated.');
            redirect('workflow/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Workflow update failed: ' . $e->getMessage());
            redirect('workflow/edit/' . $definitionId);
        }
    }

    private function roles(): array
    {
        $stmt = db()->prepare(
            "SELECT DISTINCT COALESCE(access_level, name) AS role_name
             FROM roles
             WHERE company_id = :cid OR company_id IS NULL
             ORDER BY role_name ASC"
        );
        $stmt->execute(['cid' => Tenant::id()]);
        $roles = array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        return array_values(array_unique(array_merge(['Super Admin', 'HR Officer', 'Finance Officer'], $roles)));
    }
}
