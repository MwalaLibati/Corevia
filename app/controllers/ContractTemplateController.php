<?php

declare(strict_types=1);

/**
 * Manages contract templates (CRUD + preview).
 */
class ContractTemplateController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new ContractTemplate();

        $this->render('contract_templates/index', [
            'title'        => 'Contract Templates',
            'templates'    => $model->listAll(),
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new ContractTemplate();

        $this->render('contract_templates/create', [
            'title'         => 'New Contract Template',
            'contractTypes' => (new EmploymentType())->names(),
            'structures'    => $model->salaryStructures(),
            'branches'      => $model->branches(),
            'tokens'        => ContractTemplate::TOKENS,
            'fieldGroups'   => $model->fieldGroups(),
            'sectionDefaults' => $this->sectionDefaults($model),
            'template'      => null,
            'versions'      => [],
            'csrf'          => Session::csrfToken(),
            'flashError'    => Session::flash('error'),
            'old'           => $_SESSION['_old_ct_input'] ?? [
                'cover_body' => $model->professionalDefaultCover(),
                'body' => $model->professionalDefaultBody(),
                'signature_body' => $model->professionalDefaultSignature(),
                'footer_body' => $model->professionalDefaultFooter(),
            ],
        ]);

        unset($_SESSION['_old_ct_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract_template/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract_template/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_ct_input'] = $data;

        $error = $this->validate($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('contract_template/create');
        }

        $model  = new ContractTemplate();
        $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);

        try {
            if ((int) ($data['is_default'] ?? 0) === 1) {
                $this->clearDefaults();
            }

            $model->insert([
                'company_id'          => \Tenant::id(),
                'branch_id'           => $data['branch_id'],
                'name'                => $data['name'],
                'salary_structure_id' => $data['salary_structure_id'],
                'contract_type'       => $data['contract_type'],
                'cover_body'          => $data['cover_body'],
                'body'                => $data['body'],
                'signature_body'      => $data['signature_body'],
                'footer_body'         => $data['footer_body'],
                'is_default'          => (int) ($data['is_default'] ?? 0),
                'created_by'          => $userId > 0 ? $userId : null,
            ]);

            unset($_SESSION['_old_ct_input']);
            Session::flash('success', 'Template created successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to create template: ' . $e->getMessage());
        }

        redirect('contract_template/index');
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $templateId = (int) $id;
        if ($templateId <= 0) {
            Session::flash('error', 'Invalid template id.');
            redirect('contract_template/index');
        }

        $model    = new ContractTemplate();
        $template = $model->findDetailed($templateId);

        if (!$template) {
            Session::flash('error', 'Template not found.');
            redirect('contract_template/index');
        }

        $old      = $_SESSION['_old_ct_input'] ?? [];
        $formData = !empty($old) ? $old : $template;
        if (trim((string)($formData['cover_body'] ?? '')) === '') {
            $formData['cover_body'] = $model->professionalDefaultCover();
        }
        if (trim((string)($formData['signature_body'] ?? '')) === '') {
            $formData['signature_body'] = $model->professionalDefaultSignature();
        }
        if (trim((string)($formData['footer_body'] ?? '')) === '') {
            $formData['footer_body'] = $model->professionalDefaultFooter();
        }

        $this->render('contract_templates/create', [
            'title'         => 'Edit Contract Template',
            'contractTypes' => (new EmploymentType())->names(),
            'structures'    => $model->salaryStructures(),
            'branches'      => $model->branches(),
            'tokens'        => ContractTemplate::TOKENS,
            'fieldGroups'   => $model->fieldGroups(),
            'sectionDefaults' => $this->sectionDefaults($model),
            'template'      => $template,
            'versions'      => $model->versions($templateId),
            'csrf'          => Session::csrfToken(),
            'flashError'    => Session::flash('error'),
            'flashSuccess'  => Session::flash('success'),
            'old'           => $formData,
        ]);

        unset($_SESSION['_old_ct_input']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract_template/index');
        }

        $templateId = (int) $id;
        if ($templateId <= 0) {
            Session::flash('error', 'Invalid template id.');
            redirect('contract_template/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract_template/edit/' . $templateId);
        }

        $model    = new ContractTemplate();
        $template = $model->findDetailed($templateId);

        if (!$template) {
            Session::flash('error', 'Template not found.');
            redirect('contract_template/index');
        }

        $data = $this->collectInput();
        $_SESSION['_old_ct_input'] = $data;

        $error = $this->validate($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('contract_template/edit/' . $templateId);
        }

        try {
            $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
            if ((int) ($data['is_default'] ?? 0) === 1) {
                $this->clearDefaults($templateId);
            }

            $model->archiveVersion($template, $userId > 0 ? $userId : null);

            $updateData = [
                'name'                => $data['name'],
                'salary_structure_id' => $data['salary_structure_id'],
                'branch_id'           => $data['branch_id'],
                'contract_type'       => $data['contract_type'],
                'cover_body'          => $data['cover_body'],
                'body'                => $data['body'],
                'signature_body'      => $data['signature_body'],
                'footer_body'         => $data['footer_body'],
                'is_default'          => (int) ($data['is_default'] ?? 0),
            ];

            if (array_key_exists('version', $template)) {
                $updateData['version'] = (int) ($template['version'] ?? 1) + 1;
            }

            $model->update($templateId, $updateData);

            unset($_SESSION['_old_ct_input']);
            Session::flash('success', 'Template updated successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to update template: ' . $e->getMessage());
        }

        redirect('contract_template/edit/' . $templateId);
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contract_template/index');
        }

        $templateId = (int) $id;

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('contract_template/index');
        }

        $model = new ContractTemplate();

        try {
            $model->delete($templateId);
            Session::flash('success', 'Template deleted.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to delete template: ' . $e->getMessage());
        }

        redirect('contract_template/index');
    }

    public function preview(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $templateId = (int) $id;
        if ($templateId <= 0) {
            Session::flash('error', 'Invalid template id.');
            redirect('contract_template/index');
        }

        $model    = new ContractTemplate();
        $template = $model->findDetailed($templateId);

        if (!$template) {
            Session::flash('error', 'Template not found.');
            redirect('contract_template/index');
        }

        $dummyContract = [
            'contract_number' => 'CNT000001',
            'contract_type'   => $template['contract_type'] ?? 'Contract',
            'start_date'      => date('Y-m-d'),
            'end_date'        => date('Y-m-d', strtotime('+1 year')),
            'notes'           => 'Sample contract notes.',
        ];

        $dummyEmployee = [
            'full_name'             => 'John Mwansa',
            'employee_number'       => 'EMP001',
            'designation'           => 'Operations Officer',
            'department_name'       => 'Operations',
            'department_code'       => 'OPS',
            'employment_type'       => 'Contract',
            'hired_at'              => date('Y-m-d'),
            'probation_end_date'    => date('Y-m-d', strtotime('+3 months')),
            'branch_name'           => $template['branch_name'] ?? 'Lusaka Branch',
            'branch_code'           => 'LSK',
            'branch_address'        => 'Lusaka, Zambia',
            'branch_phone'          => '+260 211 000000',
            'branch_email'          => 'lusaka@example.com',
            'client_entity_name'    => 'Sample Group',
            'client_entity_code'    => 'SG',
            'salary_structure_name' => $template['structure_name'] ?? 'Monthly Staff',
            'grade_level'           => 'Grade 4',
            'structure_basic_pay'   => 3800.00,
            'basic_pay'             => 4000.00,
            'housing_allowance'      => 0.00,
            'transport_allowance'    => 0.00,
            'other_allowances'       => 0.00,
            'nrc_number'             => '123456/78/9',
            'phone'                  => '+260 977 000000',
            'email'                  => 'john.mwansa@example.com',
            'address'                => 'Kalulushi, Copperbelt',
            'bank_name'              => 'Sample Bank',
            'bank_account_number'    => '000123456789',
            'napsa_number'           => 'NAPSA12345',
            'tpin'                   => '1000000000',
        ];

        $tokenValues  = $model->buildTokenValues($dummyContract, $dummyEmployee);
        $missingFields = $model->missingFields((string) $template['body'], $tokenValues);
        $coverBody = trim((string)($template['cover_body'] ?? '')) ?: $model->professionalDefaultCover();
        $signatureBody = trim((string)($template['signature_body'] ?? '')) ?: $model->professionalDefaultSignature();
        $footerBody = trim((string)($template['footer_body'] ?? '')) ?: $model->professionalDefaultFooter();
        $missingFields += $model->missingFields($coverBody . $signatureBody . $footerBody, $tokenValues);
        $renderedBody = $model->renderBody((string) $template['body'], $tokenValues, true);

        $this->renderAuth('contract_templates/preview', [
            'template'     => $template,
            'renderedBody' => $renderedBody,
            'renderedCover' => $model->renderBody($coverBody, $tokenValues, true),
            'renderedSignature' => $model->renderBody($signatureBody, $tokenValues, true),
            'renderedFooter' => $model->renderBody($footerBody, $tokenValues, true),
            'missingFields'=> $missingFields,
            'tokenValues'  => $tokenValues,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function collectInput(): array
    {
        $ssId = (int) $this->input('salary_structure_id', 0);
        $branchId = (int) $this->input('branch_id', 0);
        $type = trim((string) $this->input('contract_type', ''));

        return [
            'name'                => trim((string) $this->input('name', '')),
            'salary_structure_id' => $ssId > 0 ? $ssId : null,
            'branch_id'           => $branchId > 0 ? $branchId : null,
            'contract_type'       => $type !== '' ? $type : null,
            'cover_body'          => trim((string) $this->input('cover_body', '')),
            'body'                => trim((string) $this->input('body', '')),
            'signature_body'      => trim((string) $this->input('signature_body', '')),
            'footer_body'         => trim((string) $this->input('footer_body', '')),
            'is_default'          => (int) $this->input('is_default', 0),
        ];
    }

    private function validate(array $data): ?string
    {
        if ($data['name'] === '') {
            return 'Template name is required.';
        }

        if ($data['body'] === '') {
            return 'Template body cannot be empty.';
        }

        if ($data['contract_type'] !== null && !in_array($data['contract_type'], (new EmploymentType())->names(), true)) {
            return 'Invalid contract type.';
        }

        if ($data['branch_id'] !== null) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM branches WHERE id = :id AND company_id = :cid');
            $stmt->execute(['id' => $data['branch_id'], 'cid' => Tenant::id()]);
            if ((int) $stmt->fetchColumn() === 0) {
                return 'Selected branch does not belong to this company.';
            }
        }

        return null;
    }

    private function clearDefaults(?int $excludeId = null): void
    {
        $db  = db();
        $cid = \Tenant::id();

        if ($excludeId !== null) {
            $stmt = $db->prepare(
                "UPDATE contract_templates SET is_default = 0 WHERE company_id = :cid AND id != :eid"
            );
            $stmt->execute(['cid' => $cid, 'eid' => $excludeId]);
        } else {
            $stmt = $db->prepare(
                "UPDATE contract_templates SET is_default = 0 WHERE company_id = :cid"
            );
            $stmt->execute(['cid' => $cid]);
        }
    }

    private function sectionDefaults(ContractTemplate $model): array
    {
        return [
            'cover_body' => $model->professionalDefaultCover(),
            'body' => $model->professionalDefaultBody(),
            'signature_body' => $model->professionalDefaultSignature(),
            'footer_body' => $model->professionalDefaultFooter(),
        ];
    }
}
