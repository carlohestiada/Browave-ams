<?php
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->connect();

// 1) Insert a new department
$insertStmt = $db->prepare(
    'INSERT INTO departments (department_name) VALUES (?)'
);
$insertResult = $insertStmt->execute(['Test Department']);

if (!$insertResult) {
    echo "Insert failed: ";
    print_r($insertStmt->errorInfo());
    exit(1);
}

$departmentId = $db->lastInsertId();
echo "Insert successful. New department ID: $departmentId\n";

// 2) Select and verify inserted department
$selectStmt = $db->prepare('SELECT id, department_name FROM departments WHERE id = ?');
$selectStmt->execute([$departmentId]);
$department = $selectStmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    echo "Select failed: inserted department not found.\n";
    exit(1);
}

echo "Selected department: {$department['id']} | {$department['department_name']}\n";

// 3) Update the department name
$updateStmt = $db->prepare('UPDATE departments SET department_name = ? WHERE id = ?');
$updateResult = $updateStmt->execute(['Updated Test Department', $departmentId]);

if (!$updateResult) {
    echo "Update failed: ";
    print_r($updateStmt->errorInfo());
    exit(1);
}

echo "Update successful.\n";

// 4) Select and verify updated department
$selectStmt->execute([$departmentId]);
$department = $selectStmt->fetch(PDO::FETCH_ASSOC);

echo "Verified department after update: {$department['id']} | {$department['department_name']}\n";

// 5) Delete the department
$deleteStmt = $db->prepare('DELETE FROM departments WHERE id = ?');
$deleteResult = $deleteStmt->execute([$departmentId]);

if (!$deleteResult) {
    echo "Delete failed: ";
    print_r($deleteStmt->errorInfo());
    exit(1);
}

echo "Delete successful.\n";

// 6) Verify deletion
$selectStmt->execute([$departmentId]);
$deleted = $selectStmt->fetch(PDO::FETCH_ASSOC);

if ($deleted) {
    echo "Deletion verification failed: department still exists.\n";
    exit(1);
}

echo "Department row removed from database.\n";
