<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/services/MealCalculationService.php';

try {
    $db = (new Database())->connect();
    $service = new MealCalculationService($db);

    $startDate = $_GET['date_from'] ?? null;
    $endDate = $_GET['date_to'] ?? null;

    if (!$startDate || !$endDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing date range']);
        return;
    }

    $rows = $service->getHeadcountsForDateRange($startDate, $endDate);
    $dailyCounts = [];

    foreach ($rows as $row) {
        $date = $row['date'] ?? null;
        if (!$date) {
            continue;
        }

        $dailyCounts[$date] = (int) ($row['lunch_box'] ?? $row['meal_count'] ?? $row['active_count'] ?? 0);
    }

    echo json_encode([
        'date_from' => $startDate,
        'date_to' => $endDate,
        'daily_counts' => $dailyCounts,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('lunchbox_summary.php error: ' . $e->getMessage());
    echo json_encode(['error' => 'Unable to load lunch box summary data.']);
}
