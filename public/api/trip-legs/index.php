<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TripLegController.php';

$db = (new Database())->connect();
$controller = new TripLegController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$id = $path !== '' ? explode('/', $path)[0] : null;
$method = $_SERVER['REQUEST_METHOD'];

$role = $_SESSION['role'] ?? 'Viewer';
if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && !in_array($role, ['Admin', 'HR'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
    exit;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->show($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Trip leg ID is required for direct GET.']);
        }
        break;

    case 'PUT':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing trip leg ID']);
            exit;
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing trip leg ID']);
            exit;
        }
        $controller->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
