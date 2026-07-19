<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Employee.php';

$db = (new Database())->connect();
$employee = new Employee($db);

$stmt = $db->query("SHOW COLUMNS FROM employees LIKE 'chinese_name'");
$column = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$column) {
    fwrite(STDERR, "Missing chinese_name column on employees table.\n");
    exit(1);
}

$departmentId = $db->query("SELECT id FROM departments ORDER BY id LIMIT 1")->fetchColumn();
if (!$departmentId) {
    $db->exec("INSERT INTO departments (department_name) VALUES ('Test Department')");
    $departmentId = $db->lastInsertId();
}

$code = 'EMP-TEST-' . uniqid();
$createResult = $employee->create([
    'employee_code' => $code,
    'full_name' => 'Test User',
    'chinese_name' => '测试用户',
    'gender' => 'Male',
    'department_id' => $departmentId,
    'status' => 'Active',
]);

if (!$createResult) {
    fwrite(STDERR, "Create with chinese_name failed.\n");
    exit(1);
}

$id = $db->lastInsertId();
$created = $employee->getById($id);
if (($created['chinese_name'] ?? null) !== '测试用户') {
    fwrite(STDERR, "Create did not persist chinese_name.\n");
    exit(1);
}

$updateResult = $employee->update($id, [
    'employee_code' => $code,
    'full_name' => 'Test User',
    'chinese_name' => '测试更新',
    'gender' => 'Male',
    'department_id' => $departmentId,
    'status' => 'Active',
]);

if (!$updateResult) {
    fwrite(STDERR, "Update with chinese_name failed.\n");
    exit(1);
}

$updated = $employee->getById($id);
if (($updated['chinese_name'] ?? null) !== '测试更新') {
    fwrite(STDERR, "Update did not persist chinese_name.\n");
    exit(1);
}

$deleteResult = $employee->delete($id);
if (!$deleteResult['success']) {
    fwrite(STDERR, "Delete failed: " . json_encode($deleteResult) . "\n");
    exit(1);
}

echo "Employee CRUD with chinese_name verified.\n";
exit(0);
