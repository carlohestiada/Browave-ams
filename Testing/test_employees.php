<?php
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->connect();

// Insert a sample employee for testing
$insertStmt = $db->prepare(
    'INSERT INTO employees (employee_code, full_name, gender, department_id, status) VALUES (?, ?, ?, ?, ?)' 
);
$insertResult = $insertStmt->execute([
    'EMP-TEST-001',
    'Test Employee',
    'Male',
    1,
    'Active'
]);

if ($insertResult) {
    echo "Insert successful.\n";
} else {
    echo "Insert failed: ";
    print_r($insertStmt->errorInfo());
    exit(1);
}

// Query employees and show results
$sql = "SELECT e.id, e.employee_code, e.full_name, e.gender, d.department_name, e.status, e.created_at
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
ORDER BY e.id DESC";

$stmt = $db->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$employees) {
    echo "No employees found.\n";
    exit(0);
}

echo "Employees retrieved:\n";
foreach ($employees as $employee) {
    echo sprintf(
        "%d | %s | %s | %s | %s | %s | %s\n",
        $employee['id'], 
        $employee['employee_code'],
        $employee['full_name'],
        $employee['gender'],
        $employee['department_name'] ?? 'N/A',
        $employee['status'],
        $employee['created_at']
    );
}
