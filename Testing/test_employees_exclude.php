<?php
require_once __DIR__ . '/../app/config/database.php';

// Simulate GET
$_GET['exclude_arrived_date'] = date('Y-m-d');
// Ensure REQUEST_METHOD is set for CLI include
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include __DIR__ . '/../public/api/employees/index.php';
$output = ob_get_clean();

echo $output . "\n";
$json = json_decode($output, true);
if ($json === null) {
    echo "JSON parse failed\n";
    exit(1);
}

echo "Returned " . count($json) . " employees\n";
