<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'employee_code' => 'EMP-TEST-003',
    'full_name' => 'Save Test',
    'gender' => 'Male',
    'department_id' => 1,
    'status' => 'Active'
];
ob_start();
include __DIR__ . '/../public/api/employees/index.php';
$output = ob_get_clean();
echo $output;
