<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Employee.php';

$db = (new Database())->connect();
$employee = new Employee($db);

$departmentId = $db->query("SELECT id FROM departments ORDER BY id LIMIT 1")->fetchColumn();
if (!$departmentId) {
    $db->exec("INSERT INTO departments (department_name) VALUES ('Test Department')");
    $departmentId = $db->lastInsertId();
}

$code = 'EMP-OPT-' . uniqid();
$createResult = $employee->create([
    'employee_code' => $code,
    'full_name' => 'Optional Chinese',
    // no 'chinese_name' provided
    'gender' => 'Male',
    'department_id' => $departmentId,
    'status' => 'Active',
]);

if (!$createResult) {
    fwrite(STDERR, "Create without chinese_name failed.\n");
    exit(1);
}

$id = $db->lastInsertId();
$created = $employee->getById($id);
if (($created['chinese_name'] ?? null) !== null) {
    fwrite(STDERR, "chinese_name was not null when omitted.\n");
    exit(1);
}

$deleteResult = $employee->delete($id);
if (!$deleteResult['success']) {
    fwrite(STDERR, "Cleanup delete failed: " . json_encode($deleteResult) . "\n");
    exit(1);
}

echo "Create without chinese_name verified.\n";
exit(0);
