<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/database.php';

try {
    $db = (new Database())->connect();

    $summaryStmt = $db->query(
        "WITH room_summary AS (
            SELECT
                COUNT(*) AS total_rooms,
                COUNT(*) FILTER (
                    WHERE r.status <> 'Maintenance'
                      AND EXISTS (
                          SELECT 1
                          FROM room_assignments ra
                          WHERE (ra.status = 'Active' AND ra.room_id = r.id)
                             OR (
                                  ra.status = 'Transferred'
                                  AND (
                                      (ra.actual_checkout_date > CURRENT_DATE AND ra.room_id = r.id)
                                      OR (ra.actual_checkout_date <= CURRENT_DATE AND ra.transferred_to_room_id = r.id)
                                  )
                              )
                      )
                ) AS occupied_rooms,
                COUNT(*) FILTER (WHERE r.status = 'Available') AS available_rooms,
                COUNT(*) FILTER (WHERE r.status = 'Reserved') AS reserved_rooms,
                COUNT(*) FILTER (WHERE r.status = 'Maintenance') AS maintenance_rooms
            FROM rooms r
        ),
        room_types AS (
            SELECT COALESCE(NULLIF(room_type::text, ''), 'Unknown') AS room_type, COUNT(*)::int AS count
            FROM rooms
            GROUP BY room_type
        )
        SELECT
            total_rooms,
            occupied_rooms,
            available_rooms,
            reserved_rooms,
            maintenance_rooms,
            COALESCE(
                (SELECT json_object_agg(room_type, count ORDER BY room_type) FROM room_types),
                '{}'::json
            ) AS room_types
        FROM room_summary"
    );
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_rooms' => (int) ($summary['total_rooms'] ?? 0),
        'occupied_rooms' => (int) ($summary['occupied_rooms'] ?? 0),
        'available_rooms' => (int) ($summary['available_rooms'] ?? 0),
        'reserved_rooms' => (int) ($summary['reserved_rooms'] ?? 0),
        'maintenance_rooms' => (int) ($summary['maintenance_rooms'] ?? 0),
        'room_types' => json_decode($summary['room_types'] ?? '{}', true) ?: [],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load room status summary.',
        'details' => $e->getMessage(),
    ]);
}
