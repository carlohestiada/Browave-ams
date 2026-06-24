<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/ReportController.php';

$db = (new Database())->connect();
$controller = new ReportController($db);

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = trim($_SERVER['PATH_INFO'], '/');
}

if ($path === '' && !empty($_GET['action'])) {
    $path = trim($_GET['action'], '/');
}

if ($path === '' && !empty($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($script !== '' && strpos($uri, $script) !== false) {
        $path = trim(substr($uri, strpos($uri, $script) + strlen($script)), '/');
    } elseif (preg_match('#/api/reports\.php/?(.*)$#', $uri, $matches)) {
        $path = trim($matches[1], '/');
    }
}

$action = $path !== '' ? explode('/', $path)[0] : 'summary';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only GET method is allowed']);
    exit;
}

switch ($action) {
    case 'headcount':
        $controller->headcount();
        break;

    case 'occupancy':
        $controller->occupancy();
        break;

    case 'occupancy-by-accommodation':
        $controller->occupancyByAccommodation();
        break;

    case 'arrival-departure':
        $controller->arrivalDeparture();
        break;

    case 'summary':
        $controller->summary();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report type. Available: headcount, occupancy, occupancy-by-accommodation, arrival-departure, summary']);
        break;
}
