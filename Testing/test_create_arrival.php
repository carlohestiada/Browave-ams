<?php
require_once __DIR__ . '/../app/config/database.php';

// simulate POST to create arrival for employee id 5
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/arrival';

// set session role to HR to pass permission check
session_start();
$_SESSION['role'] = 'HR';

$_POST['employee_id'] = 5;
$_POST['transaction_date'] = date('Y-m-d');
$_POST['remarks'] = 'CLI test arrival';

ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$output = ob_get_clean();

echo $output . "\n";

// run again to test duplicate prevention
ob_start();
include __DIR__ . '/../public/api/transactions/index.php';
$output2 = ob_get_clean();

echo $output2 . "\n";
