<?php
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->connect();

$stmt = $db->query('SELECT id FROM employees ORDER BY id LIMIT 1');
$employeeId = $stmt->fetchColumn();

if (!$employeeId) {
    echo "No employees found for transaction validation test.\n";
    exit(0);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['role'] = 'HR';

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/arrival';
$_POST = [
    'employee_id' => $employeeId,
    'transaction_date' => $yesterday,
    'remarks' => 'Past date test'
];

ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$pastOutput = ob_get_clean();
$pastResult = json_decode($pastOutput, true);

$pastTestPassed = !empty($pastResult['success']) === false && strpos(strtolower($pastResult['error'] ?? ''), 'past') !== false;

$_SERVER['PATH_INFO'] = '/arrival';
$_POST = [
    'employee_id' => $employeeId,
    'transaction_date' => $today,
    'remarks' => 'Conflict test arrival'
];

ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$arrivalOutput = ob_get_clean();
$arrivalResult = json_decode($arrivalOutput, true);

$createdArrival = !empty($arrivalResult['success']);

$_SERVER['PATH_INFO'] = '/departure';
$_POST = [
    'employee_id' => $employeeId,
    'transaction_date' => $today,
    'remarks' => 'Conflict test departure'
];

ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$departureOutput = ob_get_clean();
$departureResult = json_decode($departureOutput, true);

$conflictTestPassed = !empty($departureResult['success']) === false && strpos(strtolower($departureResult['error'] ?? ''), 'departure') !== false;

echo "Past date test: " . ($pastTestPassed ? 'PASS' : 'FAIL') . "\n";
echo "Same-day arrival/departure test: " . ($conflictTestPassed ? 'PASS' : 'FAIL') . "\n";

if ($createdArrival) {
    $db->prepare('DELETE FROM transactions WHERE employee_id = ? AND transaction_type = ? AND DATE(transaction_date) = ?')->execute([$employeeId, 'arrival', $today]);
    $db->prepare('DELETE FROM transactions WHERE employee_id = ? AND transaction_type = ? AND DATE(transaction_date) = ?')->execute([$employeeId, 'departure', $today]);
}
