<?php
ob_start();
include __DIR__ . '/../public/rooms.php';
$html = ob_get_clean();

$requiredScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'assets/js/swal-utils.js',
    'assets/js/rooms.js'
];

foreach ($requiredScripts as $script) {
    if (strpos($html, $script) === false) {
        echo "Missing script: $script\n";
        exit(1);
    }
}

if (strpos($html, 'id="accommodation_id"') === false || strpos($html, 'id="roomForm"') === false) {
    echo "Rooms page missing required form or select markup.\n";
    exit(1);
}

$js = file_get_contents(__DIR__ . '/../public/assets/js/rooms.js');
if (strpos($js, "const accommodationsApiUrl = 'api/accommodations.php';") === false || strpos($js, "const buildingsApiUrl = 'api/buildings.php';") === false || strpos($js, "const roomsApiUrl = 'api/rooms.php';") === false) {
    echo "rooms.js has incorrect API URL constants.\n";
    exit(1);
}

if (strpos($js, "$('#accommodation_id').on('change', loadBuildingsForModal);") === false || strpos($js, "$('#building_id').on('change', loadFloorsForModal);") === false) {
    echo "rooms.js missing room modal event bindings.\n";
    exit(1);
}

echo "Rooms page render test passed. Required scripts, markup, and room dropdown logic are present.\n";
