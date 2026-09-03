<?php
require_once __DIR__ . '/../../app/config/api_auth.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/database.php';

try {
    $db = (new Database())->connect();

    // Helper to get week start and end dates
    function getWeekDates() {
        $today = new DateTime();
        $weekStart = clone $today;
        $weekStart->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        
        return [
            'start' => $weekStart->format('Y-m-d'),
            'end' => $weekEnd->format('Y-m-d'),
            'today' => $today->format('Y-m-d'),
            'weekStartObj' => $weekStart,
            'todayObj' => $today
        ];
    }

    $weekDates = getWeekDates();
    $today = $weekDates['today'];
    $weekStart = $weekDates['start'];
    $weekEnd = $weekDates['end'];
    $todayObj = $weekDates['todayObj'];

    if (isset($_GET['trip_activity'])) {
        $dateFrom = $_GET['date_from'] ?? $weekStart;
        $dateTo = $_GET['date_to'] ?? $weekEnd;
        $params = [$dateFrom, $dateTo];
        $legType = strtoupper(trim((string) ($_GET['leg_type'] ?? '')));

        $activitySql = "SELECT
                            tl.id AS trip_leg_id,
                            tl.trip_id,
                            tl.leg_type,
                            tl.leg_date,
                            t.employee_id,
                            e.employee_code,
                            e.english_name,
                            e.chinese_name,
                            e.gender,
                            d.department_name
                        FROM trip_legs tl
                        JOIN trips t ON tl.trip_id = t.id
                        JOIN employees e ON t.employee_id = e.id
                        LEFT JOIN departments d ON e.department_id = d.id
                        WHERE tl.leg_date BETWEEN ? AND ?";

        if (in_array($legType, ['ARRIVAL', 'DEPARTURE'], true)) {
            $activitySql .= ' AND UPPER(tl.leg_type) = ?';
            $params[] = $legType;
        }

        $activitySql .= ' ORDER BY tl.leg_date ASC, tl.id ASC';
        $activityStmt = $db->prepare($activitySql);
        $activityStmt->execute($params);

        echo json_encode($activityStmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Get available vehicles (status = 'Available')
    $vehicleStmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE status = 'Available'");
    $vehicleStmt->execute();
    $availableVehicles = $vehicleStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get available drivers (status = 'Available')
    $driverStmt = $db->prepare("SELECT COUNT(*) as count FROM drivers WHERE status = 'Available'");
    $driverStmt->execute();
    $availableDrivers = $driverStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Get transportation schedules for the week (daily breakdown)
    $stmt = $db->prepare(
        "SELECT DATE(pickup_date) as schedule_date, COUNT(*) as count
         FROM transportation_requests
         WHERE DATE(pickup_date) BETWEEN ? AND ?
         GROUP BY DATE(pickup_date)
         ORDER BY DATE(pickup_date) ASC"
    );
    $stmt->execute([$weekStart, $weekEnd]);
    $dailySchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create mapping of dates to counts
    $dailyMap = [];
    foreach ($dailySchedules as $record) {
        $dailyMap[$record['schedule_date']] = (int)$record['count'];
    }

    // Build complete week data with all days
    $weekData = [];
    $weekTotal = 0;
    $todayCount = 0;

    for ($i = 0; $i < 7; $i++) {
        $currentDate = clone $weekDates['weekStartObj'];
        $currentDate->modify("+{$i} days");
        $dateStr = $currentDate->format('Y-m-d');
        $count = $dailyMap[$dateStr] ?? 0;
        $weekTotal += $count;

        if ($dateStr === $today) {
            $todayCount = $count;
        }

        $weekData[] = [
            'date' => $dateStr,
            'weekday' => $currentDate->format('D'),
            'count' => $count,
            'isToday' => ($dateStr === $today)
        ];
    }

    echo json_encode([
        'success' => true,
        'availableVehicles' => $availableVehicles,
        'availableDrivers' => $availableDrivers,
        'scheduledThisWeek' => $weekTotal,
        'scheduledToday' => $todayCount,
        'weekData' => $weekData,
        'today' => $today
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load transportation data.',
        'details' => $e->getMessage()
    ]);
}
