<?php

declare(strict_types=1);

/**
 * Salary structure management controller.
 */

class SalaryController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $model = new SalaryStructure();
        $search = trim((string) $this->input('search', ''));
        $structures = $search === '' ? $model->findAll() : $model->search($search);

        $this->render('salary/index', [
            'title' => 'Salary Management',
            'structures' => $structures,
            'search' => $search,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $this->render('salary/create', [
            'title' => 'Create Salary Structure',
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_salary_input'] ?? [],
        ]);

        unset($_SESSION['_old_salary_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('salary/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('salary/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_salary_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('salary/create');
        }

        $model = new SalaryStructure();
        if ($model->nameExists($data['name'])) {
            Session::flash('error', 'Salary structure name already exists.');
            redirect('salary/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_salary_input']);
            Session::flash('success', 'Salary structure created successfully.');
            redirect('salary/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create salary structure.');
            redirect('salary/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $structureId = (int) $id;
        if ($structureId <= 0) {
            Session::flash('error', 'Invalid salary structure id.');
            redirect('salary/index');
        }

        $model = new SalaryStructure();
        $structure = $model->find($structureId);

        if (!$structure) {
            Session::flash('error', 'Salary structure not found.');
            redirect('salary/index');
        }

        $this->render('salary/edit', [
            'title' => 'Edit Salary Structure',
            'structure' => $structure,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_salary_input'] ?? [],
        ]);

        unset($_SESSION['_old_salary_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('salary/index');
        }

        $structureId = (int) $id;
        if ($structureId <= 0) {
            Session::flash('error', 'Invalid salary structure id.');
            redirect('salary/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('salary/edit/' . $structureId);
        }

        $model = new SalaryStructure();
        $existing = $model->find($structureId);
        if (!$existing) {
            Session::flash('error', 'Salary structure not found.');
            redirect('salary/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_salary_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('salary/edit/' . $structureId);
        }

        if ($model->nameExists($data['name'], $structureId)) {
            Session::flash('error', 'Salary structure name already exists.');
            redirect('salary/edit/' . $structureId);
        }

        try {
            $model->update($structureId, $data);
            unset($_SESSION['_old_salary_input']);
            Session::flash('success', 'Salary structure updated successfully.');
            redirect('salary/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update salary structure.');
            redirect('salary/edit/' . $structureId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('salary/index');
        }

        $structureId = (int) $id;
        if ($structureId <= 0) {
            Session::flash('error', 'Invalid salary structure id.');
            redirect('salary/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('salary/index');
        }

        $model = new SalaryStructure();

        try {
            $model->delete($structureId);
            Session::flash('success', 'Salary structure deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete salary structure.');
        }

        redirect('salary/index');
    }

    private function collectInput(): array
    {
        return [
            'name' => trim((string) $this->input('name', '')),
            'grade_level' => $this->normalizeNullableString((string) $this->input('grade_level', '')),
            'basic_pay' => $this->normalizeMoney((string) $this->input('basic_pay', '0')),
            'housing_allowance' => $this->normalizeMoney((string) $this->input('housing_allowance', '0')),
            'transport_allowance' => $this->normalizeMoney((string) $this->input('transport_allowance', '0')),
            'other_allowances' => $this->normalizeMoney((string) $this->input('other_allowances', '0')),
        ];
    }

    private function validateInput(array $data): ?string
    {
        if ($data['name'] === '') {
            return 'Salary structure name is required.';
        }

        return null;
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeMoney(string $value): float
    {
        $normalized = trim($value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
