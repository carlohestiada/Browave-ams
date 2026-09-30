<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/TransportationTypeController.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$role = $_SESSION['role'] ?? 'Viewer';

if ($method === 'POST' && !in_array($role, ['Admin', 'HR'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: insufficient permissions']);
    exit;
}

if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = (new Database())->connect();
    $controller = new TransportationTypeController($db);

    if ($method === 'GET') {
        $controller->index();
    } else {
        $controller->store();
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to process transportation types.']);
}