<?php
ob_start();
include __DIR__ . '/../public/users.php';
$html = ob_get_clean();

$requiredScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'assets/js/swal-utils.js',
    'assets/js/users.js'
];

foreach ($requiredScripts as $script) {
    if (strpos($html, $script) === false) {
        echo "Missing script: $script\n";
        exit(1);
    }
}

if (strpos($html, 'id="userTable"') === false || strpos($html, 'id="userForm"') === false || strpos($html, 'id="userModal"') === false) {
    echo "users.php missing required user UI markup.\n";
    exit(1);
}

$js = file_get_contents(__DIR__ . '/../public/assets/js/users.js');
if (strpos($js, "const usersApiUrl = 'api/users.php';") === false || strpos($js, 'function parseJsonResponse') === false) {
    echo "users.js is missing the API constant or JSON helper.\n";
    exit(1);
}

echo "Users page render test passed. Required scripts, markup, and API constant are present.\n";
