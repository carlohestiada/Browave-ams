<?php
ob_start();
include __DIR__ . '/../public/meals.php';
$html = ob_get_clean();

$requiredScripts = [
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    'assets/js/swal-utils.js',
    'assets/js/meals.js'
];

foreach ($requiredScripts as $script) {
    if (strpos($html, $script) === false) {
        echo "Missing script: $script\n";
        exit(1);
    }
}

if (strpos($html, 'id="mealForm"') === false || strpos($html, 'id="mealTable"') === false || strpos($html, 'id="meal_date"') === false) {
    echo "meals.php missing required meal UI markup.\n";
    exit(1);
}

if (strpos($html, 'Select a date to view the saved totals for that day.') === false) {
    echo "meals.php missing the date filter guidance note.\n";
    exit(1);
}

$js = file_get_contents(__DIR__ . '/../public/assets/js/meals.js');
if (strpos($js, "const mealsApiUrl = 'api/meals.php';") === false || strpos($js, 'function parseJsonResponse') === false) {
    echo "meals.js is missing the meals API constant or JSON helper.\n";
    exit(1);
}

if (strpos($js, "openMealModal({date: '") === false || strpos($js, 'Create Plan') === false) {
    echo "meals.js is missing the unsaved date create button or openMealModal call.\n";
    exit(1);
}

echo "Meals page render test passed. Required scripts, markup, and API logic are present.\n";
