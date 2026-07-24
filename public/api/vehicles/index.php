<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/VehicleController.php';

$db = null;
try {
    $db = (new Database())->connect();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$controller = new VehicleController($db);

$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
$segments = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($segments[0]) && is_numeric($segments[0]) ? $segments[0] : null;

// Ensure logs directory exists and set a small error logger for unexpected exceptions.
$logDir = __DIR__ . '/../../../app/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/api_errors.log';

try {
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
} catch (Throwable $e) {
    $entry = sprintf("[%s] %s in %s:%d\nStack trace:\n%s\n\n", date('c'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
