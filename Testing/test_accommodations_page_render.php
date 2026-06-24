<?php
ob_start();
include __DIR__ . '/../public/accommodations.php';
$html = ob_get_clean();

$requiredScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'assets/js/swal-utils.js',
    'assets/js/accommodations.js'
];

foreach ($requiredScripts as $script) {
    if (strpos($html, $script) === false) {
        echo "Missing script: $script\n";
        exit(1);
    }
}

if (strpos($html, 'id="accommodationForm"') === false || strpos($html, 'id="accommodationTable"') === false || strpos($html, 'id="floorModal"') === false || strpos($html, 'id="floorForm"') === false) {
    echo "accommodations.php missing required form or table markup.\n";
    exit(1);
}

if (strpos($html, 'Notes: Enter a descriptive building name') === false) {
    echo "accommodations.php missing add building note.\n";
    exit(1);
}

$js = file_get_contents(__DIR__ . '/../public/assets/js/accommodations.js');
if (strpos($js, "const floorApiUrl = 'api/floors.php';") === false || strpos($js, 'function saveFloorModal') === false || strpos($js, 'function showFloorModal') === false) {
    echo "accommodations.js missing required floor functions or API constant.\n";
    exit(1);
}

echo "Accommodations page render test passed. Required scripts and floor UI markup are present.\n";
