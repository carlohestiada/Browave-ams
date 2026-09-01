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
            "SELECT ra.*, r.room_no, a.accommodation_name, e.employee_code, e.english_name, e.gender, d.department_name,
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
             WHERE ra.status = 'Active'
               AND ra.id = (
                   SELECT ra2.id
                   FROM room_assignments ra2
                   WHERE ra2.employee_id = ra.employee_id
                     AND ra2.status = 'Active'
                   ORDER BY ra2.checkin_date DESC, ra2.id DESC
                   LIMIT 1
               )
             ORDER BY ra.checkin_date DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($assignmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT ra.*, r.room_no, e.employee_code, e.english_name, e.gender, d.department_name,
                    f.floor_name AS floor_name, bf.building_name AS building_name, af.accommodation_name AS accommodation_name,
                    tr.room_no AS transferred_room_no, tf.floor_name AS transferred_floor_name,
                    tb.building_name AS transferred_building_name, ta.accommodation_name AS transferred_accommodation_name
             FROM room_assignments ra
             JOIN employees e ON ra.employee_id = e.id
             JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings bf ON f.building_id = bf.id
             LEFT JOIN accommodations af ON bf.accommodation_id = af.id
             LEFT JOIN rooms tr ON ra.transferred_to_room_id = tr.id
             LEFT JOIN floors tf ON tr.floor_id = tf.id
             LEFT JOIN buildings tb ON tf.building_id = tb.id
             LEFT JOIN accommodations ta ON tb.accommodation_id = ta.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE ra.id = ?"
        );
        $stmt->execute([$assignmentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEmployeeHistory($employeeId)
    {
        $stmt = $this->db->prepare(
            "SELECT ra.*, r.room_no, e.employee_code, e.english_name, e.gender, d.department_name,
                    f.floor_name AS floor_name, bf.building_name AS building_name, af.accommodation_name AS accommodation_name,
                    tr.room_no AS transferred_room_no, tf.floor_name AS transferred_floor_name,
                    tb.building_name AS transferred_building_name, ta.accommodation_name AS transferred_accommodation_name
             FROM room_assignments ra
             JOIN employees e ON ra.employee_id = e.id
             JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings bf ON f.building_id = bf.id
             LEFT JOIN accommodations af ON bf.accommodation_id = af.id
             LEFT JOIN rooms tr ON ra.transferred_to_room_id = tr.id
             LEFT JOIN floors tf ON tr.floor_id = tf.id
             LEFT JOIN buildings tb ON tf.building_id = tb.id
             LEFT JOIN accommodations ta ON tb.accommodation_id = ta.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE ra.employee_id = ?
             ORDER BY ra.checkin_date DESC, ra.id DESC"
        );
        $stmt->execute([$employeeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAssignmentDetails($assignmentId)
    {
        $assignment = $this->getById($assignmentId);
        if (!$assignment) {
            return null;
        }

        $history = $this->getEmployeeHistory($assignment['employee_id']);
        return [
            'assignment' => $assignment,
            'history' => $history,
            'employee' => [
                'id' => $assignment['employee_id'],
                'employee_code' => $assignment['employee_code'],
                'english_name' => $assignment['english_name'],
                'gender' => $assignment['gender'],
                'department_name' => $assignment['department_name'],
            ],
        ];
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

        if ($this->roomIsReserved($data['room_id'], $data['employee_id'])) {
            return ['success' => false, 'error' => 'This room is reserved by another employee. Please choose another room or remove the reservation first.'];
        }

        if (!$this->roomHasCapacity($data['room_id'])) {
            return ['success' => false, 'error' => 'This room has reached its maximum capacity. Please choose another room.'];
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

        $occupancyCount = $this->countRoomOccupants($data['room_id']);
        if (!$this->updateRoomStatus($data['room_id'], 'Occupied', $occupancyCount)) {
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

    public function updateAssignment($assignmentId, $data)
    {
        $stmt = $this->db->prepare(
            "SELECT id, employee_id, room_id, checkin_date, expected_checkout_date, status
             FROM room_assignments WHERE id=?"
        );
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return ['success' => false, 'error' => 'Room assignment not found.'];
        }

        if (($assignment['status'] ?? '') !== 'Active') {
            return ['success' => false, 'error' => 'Only active room assignments can be updated here.'];
        }

        $employeeId = (int) ($assignment['employee_id'] ?? 0);
        $newRoomId = isset($data['new_room_id']) ? (int) $data['new_room_id'] : (isset($data['room_id']) ? (int) $data['room_id'] : (int) $assignment['room_id']);
        $checkinDate = trim((string) ($data['checkin_date'] ?? $assignment['checkin_date']));
        $checkoutDate = trim((string) ($data['expected_checkout_date'] ?? $assignment['expected_checkout_date']));

        if (!$newRoomId || !$checkinDate || !$checkoutDate) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }

        if ($checkoutDate < $checkinDate) {
            return ['success' => false, 'error' => 'Check-out date cannot be before check-in date.'];
        }

        if ((int) $assignment['room_id'] !== (int) $newRoomId) {
            $result = $this->transfer($assignmentId, $newRoomId, $checkinDate);
            if (is_array($result) && !$result['success']) {
                return $result;
            }
            return ['success' => true];
        }

        if ($this->roomConflict($newRoomId, $employeeId, $checkinDate, $checkoutDate, $assignmentId)) {
            return ['success' => false, 'error' => 'This room is already assigned during the selected dates. Please select another room.'];
        }

        $update = $this->db->prepare(
            "UPDATE room_assignments
             SET checkin_date=?, expected_checkout_date=?
             WHERE id=?"
        );
        if (!$update->execute([$checkinDate, $checkoutDate, $assignmentId])) {
            return ['success' => false, 'error' => 'Could not update room assignment.'];
        }

        $this->syncRoomStatuses([$newRoomId]);
        return ['success' => true];
    }

    public function transfer($assignmentId, $newRoomId, $transferDate)
    {
        $this->ensureTransferredToColumn();

        // Fetch the original assignment with all details
        $stmt2 = $this->db->prepare(
            "SELECT id, employee_id, room_id, checkin_date, expected_checkout_date, transferred_to_room_id, status 
             FROM room_assignments WHERE id=?"
        );
        $stmt2->execute([$assignmentId]);
        $oldAssignment = $stmt2->fetch(PDO::FETCH_ASSOC);
        if (!$oldAssignment) {
            return ['success' => false, 'error' => 'Original assignment not found.'];
        }

        if (($oldAssignment['status'] ?? '') !== 'Active') {
            return ['success' => false, 'error' => 'Only active room assignments can be transferred.'];
        }

        $currentRoomId = $oldAssignment['room_id'];
        $employeeId = $oldAssignment['employee_id'];

        $duplicateCheck = $this->db->prepare(
            "SELECT COUNT(*) AS count FROM room_assignments WHERE employee_id = ? AND status = 'Active' AND id != ?"
        );
        $duplicateCheck->execute([$employeeId, $assignmentId]);
        $duplicateActive = $duplicateCheck->fetch(PDO::FETCH_ASSOC);
        if (($duplicateActive['count'] ?? 0) > 0) {
            return ['success' => false, 'error' => 'This employee already has an active room assignment. Please resolve the current assignment before transferring again.'];
        }

        if ($newRoomId == $currentRoomId) {
            return ['success' => false, 'error' => 'The selected room is the same as the current room. Please choose a different room.'];
        }

        if ($this->roomIsReserved($newRoomId, $employeeId)) {
            return ['success' => false, 'error' => 'The selected room is reserved by another employee. Please choose another room or remove the reservation first.'];
        }

        if (!$this->roomHasCapacity($newRoomId, $assignmentId)) {
            return ['success' => false, 'error' => 'The selected room has reached its maximum capacity. Please choose another room.'];
        }

        if ($this->roomConflict($newRoomId, $employeeId, $transferDate, $oldAssignment['expected_checkout_date'] ?? $transferDate, $assignmentId)) {
            return ['success' => false, 'error' => 'This room is already assigned during the selected dates. Please select another room.'];
        }

        // Start transaction
        try {
            $this->db->beginTransaction();

            // Step 1: Close the old assignment
            $updateOld = $this->db->prepare(
                "UPDATE room_assignments
                 SET status='Transferred', actual_checkout_date=?, transferred_to_room_id=?
                 WHERE id=?"
            );
            if (!$updateOld->execute([$transferDate, $newRoomId, $assignmentId])) {
                throw new Exception('Could not close the old assignment.');
            }

            // Step 2: Create a new Active assignment for the new room
            $insertNew = $this->db->prepare(
                "INSERT INTO room_assignments (employee_id, room_id, checkin_date, expected_checkout_date, status)
                 VALUES (?, ?, ?, ?, 'Active')"
            );
            if (!$insertNew->execute([
                $employeeId,
                $newRoomId,
                $transferDate,
                $oldAssignment['expected_checkout_date']
            ])) {
                throw new Exception('Could not create new assignment for the new room.');
            }

            // Step 3: Sync room statuses for both old and new rooms
            $this->syncRoomStatuses([$currentRoomId, $newRoomId]);

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($assignmentId)
    {
        $stmt = $this->db->prepare("SELECT id, room_id, status FROM room_assignments WHERE id=?");
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            return ['success' => false, 'error' => 'Room assignment not found.'];
        }

        if (($assignment['status'] ?? '') !== 'Active') {
            return ['success' => false, 'error' => 'Only the active assignment can be deleted from this screen.'];
        }

        $delete = $this->db->prepare("DELETE FROM room_assignments WHERE id=?");
        if (!$delete->execute([$assignmentId])) {
            return ['success' => false, 'error' => 'Could not delete room assignment.'];
        }

        // Only sync the room that was assigned (transferred_to_room_id is no longer used)
        $this->syncRoomStatuses([$assignment['room_id']]);

        return ['success' => true];
    }

    private function roomOccupiedToday($roomId, $excludeAssignmentId = null)
    {
        return $this->countRoomOccupants($roomId, $excludeAssignmentId) > 0;
    }

    private function roomConflict($roomId, $employeeId, $checkinDate, $checkoutDate, $excludeAssignmentId = null)
    {
        $checkinDate = trim((string) $checkinDate);
        $checkoutDate = trim((string) $checkoutDate);

        if ($checkinDate === '' || $checkoutDate === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) AS count
                FROM room_assignments
                WHERE room_id = ?
                  AND status = 'Active'
                  AND employee_id != ?";
        $params = [$roomId, $employeeId];

        if ($excludeAssignmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssignmentId;
        }

        $sql .= " AND checkin_date <= ? AND expected_checkout_date >= ?";
        $params[] = $checkoutDate;
        $params[] = $checkinDate;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['count'] ?? 0) > 0;
    }

    private function roomHasCapacity($roomId, $excludeAssignmentId = null)
    {
        $capacity = $this->getRoomCapacity($roomId);
        if ($capacity === null || $capacity <= 0) {
            return true;
        }

        return $this->countRoomOccupants($roomId, $excludeAssignmentId) < $capacity;
    }

    private function getRoomCapacity($roomId)
    {
        $stmt = $this->db->prepare("SELECT capacity FROM rooms WHERE id=?");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['capacity'] : null;
    }

    private function countRoomOccupants($roomId, $excludeAssignmentId = null)
    {
        // Count only Active assignments for the room (after transfer, new active assignment is in new room)
        $sql = "SELECT COUNT(*) AS count
                FROM room_assignments
                WHERE status = 'Active'
                  AND room_id = ?";
        $params = [$roomId];

        if ($excludeAssignmentId) {
            $sql .= " AND id != ?";
            $params[] = $excludeAssignmentId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['count'] ?? 0);
    }

    public function refreshRoomStatuses()
    {
        $this->syncRoomStatuses();
    }

    private function syncRoomStatuses($extraRoomIds = [])
    {
        // Get all active assignments - these determine room occupancy
        $stmt = $this->db->prepare(
            "SELECT room_id
             FROM room_assignments
             WHERE status = 'Active'"
        );
        $stmt->execute();
        $activeAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $touchedRooms = [];

        // Mark rooms that have active assignments
        foreach ($activeAssignments as $assignment) {
            if (!empty($assignment['room_id'])) {
                $touchedRooms[(int)$assignment['room_id']] = true;
            }
        }

        // Also include explicitly provided rooms to sync
        foreach ($extraRoomIds as $roomId) {
            if (!empty($roomId)) {
                $touchedRooms[(int)$roomId] = true;
            }
        }

        // Update status for all touched rooms
        foreach (array_keys($touchedRooms) as $roomId) {
            $occupancyCount = $this->countRoomOccupants($roomId);
            $isOccupied = $occupancyCount > 0;
            $roomStatus = $this->getRoomDisplayStatus($roomId);
            $statusToSet = in_array($roomStatus, ['Reserved', 'Maintenance'], true)
                ? $roomStatus
                : ($isOccupied ? 'Occupied' : 'Available');
            $this->updateRoomStatus($roomId, $statusToSet, $occupancyCount);
        }
    }

    private function ensureTransferredToColumn()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'room_assignments' AND column_name = 'transferred_to_room_id'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("ALTER TABLE room_assignments ADD COLUMN transferred_to_room_id INTEGER DEFAULT NULL");
        }

        $checked = true;
    }

    private function roomIsReserved($roomId, $employeeId)
    {
        $stmt = $this->db->prepare(
            "SELECT reserved_by_employee_id FROM rooms WHERE id=?"
        );
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['reserved_by_employee_id'])) {
            return false;
        }

        return (int)$row['reserved_by_employee_id'] !== (int)$employeeId;
    }

    private function getRoomDisplayStatus($roomId)
    {
        $stmt = $this->db->prepare("SELECT status FROM rooms WHERE id=?");
        $stmt->execute([$roomId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['status'] ?? 'Available';
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
