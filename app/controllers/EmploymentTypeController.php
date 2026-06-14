<?php

declare(strict_types=1);

class EmploymentTypeController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new EmploymentType();
        $search = trim((string) $this->input('search', ''));

        $this->render('employment_types/index', [
            'title' => 'Employment Types',
            'types' => $search === '' ? $model->findAll() : $model->search($search),
            'search' => $search,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new EmploymentType();
        $this->render('employment_types/create', [
            'title' => 'Create Employment Type',
            'type' => null,
            'old' => $_SESSION['_old_employment_type_input'] ?? ['code' => $model->generateNextCode(), 'is_active' => 1],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_employment_type_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employment-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employment-type/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_employment_type_input'] = $data;
        $model = new EmploymentType();
        $error = $this->validate($model, $data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('employment-type/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_employment_type_input']);
            Session::flash('success', 'Employment type created successfully.');
            redirect('employment-type/index');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to create employment type: ' . $e->getMessage());
            redirect('employment-type/create');
        }
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new EmploymentType();
        $type = $model->find((int) $id);
        if (!$type) {
            Session::flash('error', 'Employment type not found.');
            redirect('employment-type/index');
        }

        $this->render('employment_types/create', [
            'title' => 'Edit Employment Type',
            'type' => $type,
            'old' => $_SESSION['_old_employment_type_input'] ?? $type,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);

        unset($_SESSION['_old_employment_type_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employment-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employment-type/edit/' . (int) $id);
        }

        $typeId = (int) $id;
        $model = new EmploymentType();
        if (!$model->find($typeId)) {
            Session::flash('error', 'Employment type not found.');
            redirect('employment-type/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_employment_type_input'] = $data;
        $error = $this->validate($model, $data, $typeId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('employment-type/edit/' . $typeId);
        }

        try {
            $model->update($typeId, $data);
            unset($_SESSION['_old_employment_type_input']);
            Session::flash('success', 'Employment type updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update employment type: ' . $e->getMessage());
        }

        redirect('employment-type/index');
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employment-type/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employment-type/index');
        }

        try {
            (new EmploymentType())->delete((int) $id);
            Session::flash('success', 'Employment type deleted successfully.');
        } catch (Throwable) {
            Session::flash('error', 'Employment type could not be deleted because it is in use.');
        }

        redirect('employment-type/index');
    }

    private function collectInput(): array
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new EmploymentType())->generateNextCode();
        }

        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => $code,
            'sort_order' => max(0, (int) $this->input('sort_order', 0)),
            'is_active' => (int) $this->input('is_active', 0) === 1 ? 1 : 0,
        ];
    }

    private function validate(EmploymentType $model, array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Employment type name is required.';
        }
        if ($model->nameExists((string) $data['name'], $excludeId)) {
            return 'An employment type with this name already exists for this company.';
        }

        return null;
    }
}
