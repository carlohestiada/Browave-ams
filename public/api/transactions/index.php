<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TransactionController.php';

$db = (new Database())->connect();
$controller = new TransactionController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$parts = $path !== '' ? explode('/', $path) : [];
$action = $parts[0] ?? null;
$type = $parts[1] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'type' && $type) {
        $controller->listByType($type);
    } elseif ($action && is_numeric($action)) {
        // GET /api/transactions/index.php/{id} - fetch single transaction
        $controller->show($action);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
    }
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['Admin', 'HR'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
        return;
    }

    if ($action === 'arrival') {
        $controller->storeArrival();
        return;
    }

    if ($action === 'departure') {
        $controller->storeDeparture();
        return;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    session_start();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['Admin', 'HR'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
        return;
    }

    if ($action && is_numeric($action)) {
        // PUT /api/transactions/index.php/{id} - update transaction
        $controller->update($action);
        return;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    session_start();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['Admin', 'HR'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
        return;
    }

    if ($action && is_numeric($action)) {
        $controller->destroy($action);
        return;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
