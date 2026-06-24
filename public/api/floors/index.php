<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/FloorController.php';

$db = (new Database())->connect();
$controller = new FloorController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$id = $path !== '' ? explode('/', $path)[0] : null;
$method = $_SERVER['REQUEST_METHOD'];

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
            echo json_encode(['success' => false, 'error' => 'Missing floor ID']);
            exit;
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing floor ID']);
            exit;
        }
        $controller->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
