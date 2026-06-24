<?php
require_once __DIR__ . '/../app/config/database.php';

// Simulate GET
$_GET['mark_arrived_date'] = date('Y-m-d');
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

foreach ($json as $emp) {
    echo ($emp['id'] ?? '-') . ' - arrived_count=' . ($emp['arrived_count'] ?? '0') . "\n";
}
