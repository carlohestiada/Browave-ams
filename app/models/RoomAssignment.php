<?php

class RoomAssignment
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureTransferredToColumn();
    }

    public function getAll()
    {
        $this->syncRoomStatuses();

        $stmt = $this->db->query(
            "SELECT ra.*, r.room_no, a.accommodation_name, e.employee_code, e.full_name, e.gender, d.department_name,
                    tr.room_no AS transferred_room_no, ta.accommodation_name AS transferred_accommodation_name
             FROM room_assignments ra
             JOIN employees e ON ra.employee_id = e.id
             JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             LEFT JOIN rooms tr ON ra.transferred_to_room_id = tr.id
             LEFT JOIN floors tf ON tr.floor_id = tf.id
             LEFT JOIN buildings tb ON tf.building_id = tb.id
             LEFT JOIN accommodations ta ON tb.accommodation_id = ta.id
             LEFT JOIN departments d ON e.department_id = d.id
             ORDER BY ra.checkin_date DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasActiveAssignment($employeeId, $excludeAssignmentId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM room_assignments WHERE employee_id = ? AND status = 'Active'";
        $params = [$employeeId];

        if ($excludeAssignmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssignmentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['count'] > 0;
    }

    public function create($data)
    {
        if ($this->hasActiveAssignment($data['employee_id'])) {
            return ['success' => false, 'error' => 'This employee already has an active room assignment. Please check out or transfer the existing room before assigning a new one.'];
        }

        if ($this->roomOccupiedToday($data['room_id'])) {
            return ['success' => false, 'error' => 'The selected room is already occupied. Please choose another available room.'];
        }

        $expectedCheckout = trim($data['expected_checkout_date'] ?? '') ?: $data['checkin_date'];

        $stmt = $this->db->prepare(
            "INSERT INTO room_assignments (employee_id, room_id, checkin_date, expected_checkout_date, status)
             VALUES (?, ?, ?, ?, 'Active')"
        );

        $success = $stmt->execute([
            $data['employee_id'],
            $data['room_id'],
            $data['checkin_date'],
            $expectedCheckout
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Could not create room assignment.'];
        }

        if (!$this->updateRoomStatus($data['room_id'], 'Occupied', 1)) {
            return ['success' => false, 'error' => 'Assignment created, but room status failed to update.'];
        }

        return true;
    }

    public function roomHasActiveAssignment($roomId, $excludeAssignmentId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM room_assignments WHERE room_id = ? AND status = 'Active'";
        $params = [$roomId];

        if ($excludeAssignmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssignmentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['count'] > 0;
    }

    public function transfer($assignmentId, $newRoomId, $transferDate)
    {
        $this->ensureTransferredToColumn();

        $stmt2 = $this->db->prepare("SELECT employee_id, room_id, transferred_to_room_id FROM room_assignments WHERE id=?");
        $stmt2->execute([$assignmentId]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['success' => false, 'error' => 'Original assignment not found.'];
        }

        $currentRoomId = $row['room_id'];

        if ($newRoomId == $currentRoomId) {
            return ['success' => false, 'error' => 'The selected room is the same as the current room. Please choose a different room.'];
        }

        if ($this->roomOccupiedToday($newRoomId, $assignmentId)) {
            return ['success' => false, 'error' => 'The selected room is already assigned. Please choose another room.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE room_assignments
             SET status='Transferred', actual_checkout_date=?, transferred_to_room_id=?
             WHERE id=?"
        );
        if (!$stmt->execute([$transferDate, $newRoomId, $assignmentId])) {
            return ['success' => false, 'error' => 'Could not update the transfer.'];
        }

        $this->syncRoomStatuses([$row['transferred_to_room_id'] ?? null]);

        return ['success' => true];
    }

    public function delete($assignmentId)
    {
        $stmt = $this->db->prepare("SELECT id, room_id, transferred_to_room_id FROM room_assignments WHERE id=?");
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return ['success' => false, 'error' => 'Room assignment not found.'];
        }

        $delete = $this->db->prepare("DELETE FROM room_assignments WHERE id=?");
        if (!$delete->execute([$assignmentId])) {
            return ['success' => false, 'error' => 'Could not delete room assignment.'];
        }

        $this->syncRoomStatuses([$assignment['room_id'], $assignment['transferred_to_room_id']]);

        return ['success' => true];
    }

    private function roomOccupiedToday($roomId, $excludeAssignmentId = null)
    {
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) AS count
                FROM room_assignments
                WHERE status IN ('Active', 'Transferred')
                  AND (
                    (status = 'Active' AND room_id = ?)
                    OR (status = 'Transferred' AND actual_checkout_date > ? AND room_id = ?)
                    OR (status = 'Transferred' AND actual_checkout_date <= ? AND transferred_to_room_id = ?)
                  )";
        $params = [$roomId, $today, $roomId, $today, $roomId];

        if ($excludeAssignmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssignmentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['count'] > 0;
    }

    public function refreshRoomStatuses()
    {
        $this->syncRoomStatuses();
    }

    private function syncRoomStatuses($extraRoomIds = [])
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT room_id, transferred_to_room_id, actual_checkout_date, status
             FROM room_assignments
             WHERE status IN ('Active', 'Transferred')"
        );
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $touchedRooms = [];
        $occupiedRooms = [];

        foreach ($assignments as $assignment) {
            if (!empty($assignment['room_id'])) {
                $touchedRooms[(int)$assignment['room_id']] = true;
            }
            if (!empty($assignment['transferred_to_room_id'])) {
                $touchedRooms[(int)$assignment['transferred_to_room_id']] = true;
            }

            if ($assignment['status'] === 'Transferred'
                && !empty($assignment['transferred_to_room_id'])
                && !empty($assignment['actual_checkout_date'])
                && $assignment['actual_checkout_date'] <= $today) {
                $occupiedRooms[(int)$assignment['transferred_to_room_id']] = true;
                continue;
            }

            $occupiedRooms[(int)$assignment['room_id']] = true;
        }

        foreach ($extraRoomIds as $roomId) {
            if (!empty($roomId)) {
                $touchedRooms[(int)$roomId] = true;
            }
        }

        foreach (array_keys($touchedRooms) as $roomId) {
            $isOccupied = isset($occupiedRooms[$roomId]);
            $this->updateRoomStatus($roomId, $isOccupied ? 'Occupied' : 'Available', $isOccupied ? 1 : 0);
        }
    }

    private function ensureTransferredToColumn()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM room_assignments LIKE 'transferred_to_room_id'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("ALTER TABLE room_assignments ADD transferred_to_room_id int(11) DEFAULT NULL AFTER room_id");
        }

        $checked = true;
    }

    private function updateRoomStatus($roomId, $status, $occupancy = null)
    {
        if ($occupancy === null) {
            $stmt = $this->db->prepare("UPDATE rooms SET status=? WHERE id=?");
            return $stmt->execute([$status, $roomId]);
        }

        $stmt = $this->db->prepare("UPDATE rooms SET status=?, current_occupancy=? WHERE id=?");
        return $stmt->execute([$status, $occupancy, $roomId]);
    }
}
