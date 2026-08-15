<?php
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

    // Occupied - get employees in occupied rooms
    if ($status === 'Occupied') {
        $stmt = $db->prepare(
            "SELECT DISTINCT
                e.id,
                e.employee_code,
                e.english_name,
                e.chinese_name,
                e.gender,
                d.department_name,
                d.location,
                r.room_no,
                b.building_name,
                f.floor_name
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN room_assignments ra ON e.id = ra.employee_id
             LEFT JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE ra.status = 'Active'
                OR (ra.status = 'Transferred' 
                    AND (
                        (ra.actual_checkout_date > CURRENT_DATE AND ra.room_id = r.id)
                        OR (ra.actual_checkout_date <= CURRENT_DATE AND ra.transferred_to_room_id = r.id)
                    ))
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
            "SELECT 
                r.id,
                r.room_no,
                r.room_type,
                b.building_name,
                f.floor_name
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE r.status = 'Available'
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
            "SELECT 
                r.id,
                r.room_no,
                r.room_type,
                r.reserved_by_employee_id,
                e.english_name AS reserved_by_name,
                e.employee_code,
                d.department_name,
                b.building_name,
                f.floor_name
             FROM rooms r
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             WHERE r.status = 'Reserved'
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
