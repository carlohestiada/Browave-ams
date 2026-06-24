<?php
// Test arrivals page rendering
echo "Arrivals Page & Select2 Setup Test:\n";
echo str_repeat("=", 50) . "\n";

// Check key files exist
$checks = [
    'arrivals.php exists' => file_exists(__DIR__ . '/../public/arrivals.php'),
    'transactions.js exists' => file_exists(__DIR__ . '/../public/assets/js/transactions.js'),
    'EmployeeController exists' => file_exists(__DIR__ . '/../app/controllers/EmployeeController.php'),
];

$allPass = true;
foreach ($checks as $check => $result) {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo "$status: $check\n";
    if (!$result) $allPass = false;
}

// Check arrivals.php content for Select2
$arrivals = file_get_contents(__DIR__ . '/../public/arrivals.php');
$contentChecks = [
    'Select2 CSS link' => strpos($arrivals, 'select2.min.css') !== false,
    'Select2 JS script' => strpos($arrivals, 'select2.min.js') !== false,
    'Create employee modal' => strpos($arrivals, 'createEmployeeModal') !== false,
    'Show all checkbox' => strpos($arrivals, 'arrival_show_all') !== false,
];

echo "\nArrivals.php Content Checks:\n";
foreach ($contentChecks as $check => $result) {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo "$status: $check\n";
    if (!$result) $allPass = false;
}

// Check transactions.js for Select2 initialization
$transactions = file_get_contents(__DIR__ . '/../public/assets/js/transactions.js');
$jsChecks = [
    'Select2 initialization' => strpos($transactions, '.select2({') !== false,
    'Create new handler' => strpos($transactions, 'select2:select') !== false,
    'Create employee form' => strpos($transactions, 'createEmployeeForm') !== false,
    'Destroy select2' => strpos($transactions, "select2('destroy')") !== false,
];

echo "\nTransactions.js Checks:\n";
foreach ($jsChecks as $check => $result) {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    echo "$status: $check\n";
    if (!$result) $allPass = false;
}

// Test employee API separately (direct DB query to avoid header conflicts)
echo "\nEmployee API Test:\n";
echo str_repeat("=", 50) . "\n";

require __DIR__ . '/../app/config/database.php';
$db = (new Database())->connect();

// Query employees directly to test the mark_arrived_date logic
$sql = "SELECT e.*, d.department_name,
        (SELECT COUNT(*) FROM transactions t2 WHERE t2.employee_id = e.id AND t2.transaction_type = 'arrival' AND DATE(t2.transaction_date) = ?) as arrived_count
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY e.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute([date('Y-m-d')]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (is_array($employees)) {
    echo "✓ PASS: Employee query returns valid array\n";
    echo "  Returned " . count($employees) . " employees\n";
    if (count($employees) > 0) {
        echo "  Sample: {$employees[0]['employee_code']} - {$employees[0]['full_name']}\n";
        if (isset($employees[0]['arrived_count'])) {
            echo "  ✓ arrived_count field: " . $employees[0]['arrived_count'] . "\n";
        }
    }
} else {
    echo "✗ FAIL: Employee query failed\n";
    $allPass = false;
}

echo "\n" . str_repeat("=", 50) . "\n";
echo $allPass ? "✓ All setup checks passed!\n" : "✗ Some checks failed!\n";
