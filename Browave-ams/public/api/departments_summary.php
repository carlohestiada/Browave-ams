<?php
require_once __DIR__ . '/../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/database.php';

try {
    $db = (new Database())->connect();

    $sql = <<<SQL
SELECT
    d.id AS id,
    d.department_name AS department,
    COUNT(e.id) AS employee_count
FROM departments d
LEFT JOIN employees e
    ON e.department_id = d.id
GROUP BY d.id, d.department_name
ORDER BY d.department_name ASC
SQL;

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('departments_summary.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'Unable to load department summary data.']);
}
