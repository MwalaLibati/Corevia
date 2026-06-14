<?php

declare(strict_types=1);

class SuperadminClientEntityController extends Controller
{
    public function index(): void
    {
        require_superadmin();
        $model = new ClientEntity();

        $this->renderSuperAdmin('superadmin/client_entities/index', [
            'title' => 'Client Entities',
            'entities' => $model->all(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_superadmin();
        $model = new ClientEntity();
        $this->renderSuperAdmin('superadmin/client_entities/create', [
            'title' => 'Create Client Entity',
            'entity' => null,
            'old' => $_SESSION['_old_client_entity_input'] ?? ['code' => $model->generateNextCode(), 'entity_type' => 'Group', 'is_active' => 1],
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
        unset($_SESSION['_old_client_entity_input']);
    }

    public function store(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('superadmin/client-entity/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/client-entity/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_client_entity_input'] = $data;
        $error = $this->validate($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('superadmin/client-entity/create');
        }

        try {
            $id = (new ClientEntity())->insert($data);
            unset($_SESSION['_old_client_entity_input']);
            AuditLog::recordPlatform('created', 'Created client entity ' . $data['name'], 'ClientEntity', $id);
            Session::flash('success', 'Client entity created successfully.');
            redirect('superadmin/client-entity/view/' . $id);
        } catch (Throwable $e) {
            Session::flash('error', 'Client entity could not be created: ' . $e->getMessage());
            redirect('superadmin/client-entity/create');
        }
    }

    public function edit(string $id): void
    {
        require_superadmin();
        $model = new ClientEntity();
        $entity = $model->find((int) $id);
        if (!$entity) {
            Session::flash('error', 'Client entity not found.');
            redirect('superadmin/client-entity/index');
        }

        $this->renderSuperAdmin('superadmin/client_entities/create', [
            'title' => 'Edit Client Entity',
            'entity' => $entity,
            'old' => $_SESSION['_old_client_entity_input'] ?? $entity,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
        unset($_SESSION['_old_client_entity_input']);
    }

    public function update(string $id): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('superadmin/client-entity/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/client-entity/edit/' . (int) $id);
        }

        $entityId = (int) $id;
        $model = new ClientEntity();
        $entity = $model->find($entityId);
        if (!$entity) {
            Session::flash('error', 'Client entity not found.');
            redirect('superadmin/client-entity/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_client_entity_input'] = $data;
        $error = $this->validate($data, $entityId);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('superadmin/client-entity/edit/' . $entityId);
        }

        try {
            $model->update($entityId, $data);
            unset($_SESSION['_old_client_entity_input']);
            AuditLog::recordPlatform('updated', 'Updated client entity ' . $data['name'], 'ClientEntity', $entityId);
            Session::flash('success', 'Client entity updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Client entity could not be updated: ' . $e->getMessage());
            redirect('superadmin/client-entity/edit/' . $entityId);
        }

        redirect('superadmin/client-entity/view/' . $entityId);
    }

    public function view(string $id): void
    {
        require_superadmin();
        $model = new ClientEntity();
        $entity = $model->find((int) $id);
        if (!$entity) {
            Session::flash('error', 'Client entity not found.');
            redirect('superadmin/client-entity/index');
        }

        $this->renderSuperAdmin('superadmin/client_entities/view', [
            'title' => (string) $entity['name'],
            'entity' => $entity,
            'companies' => $model->companies((int) $entity['id']),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    private function collectInput(): array
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        if ($code === '') {
            $code = (new ClientEntity())->generateNextCode();
        }

        return [
            'name' => trim((string) $this->input('name', '')),
            'code' => $code,
            'entity_type' => trim((string) $this->input('entity_type', 'Group')) ?: 'Group',
            'contact_person' => $this->nullable((string) $this->input('contact_person', '')),
            'email' => $this->nullable((string) $this->input('email', '')),
            'phone' => $this->nullable((string) $this->input('phone', '')),
            'address' => $this->nullable((string) $this->input('address', '')),
            'is_active' => (int) $this->input('is_active', 1) === 1 ? 1 : 0,
        ];
    }

    private function validate(array $data, ?int $excludeId = null): ?string
    {
        if ($data['name'] === '') {
            return 'Client entity name is required.';
        }
        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email format is invalid.';
        }

        $sql = 'SELECT id FROM client_entities WHERE name = :name';
        $params = ['name' => $data['name']];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            return 'A client entity with this name already exists.';
        }

        return null;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
