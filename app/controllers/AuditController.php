<?php

declare(strict_types=1);

class AuditController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin']);

        $model = new AuditLog();
        $filters = [
            'action' => trim((string) $this->input('action', '')),
            'module' => trim((string) $this->input('module', '')),
            'date_from' => trim((string) $this->input('date_from', '')),
            'date_to' => trim((string) $this->input('date_to', '')),
        ];
        $logs  = $model->search($filters, 500);

        if ($this->input('export') === 'csv') {
            $this->streamCsv($logs);
            return;
        }

        $this->render('audit/index', [
            'title'        => 'Audit Log',
            'logs'         => $logs,
            'filters'      => $filters,
            'flashSuccess' => Session::flash('success'),
        ]);
    }

    private function streamCsv(array $logs): void
    {
        AuditLog::record('audit_export', 'Exported audit log CSV.', 'AuditLog');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit-log-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Time', 'User', 'User Type', 'Action', 'Module', 'Record ID', 'Description', 'IP Address', 'User Agent']);
        foreach ($logs as $log) {
            fputcsv($out, [
                $log['created_at'] ?? '',
                $log['admin_name'] ?? $log['employee_name'] ?? 'System',
                $log['user_type'] ?? '',
                $log['action'] ?? '',
                $log['model'] ?? $log['module_name'] ?? '',
                $log['record_id'] ?? $log['entity_id'] ?? '',
                $log['description'] ?? '',
                $log['ip_address'] ?? '',
                $log['user_agent'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
