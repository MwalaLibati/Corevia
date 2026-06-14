<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Bonuses & Overtime</h2>
        <p class="text-gray mb-0">Additional payroll earnings module scaffold.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Item Type</th>
                    <th>Amount</th>
                    <th>Hours</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-gray">No bonus/overtime items found.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e((string) ($item['employee_name'] ?? '')) ?></td>
                        <td><?= e((string) ($item['item_type'] ?? '')) ?></td>
                        <td><?= e(format_currency((float) ($item['amount'] ?? 0))) ?></td>
                        <td><?= e((string) ($item['worked_hours'] ?? '-')) ?></td>
                        <td><?= e((string) ($item['description'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
