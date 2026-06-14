<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$db = db();

function statutoryColumnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

$columns = [
    'nrc_number' => "ALTER TABLE employees ADD COLUMN nrc_number VARCHAR(30) NULL AFTER gender_id",
    'date_of_birth' => "ALTER TABLE employees ADD COLUMN date_of_birth DATE NULL AFTER nrc_number",
    'address' => "ALTER TABLE employees ADD COLUMN address TEXT NULL AFTER phone",
    'napsa_number' => "ALTER TABLE employees ADD COLUMN napsa_number VARCHAR(50) NULL AFTER address",
    'tpin' => "ALTER TABLE employees ADD COLUMN tpin VARCHAR(30) NULL AFTER napsa_number",
];

foreach ($columns as $column => $sql) {
    if (!statutoryColumnExists($db, 'employees', $column)) {
        $db->exec($sql);
        echo "Added employees.{$column}\n";
    }
}

echo "Statutory compliance migration completed.\n";

