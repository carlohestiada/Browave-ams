<?php

require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/controllers/WorkCalendarController.php';

$db = (new Database())->connect();
$controller = new WorkCalendarController($db);
$path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if ($path === '' && isset($_SERVER['REQUEST_URI'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
    $scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '';
    if ($scriptPath !== '' && strpos($requestPath, $scriptPath) !== false) {
        $path = trim(substr($requestPath, strpos($requestPath, $scriptPath) + strlen($scriptPath)), '/');
    }
}
$parts = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && (($parts[0] ?? '') === 'export')) {
    $controller->export();
    return;
}

if ($method === 'GET') {
    if (!empty($parts[0])) {
        $controller->show($parts[0]);
    } else {
        $controller->index();
    }
    return;
}

if ($method === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'Admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required.']);
        return;
    }
    $controller->store();
    return;
}

if ($method === 'DELETE') {
    if (($_SESSION['role'] ?? '') !== 'Admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required.']);
        return;
    }
    $controller->destroy($parts[0] ?? '');
    return;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
