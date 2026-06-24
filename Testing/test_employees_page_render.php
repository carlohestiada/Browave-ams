<?php
ob_start();
include __DIR__ . '/../public/employees.php';
$html = ob_get_clean();

$requiredScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'assets/js/swal-utils.js',
    'assets/js/employee.js'
];

foreach ($requiredScripts as $script) {
    if (strpos($html, $script) === false) {
        echo "Missing script: $script\n";
        exit(1);
    }
}

$scriptOrder = [];
foreach ($requiredScripts as $script) {
    $scriptOrder[$script] = strpos($html, $script);
}

if ($scriptOrder['assets/js/swal-utils.js'] > $scriptOrder['assets/js/employee.js']) {
    echo "Script order incorrect: swal-utils must load before employee.js\n";
    exit(1);
}

if (strpos($html, 'data-bs-toggle="modal"') === false || strpos($html, 'id="employeeForm"') === false) {
    echo "Employee modal or form markup missing.\n";
    exit(1);
}

echo "Employees page render test passed. Scripts and markup OK.\n";
