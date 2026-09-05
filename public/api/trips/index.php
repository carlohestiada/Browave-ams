<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TripController.php';
require_once __DIR__ . '/../../../app/controllers/TripLegController.php';

$db = (new Database())->connect();
$tripController = new TripController($db);
$tripLegController = new TripLegController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if ($path === '' && isset($_SERVER['REQUEST_URI'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '';
    $scriptPosition = $scriptPath !== '' ? strpos($requestPath, $scriptPath) : false;
    if ($scriptPosition !== false) {
        $path = trim(substr($requestPath, $scriptPosition + strlen($scriptPath)), '/');
    }
}
$segments = $path !== '' ? explode('/', $path) : [];
$id = $segments[0] ?? null;
$action = $segments[1] ?? null;
$legId = $segments[2] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

$role = $_SESSION['role'] ?? 'Viewer';
if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && !in_array($role, ['Admin', 'HR'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
    exit;
}

// Handle nested legs operations: /trips/{trip_id}/legs
if ($action === 'legs') {
    if ($method === 'GET') {
        $tripLegController->index($id);
    } elseif ($method === 'POST') {
        $tripLegController->store($id);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    exit;
}

if ($action === 'complete' || $action === 'cancel') {
    if ($method !== 'POST' || !$id) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $action === 'complete' ? $tripController->complete($id) : $tripController->cancel($id);
    exit;
}

// Handle trip-level operations
switch ($method) {
    case 'GET':
        if ($id) {
            $tripController->show($id);
        } else {
            $tripController->index();
        }
        break;

    case 'POST':
        $tripController->store();
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing trip ID']);
            exit;
        }
        $tripController->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing trip ID']);
            exit;
        }
        $tripController->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
