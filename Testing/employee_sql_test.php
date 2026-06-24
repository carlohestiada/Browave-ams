<?php
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->connect();

$insertStmt = $db->prepare(
    'INSERT INTO employees (employee_code, full_name, gender, department_id, status) VALUES (?, ?, ?, ?, ?)'
);
$insertResult = $insertStmt->execute([
    'EMP-TEST-002',
    'Test Employee 2',
    'Male',
    1,
    'Active'
]);

if (!$insertResult) {
    echo "Insert failed: ";
    print_r($insertStmt->errorInfo());
    exit(1);
}

$employeeId = $db->lastInsertId();
echo "Insert successful. New employee ID: $employeeId\n";

$selectStmt = $db->prepare('SELECT e.id, e.employee_code, e.full_name, e.gender, e.department_id, e.status, d.department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = ?');
$selectStmt->execute([$employeeId]);
$employee = $selectStmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "Select failed: employee not found.\n";
    exit(1);
}

echo "Selected employee: {$employee['id']} | {$employee['employee_code']} | {$employee['full_name']} | {$employee['gender']} | {$employee['department_name']} | {$employee['status']}\n";

$updateStmt = $db->prepare('UPDATE employees SET full_name = ?, status = ? WHERE id = ?');
$updateResult = $updateStmt->execute(['Updated Employee 2', 'Inactive', $employeeId]);

if (!$updateResult) {
    echo "Update failed: ";
    print_r($updateStmt->errorInfo());
    exit(1);
}

echo "Update successful.\n";

$selectStmt->execute([$employeeId]);
$employee = $selectStmt->fetch(PDO::FETCH_ASSOC);
echo "Verified after update: {$employee['id']} | {$employee['full_name']} | {$employee['status']}\n";

$deleteStmt = $db->prepare('DELETE FROM employees WHERE id = ?');
$deleteResult = $deleteStmt->execute([$employeeId]);

if (!$deleteResult) {
    echo "Delete failed: ";
    print_r($deleteStmt->errorInfo());
    exit(1);
}

echo "Delete successful.\n";

$selectStmt->execute([$employeeId]);
$deleted = $selectStmt->fetch(PDO::FETCH_ASSOC);
if ($deleted) {
    echo "Deletion verification failed.\n";
    exit(1);
}

echo "Employee row removed from database.\n";
