<?php

require_once __DIR__ . '/RoomAssignment.php';

class Room
{
    private $db;
    private $assignment;

    public function __construct($db)
    {
        $this->db = $db;
        $this->assignment = new RoomAssignment($db);
        $this->ensureReservationColumns();
    }

    public function getAll()
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->query(
            "SELECT r.*, f.floor_name, b.building_name, a.accommodation_name, e.full_name AS reserved_by_employee_name,
                    GROUP_CONCAT(DISTINCT emp.full_name ORDER BY emp.full_name SEPARATOR '\n') AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status IN ('Active', 'Transferred')
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             GROUP BY r.id
             ORDER BY r.room_no ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByFloor($floorId)
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->prepare(
            "SELECT r.*, f.floor_name, b.building_name, a.accommodation_name, e.full_name AS reserved_by_employee_name,
                    GROUP_CONCAT(DISTINCT emp.full_name ORDER BY emp.full_name SEPARATOR '\n') AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status IN ('Active', 'Transferred')
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             WHERE r.floor_id=?
             GROUP BY r.id
             ORDER BY r.room_no ASC"
        );

        $stmt->execute([$floorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->prepare(
            "SELECT r.*, f.floor_name, f.building_id AS building_id, b.accommodation_id AS accommodation_id,
                    b.building_name, a.accommodation_name, e.full_name AS reserved_by_employee_name,
                    GROUP_CONCAT(DISTINCT emp.full_name ORDER BY emp.full_name SEPARATOR '\n') AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status IN ('Active', 'Transferred')
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             WHERE r.id=?
             GROUP BY r.id"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $status = $data['status'] ?? 'Available';
        $reservedByEmployeeId = null;

        if ($status === 'Reserved') {
            $reservedByEmployeeId = $data['reserved_by_employee_id'] ?? null;
            if (empty($reservedByEmployeeId)) {
                return false;
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO rooms (floor_id, room_no, room_type, capacity, current_occupancy, status, reserved_by_employee_id, gender_restriction, remarks)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['floor_id'],
            $data['room_no'],
            $data['room_type'],
            $data['capacity'],
            $data['current_occupancy'] ?? 0,
            $status,
            $reservedByEmployeeId,
            $data['gender_restriction'] ?? '',
            $data['remarks'] ?? ''
        ]);
    }

    public function update($id, $data)
    {
        $status = $data['status'] ?? 'Available';
        $reservedByEmployeeId = null;

        if ($status === 'Reserved') {
            $reservedByEmployeeId = $data['reserved_by_employee_id'] ?? null;
            if (empty($reservedByEmployeeId)) {
                return false;
            }
        }

        $stmt = $this->db->prepare(
            "UPDATE rooms SET floor_id=?, room_no=?, room_type=?, capacity=?, status=?, reserved_by_employee_id=?, gender_restriction=?, remarks=? WHERE id=?"
        );

        return $stmt->execute([
            $data['floor_id'],
            $data['room_no'],
            $data['room_type'],
            $data['capacity'],
            $status,
            $reservedByEmployeeId,
            $data['gender_restriction'] ?? '',
            $data['remarks'] ?? '',
            $id
        ]);
    }

    public function updateOccupancy($id, $occupancy)
    {
        $stmt = $this->db->prepare(
            "UPDATE rooms SET current_occupancy=? WHERE id=?"
        );

        return $stmt->execute([$occupancy, $id]);
    }

    public function hasRoomAssignments($roomId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM room_assignments WHERE room_id = ? OR transferred_to_room_id = ?"
        );
        $stmt->execute([$roomId, $roomId]);
        return $stmt->fetchColumn() > 0;
    }

    private function ensureReservationColumns()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM rooms LIKE 'reserved_by_employee_id'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("ALTER TABLE rooms ADD reserved_by_employee_id int(11) DEFAULT NULL AFTER status");
        }

        $checked = true;
    }

    public function delete($id)
    {
        if ($this->hasRoomAssignments($id)) {
            return ['success' => false, 'error' => 'Delete room assignments first before deleting this room.'];
        }

        $stmt = $this->db->prepare(
            "DELETE FROM rooms WHERE id=?"
        );

        try {
            $success = $stmt->execute([$id]);
        } catch (PDOException $e) {
            $dbMessage = $e->getMessage();
            $message = 'Unable to delete room.';

            if (stripos($dbMessage, 'foreign key') !== false || stripos($dbMessage, 'constraint') !== false || stripos($dbMessage, 'SQLSTATE[23000]') !== false) {
                $message = 'Cannot delete room because it is referenced by room assignments or transfers.';
            }

            return ['success' => false, 'error' => $message];
        }

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete room.'];
        }

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Room not found or already deleted.'];
        }

        return ['success' => true];
    }
}
