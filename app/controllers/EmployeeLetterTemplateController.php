<?php

declare(strict_types=1);

class EmployeeLetterTemplateController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $model = new EmployeeLetterTemplate();

        $this->render('employee_letter_templates/index', [
            'title' => 'Employee Letter Templates',
            'templates' => $model->allTemplates(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $template = (new EmployeeLetterTemplate())->find((int) $id);
        if (!$template) {
            Session::flash('error', 'Letter template not found.');
            redirect('employee-letter-template/index');
        }

        $this->render('employee_letter_templates/edit', [
            'title' => 'Edit Letter Template',
            'template' => $template,
            'fieldGroups' => EmployeeLetterTemplate::fieldGroups(),
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
            'old' => $_SESSION['_old_employee_letter_template'] ?? [],
        ]);
        unset($_SESSION['_old_employee_letter_template']);
    }

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $templateId = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('employee-letter-template/index');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('employee-letter-template/edit/' . $templateId);
        }

        $template = (new EmployeeLetterTemplate())->find($templateId);
        if (!$template) {
            Session::flash('error', 'Letter template not found.');
            redirect('employee-letter-template/index');
        }

        $body = trim((string) $this->input('body_html', ''));
        $_SESSION['_old_employee_letter_template'] = ['body_html' => $body];

        if ($body === '') {
            Session::flash('error', 'Letter content cannot be empty.');
            redirect('employee-letter-template/edit/' . $templateId);
        }

        try {
            (new EmployeeLetterTemplate())->update($templateId, [
                'title' => (string) ($template['letter_type'] ?? 'Letter') . ' - {{employee_name}}',
                'body_html' => $body,
                'version' => (int) ($template['version'] ?? 1) + 1,
                'updated_by' => (int) (current_user()['id'] ?? 0) ?: null,
            ]);
            unset($_SESSION['_old_employee_letter_template']);
            AuditLog::record('employee_letter_template_updated', 'Updated ' . (string) $template['letter_type'] . ' template.', 'EmployeeLetterTemplate', $templateId);
            Session::flash('success', 'Letter template updated.');
        } catch (Throwable $e) {
            Session::flash('error', 'Could not update letter template: ' . $e->getMessage());
        }

        redirect('employee-letter-template/edit/' . $templateId);
    }

    public function preview(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $template = (new EmployeeLetterTemplate())->find((int) $id);
        if (!$template) {
            Session::flash('error', 'Letter template not found.');
            redirect('employee-letter-template/index');
        }

        $employee = [
            'id' => 0,
            'full_name' => 'Martha Chanda',
            'employee_number' => 'EMP0003',
            'email' => 'martha.chanda@example.com',
            'phone' => '+260 977 000000',
            'department_name' => 'Academics',
            'designation' => 'Senior English Teacher',
            'employment_type' => 'Contract',
            'hired_at' => date('Y-m-d', strtotime('-3 years')),
            'probation_end_date' => date('Y-m-d', strtotime('-2 years')),
            'lifecycle_status' => 'Active',
            '_sample_latest_event' => [
                'event_type' => 'Promoted',
                'effective_date' => date('Y-m-d'),
                'notes' => 'Promotion approved after annual performance review.',
                'to_department' => 'Academics',
                'to_designation' => 'Senior English Teacher',
            ],
            '_sample_final_due' => [
                'net_final_due' => 7150,
                'notes' => 'Includes accrued leave pay and approved gratuity less deductions.',
            ],
        ];

        $rendered = (new EmployeeLetterTemplate())->render((string) $template['letter_type'], $employee, $template);

        $this->renderAuth('employee_letter_templates/preview', [
            'template' => $template,
            'renderedBody' => $rendered['body_html'],
            'missingFields' => $rendered['missing_tokens'],
        ]);
    }
}
