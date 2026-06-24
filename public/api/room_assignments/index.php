<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/RoomAssignmentController.php';

session_start();

$db = (new Database())->connect();
$controller = new RoomAssignmentController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$id = $path !== '' ? explode('/', $path)[0] : null;
$method = $_SERVER['REQUEST_METHOD'];

// simple role check: only Admin and HR can create/transfer assignments
$role = $_SESSION['role'] ?? 'Viewer';

if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && !in_array($role, ['Admin', 'HR'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
    exit;
}

switch ($method) {
    case 'GET':
        $controller->index();
        break;

    case 'POST':
        $controller->store();
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            exit;
        }
        $controller->transfer($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            exit;
        }
        $controller->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
