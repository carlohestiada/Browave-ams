<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/MealPlanningController.php';

$db = (new Database())->connect();
$controller = new MealPlanningController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$parts = $path !== '' ? explode('/', $path) : [];
$id = $parts[0] ?? null;
$action = $parts[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($id === 'range' && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $controller->getRange($_GET['start_date'], $_GET['end_date']);
        return;
    }

    if ($action === 'date' && $id) {
        $controller->getByDate($id);
        return;
    }
    
    if ($id) {
        $controller->edit($id);
    } else {
        $controller->index();
    }
    return;
}

if ($method === 'POST') {
    $controller->store();
    return;
}

if ($method === 'PUT') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing headcount ID']);
        return;
    }
    $controller->update($id);
    return;
}

if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing headcount ID']);
        return;
    }
    $controller->destroy($id);
    return;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
