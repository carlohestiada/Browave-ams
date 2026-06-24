<?php
// Verify employees page and employee.js support department dropdown loading.
$html = file_get_contents(__DIR__ . '/../public/employees.php');

if (strpos($html, 'id="department_id"') === false) {
    echo "employees.php missing department select markup.\n";
    exit(1);
}

if (strpos($html, 'assets/js/employee.js') === false) {
    echo "employees.php missing employee.js include.\n";
    exit(1);
}

$js = file_get_contents(__DIR__ . '/../public/assets/js/employee.js');

if (strpos($js, "$.get('api/departments.php'") === false && strpos($js, 'api/departments.php') === false) {
    echo "employee.js does not call api/departments.php correctly.\n";
    exit(1);
}

if (preg_match('/const \w+\s*=\s*JSON\.parse\(data\)/', $js) && strpos($js, "typeof data === 'string' ? JSON.parse(data) : data") === false) {
    echo "employee.js contains unconditional JSON.parse(data) calls; browser data may already be parsed.\n";
    exit(1);
}

$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$stmt = $db->query('SELECT id FROM departments LIMIT 1');
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    echo "No departments found in database; cannot populate dropdown.\n";
    exit(1);
}

echo "Employee dropdown test passed: markup present, js path correct, and departments exist in database.\n";
