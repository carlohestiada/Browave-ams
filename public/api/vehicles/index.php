<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/VehicleController.php';

$db = (new Database())->connect();
$controller = new VehicleController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];
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
            echo json_encode(['success' => false, 'error' => 'Missing vehicle ID']);
            exit;
        }
        $controller->update($id);
        break;
    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing vehicle ID']);
            exit;
        }
        $controller->destroy($id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
