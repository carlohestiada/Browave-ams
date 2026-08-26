<?php

class TripLeg
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(array $filters = [])
    {
        $sql = "SELECT 
                    tl.id,
                    tl.trip_id,
                    tl.leg_type,
                    tl.leg_date,
                    tl.origin,
                    tl.destination,
                    tl.arrival_airport,
                    tl.departure_airport,
                    tl.remarks,
                    tl.created_at,
                    tl.updated_at,
                    t.employee_id,
                    t.trip_type,
                    t.status
                FROM trip_legs tl
                JOIN trips t ON tl.trip_id = t.id";

        $conditions = [];
        $params = [];

        if (!empty($filters['trip_id'])) {
            $conditions[] = 'tl.trip_id = ?';
            $params[] = $filters['trip_id'];
        }

        if (!empty($filters['leg_type']) && in_array($filters['leg_type'], ['ARRIVAL', 'DEPARTURE'])) {
            $conditions[] = 'tl.leg_type = ?';
            $params[] = $filters['leg_type'];
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY tl.leg_date ASC, tl.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT 
                tl.id,
                tl.trip_id,
                tl.leg_type,
                tl.leg_date,
                tl.origin,
                tl.destination,
                tl.arrival_airport,
                tl.departure_airport,
                tl.remarks,
                tl.created_at,
                tl.updated_at,
                t.employee_id,
                t.trip_type,
                t.status
            FROM trip_legs tl
            JOIN trips t ON tl.trip_id = t.id
            WHERE tl.id = ?"
        );

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByTripId($tripId)
    {
        $stmt = $this->db->prepare(
            "SELECT 
                tl.id,
                tl.trip_id,
                tl.leg_type,
                tl.leg_date,
                tl.origin,
                tl.destination,
                tl.arrival_airport,
                tl.departure_airport,
                tl.remarks,
                tl.created_at,
                tl.updated_at
            FROM trip_legs tl
            WHERE tl.trip_id = ?
            ORDER BY tl.leg_date ASC, tl.id ASC"
        );

        $stmt->execute([$tripId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->normalizeInput($data);

        $stmt = $this->db->prepare(
            "INSERT INTO trip_legs 
             (trip_id, leg_type, leg_date, origin, destination, arrival_airport, departure_airport, remarks, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $success = $stmt->execute([
            $data['trip_id'],
            $data['leg_type'],
            $data['leg_date'],
            $data['origin'],
            $data['destination'],
            $data['arrival_airport'] ?? null,
            $data['departure_airport'] ?? null,
            $data['remarks'] ?? ''
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to create trip leg.'];
        }

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function update($id, array $data)
    {
        if (empty($id)) {
            return ['success' => false, 'error' => 'Trip leg ID is required.'];
        }

        $leg = $this->getById($id);
        if (!$leg) {
            return ['success' => false, 'error' => 'Trip leg not found.'];
        }

        $data = $this->normalizeInput($data);

        $updateFields = [];
        $params = [];

        if (isset($data['leg_type'])) {
            if (!in_array($data['leg_type'], ['ARRIVAL', 'DEPARTURE'])) {
                return ['success' => false, 'error' => 'Invalid leg type.'];
            }
            $updateFields[] = 'leg_type = ?';
            $params[] = $data['leg_type'];
        }

        if (isset($data['leg_date'])) {
            if (!$this->isValidDate($data['leg_date'])) {
                return ['success' => false, 'error' => 'Invalid leg date format.'];
            }
            $updateFields[] = 'leg_date = ?';
            $params[] = $data['leg_date'];
        }

        if (isset($data['origin'])) {
            $updateFields[] = 'origin = ?';
            $params[] = $data['origin'];
        }

        if (isset($data['destination'])) {
            $updateFields[] = 'destination = ?';
            $params[] = $data['destination'];
        }

        if (isset($data['arrival_airport'])) {
            $updateFields[] = 'arrival_airport = ?';
            $params[] = $data['arrival_airport'];
        }

        if (isset($data['departure_airport'])) {
            $updateFields[] = 'departure_airport = ?';
            $params[] = $data['departure_airport'];
        }

        if (isset($data['remarks'])) {
            $updateFields[] = 'remarks = ?';
            $params[] = $data['remarks'];
        }

        if (empty($updateFields)) {
            return ['success' => false, 'error' => 'No valid fields to update.'];
        }

        $updateFields[] = 'updated_at = NOW()';
        $params[] = $id;

        $sql = "UPDATE trip_legs SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update trip leg.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        if (empty($id)) {
            return ['success' => false, 'error' => 'Trip leg ID is required.'];
        }

        $leg = $this->getById($id);
        if (!$leg) {
            return ['success' => false, 'error' => 'Trip leg not found.'];
        }

        $stmt = $this->db->prepare("DELETE FROM trip_legs WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete trip leg.'];
        }

        return ['success' => true];
    }

    private function validate(array $data)
    {
        if (empty($data['trip_id'])) {
            return ['success' => false, 'error' => 'Trip ID is required.'];
        }

        if (!is_numeric($data['trip_id'])) {
            return ['success' => false, 'error' => 'Invalid trip ID.'];
        }

        // Verify trip exists
        $stmt = $this->db->prepare("SELECT id FROM trips WHERE id = ?");
        $stmt->execute([$data['trip_id']]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => 'Trip does not exist.'];
        }

        if (empty($data['leg_type'])) {
            return ['success' => false, 'error' => 'Leg type is required.'];
        }

        if (!in_array($data['leg_type'], ['ARRIVAL', 'DEPARTURE'])) {
            return ['success' => false, 'error' => 'Invalid leg type. Allowed: ARRIVAL, DEPARTURE'];
        }

        if (empty($data['leg_date'])) {
            return ['success' => false, 'error' => 'Leg date is required.'];
        }

        if (!$this->isValidDate($data['leg_date'])) {
            return ['success' => false, 'error' => 'Invalid leg date format. Use YYYY-MM-DD.'];
        }

        if (empty($data['origin'])) {
            return ['success' => false, 'error' => 'Origin is required.'];
        }

        if (empty($data['destination'])) {
            return ['success' => false, 'error' => 'Destination is required.'];
        }

        return ['success' => true];
    }

    private function normalizeInput(array $data)
    {
        $normalized = [];
        $normalized['trip_id'] = isset($data['trip_id']) ? (int) $data['trip_id'] : null;
        $normalized['leg_type'] = isset($data['leg_type']) ? trim((string) $data['leg_type']) : null;
        $normalized['leg_date'] = isset($data['leg_date']) ? trim((string) $data['leg_date']) : null;
        $normalized['origin'] = isset($data['origin']) ? trim((string) $data['origin']) : null;
        $normalized['destination'] = isset($data['destination']) ? trim((string) $data['destination']) : null;
        $normalized['arrival_airport'] = isset($data['arrival_airport']) ? trim((string) $data['arrival_airport']) : null;
        $normalized['departure_airport'] = isset($data['departure_airport']) ? trim((string) $data['departure_airport']) : null;
        $normalized['remarks'] = isset($data['remarks']) ? trim((string) $data['remarks']) : '';

        return $normalized;
    }

    private function isValidDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
