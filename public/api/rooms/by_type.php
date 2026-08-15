<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';

try {
    $db = (new Database())->connect();
    $roomType = isset($_GET['room_type']) ? trim($_GET['room_type']) : null;

    if (!$roomType) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'room_type parameter is required']);
        return;
    }

    $stmt = $db->prepare(
        "SELECT r.id, r.room_no, r.room_type, r.capacity, r.current_occupancy, r.status,
                b.building_name,
                f.floor_name,
                COUNT(ra.id) FILTER (WHERE ra.status = 'Active') AS active_assignments
         FROM rooms r
         LEFT JOIN floors f ON r.floor_id = f.id
         LEFT JOIN buildings b ON f.building_id = b.id
         LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status IN ('Active', 'Transferred')
         WHERE r.room_type = :room_type
         GROUP BY r.id, r.room_no, r.room_type, r.capacity, r.current_occupancy, r.status, b.building_name, f.floor_name
         ORDER BY r.room_no ASC"
    );
    $stmt->execute([':room_type' => $roomType]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'total_rooms' => count($records),
        'occupied' => 0,
        'available' => 0,
        'maintenance' => 0,
    ];

    foreach ($records as $room) {
        $status = trim((string) ($room['status'] ?? ''));
        if ($status === 'Occupied') $summary['occupied']++;
        elseif ($status === 'Available') $summary['available']++;
        elseif ($status === 'Maintenance') $summary['maintenance']++;
    }

    echo json_encode([
        'success' => true,
        'room_type' => $roomType,
        'count' => count($records),
        'summary' => $summary,
        'records' => $records,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load room details.',
        'details' => $e->getMessage(),
    ]);
}
