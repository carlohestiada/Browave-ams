<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TransportationController.php';

$db = (new Database())->connect();
$controller = new TransportationController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

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
