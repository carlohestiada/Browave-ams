<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';

try {
    $db = (new Database())->connect();
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;

    if (!$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'status parameter is required']);
        return;
    }

    // Occupied - get employees in occupied rooms (only Active assignments)
    if ($status === 'Occupied') {
        $stmt = $db->prepare(
            "WITH room_state AS (
                SELECT
                    r.*,
                    CASE
                        WHEN r.status = 'Maintenance' THEN 'Maintenance'
                        WHEN EXISTS (
                            SELECT 1 FROM room_assignments active_ra
                            WHERE active_ra.room_id = r.id AND active_ra.status = 'Active'
                        ) THEN 'Occupied'
                        WHEN r.reserved_by_employee_id IS NOT NULL THEN 'Reserved'
                        ELSE 'Available'
                    END AS effective_status
                FROM rooms r
             )
             SELECT DISTINCT
                e.id,
                e.employee_code,
                e.english_name,
                e.chinese_name,
                e.gender,
                d.department_name,
                d.location,
                r.room_no,
                r.reserved_by_employee_id,
                reserved_by.english_name AS reserved_by_name,
                b.building_name,
                f.floor_name
             FROM room_assignments ra
             JOIN employees e ON e.id = ra.employee_id
             LEFT JOIN departments d ON e.department_id = d.id
             JOIN room_state r ON ra.room_id = r.id AND r.effective_status = 'Occupied'
             LEFT JOIN employees reserved_by ON r.reserved_by_employee_id = reserved_by.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE ra.status = 'Active'
             ORDER BY e.english_name ASC"
        );
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'status' => 'Occupied',
            'type' => 'employees',
            'count' => count($records),
            'records' => $records
        ]);
        return;
    }

    // Available - get available rooms
    if ($status === 'Available') {
        $stmt = $db->prepare(
            "WITH room_state AS (
                SELECT
                    r.*,
                    CASE
                        WHEN r.status = 'Maintenance' THEN 'Maintenance'
                        WHEN EXISTS (
                            SELECT 1 FROM room_assignments ra
                            WHERE ra.room_id = r.id AND ra.status = 'Active'
                        ) THEN 'Occupied'
                        WHEN r.reserved_by_employee_id IS NOT NULL THEN 'Reserved'
                        ELSE 'Available'
                    END AS effective_status
                FROM rooms r
             )
             SELECT
                r.id,
                r.room_no,
                r.room_type,
                r.effective_status AS status,
                r.capacity,
                r.current_occupancy,
                b.building_name,
                f.floor_name
             FROM room_state r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE r.effective_status = 'Available'
             ORDER BY b.building_name, f.floor_name, r.room_no ASC"
        );
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'status' => 'Available',
            'type' => 'rooms',
            'count' => count($records),
            'records' => $records
        ]);
        return;
    }

    // Maintenance - get maintenance rooms
    if ($status === 'Maintenance') {
        $stmt = $db->prepare(
            "SELECT
                r.id,
                r.room_no,
                r.room_type,
                r.status,
                r.capacity,
                r.current_occupancy,
                b.building_name,
                f.floor_name
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE r.status = 'Maintenance'
             ORDER BY b.building_name, f.floor_name, r.room_no ASC"
        );
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'status' => 'Maintenance',
            'type' => 'rooms',
            'count' => count($records),
            'records' => $records
        ]);
        return;
    }

    // Reserved - get reserved rooms with reserving employee
    if ($status === 'Reserved') {
        $stmt = $db->prepare(
            "WITH room_state AS (
                SELECT
                    r.*,
                    CASE
                        WHEN r.status = 'Maintenance' THEN 'Maintenance'
                        WHEN EXISTS (
                            SELECT 1 FROM room_assignments ra
                            WHERE ra.room_id = r.id AND ra.status = 'Active'
                        ) THEN 'Occupied'
                        WHEN r.reserved_by_employee_id IS NOT NULL THEN 'Reserved'
                        ELSE 'Available'
                    END AS effective_status
                FROM rooms r
             )
             SELECT
                r.id,
                r.room_no,
                r.room_type,
                r.effective_status AS status,
                r.capacity,
                r.current_occupancy,
                r.reserved_by_employee_id,
                e.english_name AS reserved_by_name,
                e.employee_code,
                d.department_name,
                b.building_name,
                f.floor_name
             FROM room_state r
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE r.effective_status = 'Reserved'
             ORDER BY b.building_name, f.floor_name, r.room_no ASC"
        );
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'status' => 'Reserved',
            'type' => 'rooms',
            'count' => count($records),
            'records' => $records
        ]);
        return;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load room status details.',
        'details' => $e->getMessage(),
    ]);
}
