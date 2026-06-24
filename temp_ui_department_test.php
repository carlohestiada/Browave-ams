<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function fetch($url)
{
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    return @file_get_contents($url, false, $context);
}

$base = 'http://localhost/browave-ams/public/';
$pageUrl = $base . 'departments.php';
$jsUrl = $base . 'assets/js/department.js';
$apiUrl = $base . 'api/departments.php';

$page = fetch($pageUrl);
if ($page === false) {
    echo "PAGE_FETCH_FAILED\n";
    exit(1);
}

echo strpos($page, 'assets/js/department.js') !== false ? "PAGE_HAS_JS\n" : "PAGE_MISSING_JS\n";
echo strpos($page, '<form id="departmentForm"') !== false ? "PAGE_HAS_FORM\n" : "PAGE_MISSING_FORM\n";

$js = fetch($jsUrl);
if ($js === false) {
    echo "JS_FETCH_FAILED\n";
    exit(1);
}

echo strpos($js, 'function saveDepartment') !== false ? "JS_HAS_SAVE\n" : "JS_MISSING_SAVE\n";

echo "API_CHECK\n";
$response = fetch($apiUrl);
if ($response === false) {
    echo "API_FETCH_FAILED\n";
    exit(1);
}

echo "API_RESPONSE=" . trim($response) . "\n";
