<?php
require_once __DIR__ . '/../app/config/database.php';
$db = (new Database())->connect();

// call API via include to simulate request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PATH_INFO'] = '/type/arrival';
$_GET['date_from'] = date('Y-m-d', strtotime('-7 days'));
$_GET['date_to'] = date('Y-m-d');

ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$output = ob_get_clean();

echo "API output: \n";
echo $output . "\n";
$json = json_decode($output, true);
if ($json === null) {
    echo "Failed to parse JSON\n";
    exit(1);
}

echo "OK - returned " . count($json) . " rows\n";
