<?php

declare(strict_types=1);

/**
 * Deductions and tax controller.
 */

class DeductionController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $model = new DeductionType();
        $search = trim((string) $this->input('search', ''));
        $types = $search === '' ? $model->findAll() : $model->search($search);

        $this->render('deductions/index', [
            'title' => 'Deductions & Tax',
            'types' => $types,
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

        $model = new DeductionType();
        $this->render('deductions/create', [
            'title' => 'Create Deduction Type',
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_deduction_input'] ?? ['code' => $model->generateNextCode(), 'is_active' => 1],
        ]);

        unset($_SESSION['_old_deduction_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('deduction/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('deduction/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_deduction_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('deduction/create');
        }

        $model = new DeductionType();
        if ($model->nameExists($data['name'])) {
            Session::flash('error', 'Deduction type name already exists.');
            redirect('deduction/create');
        }

        if ($model->codeExists($data['code'])) {
            Session::flash('error', 'Deduction code already exists.');
            redirect('deduction/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_deduction_input']);
            Session::flash('success', 'Deduction type created successfully.');
            redirect('deduction/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create deduction type.');
            redirect('deduction/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $typeId = (int) $id;
        if ($typeId <= 0) {
            Session::flash('error', 'Invalid deduction type id.');
            redirect('deduction/index');
        }

        $model = new DeductionType();
        $type = $model->find($typeId);

        if (!$type) {
            Session::flash('error', 'Deduction type not found.');
            redirect('deduction/index');
        }

        $this->render('deductions/edit', [
            'title' => 'Edit Deduction Type',
            'type' => $type,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_deduction_input'] ?? [],
        ]);

        unset($_SESSION['_old_deduction_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('deduction/index');
        }

        $typeId = (int) $id;
        if ($typeId <= 0) {
            Session::flash('error', 'Invalid deduction type id.');
            redirect('deduction/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('deduction/edit/' . $typeId);
        }

        $model = new DeductionType();
        $existing = $model->find($typeId);
        if (!$existing) {
            Session::flash('error', 'Deduction type not found.');
            redirect('deduction/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_deduction_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('deduction/edit/' . $typeId);
        }

        if ($model->nameExists($data['name'], $typeId)) {
            Session::flash('error', 'Deduction type name already exists.');
            redirect('deduction/edit/' . $typeId);
        }

        if ($model->codeExists($data['code'], $typeId)) {
            Session::flash('error', 'Deduction code already exists.');
            redirect('deduction/edit/' . $typeId);
        }

        try {
            $model->update($typeId, $data);
            unset($_SESSION['_old_deduction_input']);
            Session::flash('success', 'Deduction type updated successfully.');
            redirect('deduction/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update deduction type.');
            redirect('deduction/edit/' . $typeId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('deduction/index');
        }

        $typeId = (int) $id;
        if ($typeId <= 0) {
            Session::flash('error', 'Invalid deduction type id.');
            redirect('deduction/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('deduction/index');
        }

        $model = new DeductionType();

        try {
            $model->delete($typeId);
            Session::flash('success', 'Deduction type deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete deduction type.');
        }

        redirect('deduction/index');
    }

    private function collectInput(): array
    {
        return [
            'name'             => trim((string) $this->input('name', '')),
            'code'             => $this->deductionCodeInput(),
            'is_statutory'     => (int) $this->input('is_statutory', 0) === 1 ? 1 : 0,
            'auto_apply'       => (int) $this->input('auto_apply', 0) === 1 ? 1 : 0,
            'is_active'        => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
            'calculation_type' => (string) $this->input('calculation_type', 'Fixed'),
            'default_value'    => $this->normalizeMoney((string) $this->input('default_value', '0')),
        ];
    }

    private function validateInput(array $data): ?string
    {
        if ($data['name'] === '') {
            return 'Deduction type name is required.';
        }

        if (!in_array($data['calculation_type'], ['Fixed', 'Percent'], true)) {
            return 'Invalid calculation type selected.';
        }

        return null;
    }

    private function deductionCodeInput(): string
    {
        $code = $this->normalizeNullableString((string) $this->input('code', ''));

        return $code !== null ? $code : (new DeductionType())->generateNextCode();
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : strtoupper($trimmed);
    }

    private function normalizeMoney(string $value): float
    {
        $normalized = trim($value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
