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
        $this->ensureRoomRelationshipColumns();
        $this->ensureReservationColumns();
    }

    public function getAll()
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->query(
            "SELECT r.*, COALESCE(r.accommodation_id, b.accommodation_id) AS accommodation_id, r.building_id,
                f.floor_name, b.building_name, a.accommodation_name, e.english_name AS reserved_by_employee_name,
                STRING_AGG(DISTINCT emp.english_name, E'\n' ORDER BY emp.english_name) AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON COALESCE(r.building_id, f.building_id) = b.id
             LEFT JOIN accommodations a ON COALESCE(r.accommodation_id, b.accommodation_id) = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status = 'Active'
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             GROUP BY r.id, r.accommodation_id, r.building_id, f.floor_name, b.building_name, b.accommodation_id, a.accommodation_name, e.english_name
             ORDER BY r.room_no ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByFloor($floorId)
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->prepare(
            "SELECT r.*, COALESCE(r.accommodation_id, b.accommodation_id) AS accommodation_id, r.building_id,
                f.floor_name, b.building_name, a.accommodation_name, e.english_name AS reserved_by_employee_name,
                STRING_AGG(DISTINCT emp.english_name, E'\n' ORDER BY emp.english_name) AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON COALESCE(r.building_id, f.building_id) = b.id
             LEFT JOIN accommodations a ON COALESCE(r.accommodation_id, b.accommodation_id) = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status = 'Active'
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             WHERE r.floor_id = ? OR COALESCE(r.building_id, f.building_id) = ?
             GROUP BY r.id, r.accommodation_id, r.building_id, f.floor_name, b.building_name, b.accommodation_id, a.accommodation_name, e.english_name
             ORDER BY r.room_no ASC"
        );

        $stmt->execute([$floorId, $floorId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $this->assignment->refreshRoomStatuses();

        $stmt = $this->db->prepare(
            "SELECT r.*, COALESCE(r.accommodation_id, b.accommodation_id) AS accommodation_id, COALESCE(r.building_id, f.building_id) AS building_id,
                f.floor_name, b.building_name, a.accommodation_name, e.english_name AS reserved_by_employee_name,
                STRING_AGG(DISTINCT emp.english_name, E'\n' ORDER BY emp.english_name) AS assigned_employee_names
             FROM rooms r
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON COALESCE(r.building_id, f.building_id) = b.id
             LEFT JOIN accommodations a ON COALESCE(r.accommodation_id, b.accommodation_id) = a.id
             LEFT JOIN employees e ON r.reserved_by_employee_id = e.id
             LEFT JOIN room_assignments ra ON ra.room_id = r.id AND ra.status = 'Active'
             LEFT JOIN employees emp ON emp.id = ra.employee_id
             WHERE r.id = ?
             GROUP BY r.id, r.accommodation_id, r.building_id, f.floor_name, f.building_id, b.building_name, b.accommodation_id, a.accommodation_name, e.english_name"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $normalized = $this->normalizeRoomData($data);

        if (!$normalized['valid']) {
            return false;
        }

        $status = $normalized['data']['status'];
        $reservedByEmployeeId = $normalized['data']['reserved_by_employee_id'];
        $genderRestriction = $normalized['data']['gender_restriction'];

        $stmt = $this->db->prepare(
            "INSERT INTO rooms (accommodation_id, building_id, floor_id, room_no, room_type, capacity, current_occupancy, status, reserved_by_employee_id, gender_restriction, remarks)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $normalized['data']['accommodation_id'],
            $normalized['data']['building_id'],
            $normalized['data']['floor_id'],
            $normalized['data']['room_no'],
            $normalized['data']['room_type'],
            $normalized['data']['capacity'],
            $normalized['data']['current_occupancy'],
            $status,
            $reservedByEmployeeId,
            $genderRestriction,
            $normalized['data']['remarks']
        ]);
    }

    public function createRange($baseData, $startRoomNo, $endRoomNo)
    {
        $startRoomNo = trim((string) $startRoomNo);
        $endRoomNo = trim((string) $endRoomNo);

        if ($startRoomNo === '' || $endRoomNo === '') {
            return ['success' => false, 'error' => 'Start and end room numbers are required.'];
        }

        $parsedStart = $this->parseRoomNumber($startRoomNo);
        $parsedEnd = $this->parseRoomNumber($endRoomNo);

        if (!$parsedStart || !$parsedEnd) {
            return ['success' => false, 'error' => 'Room numbers must use a valid numeric or alphanumeric format.'];
        }

        if ($parsedStart['prefix'] !== $parsedEnd['prefix']) {
            return ['success' => false, 'error' => 'The prefix must match for both room numbers.'];
        }

        if ($parsedStart['number'] > $parsedEnd['number']) {
            return ['success' => false, 'error' => 'The end room number must be greater than or equal to the start room number.'];
        }

        $roomNos = [];
        $createdRoomNos = [];
        $startNumber = $parsedStart['number'];
        $endNumber = $parsedEnd['number'];
        $padLength = max($parsedStart['padLength'], $parsedEnd['padLength']);
        $hasLeadingZeros = strlen($parsedStart['rawNumber']) > strlen((string) $parsedStart['number']);

        for ($i = $startNumber; $i <= $endNumber; $i++) {
            $formattedNumber = $hasLeadingZeros
                ? str_pad((string) $i, $padLength, '0', STR_PAD_LEFT)
                : (string) $i;
            $roomNo = $parsedStart['prefix'] . $formattedNumber;

            if ($this->roomNumberExists($roomNo, (int)($baseData['accommodation_id'] ?? 0), !empty($baseData['building_id']) ? (int)$baseData['building_id'] : null, !empty($baseData['floor_id']) ? (int)$baseData['floor_id'] : null)) {
                continue;
            }

            $data = $baseData;
            $data['room_no'] = $roomNo;
            if ($this->create($data)) {
                $createdRoomNos[] = $roomNo;
            }
            $roomNos[] = $roomNo;
        }

        return [
            'success' => true,
            'created_count' => count($createdRoomNos),
            'room_nos' => $createdRoomNos,
            'skipped_room_nos' => array_values(array_diff($roomNos, $createdRoomNos))
        ];
    }

    private function parseRoomNumber($roomNo)
    {
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $roomNo, $matches)) {
            $prefix = $matches[1];
            $number = (int) $matches[2];
            $digits = strlen($matches[2]);
            return ['prefix' => $prefix, 'number' => $number, 'padLength' => $digits, 'rawNumber' => $matches[2]];
        }

        if (preg_match('/^([0-9]+)$/', $roomNo, $matches)) {
            return ['prefix' => '', 'number' => (int) $matches[1], 'padLength' => strlen($matches[1]), 'rawNumber' => $matches[1]];
        }

        return null;
    }

    private function roomNumberExists($roomNo, $accommodationId = null, $buildingId = null, $floorId = null, $excludeId = null)
    {
        $sql = 'SELECT id FROM rooms WHERE room_no = ?';
        $params = [$roomNo];

        if ($accommodationId) {
            $sql .= ' AND accommodation_id = ?';
            $params[] = $accommodationId;
        }

        if ($buildingId) {
            $sql .= ' AND building_id = ?';
            $params[] = $buildingId;
        } elseif ($floorId) {
            $sql .= ' AND floor_id = ?';
            $params[] = $floorId;
        }

        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function update($id, $data)
    {
        $normalized = $this->normalizeRoomData($data, $id);

        if (!$normalized['valid']) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE rooms SET accommodation_id=?, building_id=?, floor_id=?, room_no=?, room_type=?, capacity=?, status=?, reserved_by_employee_id=?, gender_restriction=?, remarks=? WHERE id=?"
        );

        return $stmt->execute([
            $normalized['data']['accommodation_id'],
            $normalized['data']['building_id'],
            $normalized['data']['floor_id'],
            $normalized['data']['room_no'],
            $normalized['data']['room_type'],
            $normalized['data']['capacity'],
            $normalized['data']['status'],
            $normalized['data']['reserved_by_employee_id'],
            $normalized['data']['gender_restriction'],
            $normalized['data']['remarks'],
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

    private function normalizeRoomData(array $data, ?int $excludeId = null): array
    {
        $accommodationId = isset($data['accommodation_id']) ? trim((string) $data['accommodation_id']) : '';
        $buildingId = isset($data['building_id']) ? trim((string) $data['building_id']) : '';
        $floorId = isset($data['floor_id']) ? trim((string) $data['floor_id']) : '';
        $roomNo = trim((string) ($data['room_no'] ?? ''));

        if ($accommodationId === '') {
            return ['valid' => false, 'error' => 'Accommodation is required.'];
        }

        if ($roomNo === '') {
            return ['valid' => false, 'error' => 'Room number is required.'];
        }

        if ($floorId !== '') {
            $floor = $this->fetchFloor((int) $floorId);
            if (!$floor) {
                return ['valid' => false, 'error' => 'Selected floor could not be found.'];
            }

            if ($buildingId === '') {
                $buildingId = (string) $floor['building_id'];
            }

            if ($buildingId !== '' && (string) $floor['building_id'] !== $buildingId) {
                return ['valid' => false, 'error' => 'The selected floor does not belong to the selected building.'];
            }
        }

        if ($buildingId !== '') {
            $building = $this->fetchBuilding((int) $buildingId);
            if (!$building) {
                return ['valid' => false, 'error' => 'Selected building could not be found.'];
            }

            if ((string) $building['accommodation_id'] !== $accommodationId) {
                return ['valid' => false, 'error' => 'The selected building does not belong to the chosen accommodation.'];
            }
        }

        $status = strtoupper(trim((string) ($data['status'] ?? 'Available')));
        if ($status === 'OCCUPIED') {
            $status = 'Occupied';
        } elseif ($status === 'AVAILABLE') {
            $status = 'Available';
        } elseif ($status === 'RESERVED') {
            $status = 'Reserved';
        } elseif ($status === 'MAINTENANCE') {
            $status = 'Maintenance';
        } else {
            $status = in_array($status, ['Available', 'Occupied', 'Reserved', 'Maintenance'], true) ? $status : 'Available';
        }

        $reservedByEmployeeId = null;
        if ($status === 'Reserved') {
            $reservedByEmployeeId = isset($data['reserved_by_employee_id']) ? trim((string) $data['reserved_by_employee_id']) : '';
            if ($reservedByEmployeeId === '') {
                return ['valid' => false, 'error' => 'Please select the employee who reserved this room.'];
            }
        }

        $roomType = trim((string) ($data['room_type'] ?? ''));
        $capacity = trim((string) ($data['capacity'] ?? ''));
        if ($roomType === '' || $capacity === '') {
            return ['valid' => false, 'error' => 'Room type and capacity are required.'];
        }

        $duplicateCheck = $this->roomNumberExists($roomNo, (int) $accommodationId, $buildingId !== '' ? (int) $buildingId : null, $floorId !== '' ? (int) $floorId : null, $excludeId);
        if ($duplicateCheck) {
            return ['valid' => false, 'error' => 'A room with this number already exists in the selected accommodation and location.'];
        }

        return ['valid' => true, 'data' => [
            'accommodation_id' => (int) $accommodationId,
            'building_id' => $buildingId !== '' ? (int) $buildingId : null,
            'floor_id' => $floorId !== '' ? (int) $floorId : null,
            'room_no' => $roomNo,
            'room_type' => $roomType,
            'capacity' => (int) $capacity,
            'current_occupancy' => isset($data['current_occupancy']) ? (int) $data['current_occupancy'] : 0,
            'status' => $status,
            'reserved_by_employee_id' => $reservedByEmployeeId !== null && $reservedByEmployeeId !== '' ? (int) $reservedByEmployeeId : null,
            'gender_restriction' => $this->normalizeGenderRestriction($data['gender_restriction'] ?? ''),
            'remarks' => $data['remarks'] ?? ''
        ]];
    }

    private function fetchBuilding($buildingId)
    {
        $stmt = $this->db->prepare('SELECT id, accommodation_id FROM buildings WHERE id = ? LIMIT 1');
        $stmt->execute([$buildingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function fetchFloor($floorId)
    {
        $stmt = $this->db->prepare('SELECT id, building_id FROM floors WHERE id = ? LIMIT 1');
        $stmt->execute([$floorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function normalizeGenderRestriction($value)
    {
        $normalized = trim((string) ($value ?? ''));

        if ($normalized === '' || $normalized === 'None' || $normalized === 'Any') {
            return 'Any';
        }

        if ($normalized === 'Male' || $normalized === 'Male Only') {
            return 'Male';
        }

        if ($normalized === 'Female' || $normalized === 'Female Only') {
            return 'Female';
        }

        return 'Any';
    }

    private function ensureRoomRelationshipColumns()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $columns = [
            'accommodation_id' => "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS accommodation_id INTEGER NULL",
            'building_id' => "ALTER TABLE rooms ADD COLUMN IF NOT EXISTS building_id INTEGER NULL",
        ];

        foreach ($columns as $columnName => $sql) {
            $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'rooms' AND column_name = '{$columnName}'");
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->db->exec($sql);
            }
        }

        $this->db->exec("UPDATE rooms r
            SET accommodation_id = b.accommodation_id
            FROM floors f
            JOIN buildings b ON b.id = f.building_id
            WHERE r.floor_id = f.id
              AND r.accommodation_id IS NULL");

        $this->db->exec("UPDATE rooms r
            SET building_id = f.building_id
            FROM floors f
            WHERE r.floor_id = f.id
              AND r.building_id IS NULL");

        $checked = true;
    }

    private function ensureReservationColumns()
    {
        static $checked = false;

        if ($checked) {
            return;
        }

        $stmt = $this->db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'rooms' AND column_name = 'reserved_by_employee_id'");
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->db->exec("ALTER TABLE rooms ADD COLUMN reserved_by_employee_id INTEGER DEFAULT NULL");
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
