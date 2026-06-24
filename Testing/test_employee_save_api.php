<?php
// Simulate a POST request to the employees API to verify save works.
chdir(__DIR__ . '/../public/api');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'employee_code' => 'EMP-TEST-SAVE',
    'full_name' => 'Save API Test',
    'gender' => 'Male',
    'department_id' => 1,
    'status' => 'Active'
];

ob_start();
include __DIR__ . '/../public/api/employees.php';
$output = ob_get_clean();

echo "API output: $output\n";

$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Save API test failed.\n";
    exit(1);
}

$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$select = $db->prepare('SELECT * FROM employees WHERE employee_code = ?');
$select->execute(['EMP-TEST-SAVE']);
$employee = $select->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "Employee row not found in database after save.\n";
    exit(1);
}

echo "Employee saved successfully: ID {$employee['id']} | {$employee['employee_code']} | {$employee['full_name']}\n";

$delete = $db->prepare('DELETE FROM employees WHERE id = ?');
$delete->execute([$employee['id']]);

echo "Cleanup successful.\n";
