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
$segments = $path !== '' ? explode('/', $path) : [];
$id = $segments[0] ?? null;
$action = $segments[1] ?? null;
$legId = $segments[2] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

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
