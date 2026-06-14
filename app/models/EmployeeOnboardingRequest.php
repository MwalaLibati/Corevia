<?php

declare(strict_types=1);

class EmployeeOnboardingRequest extends Model
{
    protected string $table = 'employee_onboarding_requests';
    protected bool $tenantScoped = true;

    public function listAll(string $status = ''): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE eor.company_id = :cid' : 'WHERE 1=1';
        $params = [];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        if ($status !== '') {
            $where .= ' AND eor.status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare(
            "SELECT eor.*, d.name AS department_name, u.full_name AS created_by_name, emp.employee_number AS created_employee_number
             FROM employee_onboarding_requests eor
             LEFT JOIN departments d ON d.id = eor.department_id
             LEFT JOIN users u ON u.id = eor.created_by
             LEFT JOIN employees emp ON emp.id = eor.created_employee_id
             {$where}
             ORDER BY eor.created_at DESC, eor.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND eor.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT eor.*, c.name AS company_name, d.name AS department_name, emp.employee_number AS created_employee_number
             FROM employee_onboarding_requests eor
             JOIN companies c ON c.id = eor.company_id
             LEFT JOIN departments d ON d.id = eor.department_id
             LEFT JOIN employees emp ON emp.id = eor.created_employee_id
             WHERE eor.id = :id{$and}
             LIMIT 1"
        );
        $params = ['id' => $id];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT eor.*, c.name AS company_name, d.name AS department_name
             FROM employee_onboarding_requests eor
             JOIN companies c ON c.id = eor.company_id
             LEFT JOIN departments d ON d.id = eor.department_id
             WHERE eor.token = :token
             LIMIT 1"
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createInvitation(array $data): int
    {
        $data['token'] = bin2hex(random_bytes(32));
        $data['status'] = 'Sent';
        return $this->insert($data);
    }

    public function markOpened(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE employee_onboarding_requests
             SET status = 'Opened'
             WHERE id = :id AND status IN ('Sent','Draft')"
        );
        $stmt->execute(['id' => $id]);
    }

    public function submit(int $id, array $data): void
    {
        $data['status'] = 'Submitted';
        $data['submitted_at'] = date('Y-m-d H:i:s');
        $this->update($id, $data);
    }

    public function approve(int $id, int $employeeId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE employee_onboarding_requests
             SET status = 'Approved', approved_at = NOW(), approved_by = :approved_by, created_employee_id = :employee_id
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id, 'approved_by' => $userId ?: null, 'employee_id' => $employeeId]);
    }

    public function cancel(int $id): bool
    {
        return $this->update($id, ['status' => 'Cancelled']);
    }

    public function documents(int $requestId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM employee_onboarding_documents WHERE onboarding_request_id = :id ORDER BY uploaded_at DESC, id DESC'
        );
        $stmt->execute(['id' => $requestId]);
        return $stmt->fetchAll();
    }

    public function addDocument(int $requestId, int $companyId, array $file): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO employee_onboarding_documents
             (onboarding_request_id, company_id, document_type, original_name, stored_path, mime_type, file_size)
             VALUES (:request_id, :company_id, :document_type, :original_name, :stored_path, :mime_type, :file_size)'
        );
        $stmt->execute([
            'request_id' => $requestId,
            'company_id' => $companyId,
            'document_type' => (string) ($file['document_type'] ?? 'Supporting Document'),
            'original_name' => (string) ($file['original_name'] ?? ''),
            'stored_path' => (string) ($file['stored_path'] ?? ''),
            'mime_type' => (string) ($file['mime_type'] ?? ''),
            'file_size' => (int) ($file['file_size'] ?? 0),
        ]);
        return (int) $this->db->lastInsertId();
    }
}
