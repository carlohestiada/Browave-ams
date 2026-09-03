<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TransportationController.php';

$db = (new Database())->connect();
$controller = new TransportationController($db);

// Apache may omit PATH_INFO for index.php/{route}; recover the route from REQUEST_URI.
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if ($path === '' && isset($_SERVER['REQUEST_URI'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '';
    $scriptPosition = $scriptPath !== '' ? strpos($requestPath, $scriptPath) : false;

    if ($scriptPosition !== false) {
        $path = trim(substr($requestPath, $scriptPosition + strlen($scriptPath)), '/');
    }
}
$segments = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Phase 4: Handle trip_leg transportation endpoint
// GET /api/company-car/trip-leg/{trip_leg_id} -> get transportation for specific trip leg
if (count($segments) > 0 && $segments[0] === 'trip-leg' && isset($segments[1])) {
    if ($method === 'GET') {
        $controller->getByTripLegId($segments[1]);
        return;
    }
}

if (count($segments) > 0 && $segments[0] === 'employee' && isset($segments[1])) {
    if ($method === 'GET') {
        $controller->getEmployeeDetails($segments[1]);
        return;
    }
}

if (count($segments) > 0 && $segments[0] === 'stats') {
    if ($method === 'GET') {
        $controller->index();
        return;
    }
}

if (count($segments) > 0 && $segments[0] === 'bulk') {
    if ($method === 'POST') {
        $controller->storeBulk();
        return;
    }
}

$id = isset($segments[0]) && is_numeric($segments[0]) ? $segments[0] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->edit($id);
        } else {
            $controller->index();
        }
        break;
    case 'POST':
        $controller->store();
        break;
    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing request ID']);
            exit;
        }
        $controller->update($id);
        break;
    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing request ID']);
            exit;
        }
        $controller->destroy($id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
