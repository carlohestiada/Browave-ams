<?php

session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: admin access required']);
    exit;
}

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/UserController.php';

$db = (new Database())->connect();
$controller = new UserController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$id = $path !== '' ? explode('/', $path)[0] : null;
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

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
            echo json_encode(['success' => false, 'error' => 'Missing user ID']);
            exit;
        }
        $controller->update($id);
        break;

    case 'DELETE':
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing user ID']);
            exit;
        }
        $controller->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}
