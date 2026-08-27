<?php

class Trip
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(array $filters = [])
    {
        $sql = "SELECT 
                    t.id,
                    t.employee_id,
                    e.employee_code,
                    e.english_name AS employee_name,
                    d.department_name,
                    t.trip_type,
                    t.status,
                    t.remarks,
                    t.created_at,
                    t.updated_at
                FROM trips t
                JOIN employees e ON t.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";

        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 't.employee_id = ?';
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = ?';
            $params[] = $filters['department_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM trip_legs date_from_leg WHERE date_from_leg.trip_id = t.id AND date_from_leg.leg_date >= ?)';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM trip_legs date_to_leg WHERE date_to_leg.trip_id = t.id AND date_to_leg.leg_date <= ?)';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['trip_type']) && in_array($filters['trip_type'], ['NORMAL_TRIP', 'ROUND_TRIP'])) {
            $conditions[] = 't.trip_type = ?';
            $params[] = $filters['trip_type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['PLANNED', 'ACTIVE', 'COMPLETED', 'CANCELLED'])) {
            $conditions[] = 't.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(
                e.employee_code LIKE ? OR
                e.english_name LIKE ? OR
                d.department_name LIKE ?
            )";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY t.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT 
                t.id,
                t.employee_id,
                e.employee_code,
                e.english_name AS employee_name,
                d.department_name,
                t.trip_type,
                t.status,
                t.remarks,
                t.created_at,
                t.updated_at
            FROM trips t
            JOIN employees e ON t.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE t.id = ?"
        );

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->normalizeInput($data);

        // Check for duplicate active/planned trips
        $duplicate = $this->checkDuplicateTrip($data['employee_id']);
        if ($duplicate) {
            return [
                'success' => false,
                'error' => 'Employee already has an active or planned trip.'
            ];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO trips (employee_id, trip_type, status, remarks, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())"
        );

        $success = $stmt->execute([
            $data['employee_id'],
            $data['trip_type'],
            $data['status'] ?? 'PLANNED',
            $data['remarks'] ?? ''
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to create trip.'];
        }

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function update($id, array $data)
    {
        if (empty($id)) {
            return ['success' => false, 'error' => 'Trip ID is required.'];
        }

        $trip = $this->getById($id);
        if (!$trip) {
            return ['success' => false, 'error' => 'Trip not found.'];
        }

        $data = $this->normalizeInput($data);

        // Validate allowed fields for update
        $updateFields = [];
        $params = [];

        if (isset($data['trip_type'])) {
            if (!in_array($data['trip_type'], ['NORMAL_TRIP', 'ROUND_TRIP'])) {
                return ['success' => false, 'error' => 'Invalid trip type.'];
            }
            $updateFields[] = 'trip_type = ?';
            $params[] = $data['trip_type'];
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], ['PLANNED', 'ACTIVE', 'COMPLETED', 'CANCELLED'])) {
                return ['success' => false, 'error' => 'Invalid trip status.'];
            }
            $updateFields[] = 'status = ?';
            $params[] = $data['status'];
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

        $sql = "UPDATE trips SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update trip.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        if (empty($id)) {
            return ['success' => false, 'error' => 'Trip ID is required.'];
        }

        $trip = $this->getById($id);
        if (!$trip) {
            return ['success' => false, 'error' => 'Trip not found.'];
        }

        $stmt = $this->db->prepare("DELETE FROM trips WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete trip.'];
        }

        return ['success' => true];
    }

    private function validate(array $data)
    {
        if (empty($data['employee_id'])) {
            return ['success' => false, 'error' => 'Employee ID is required.'];
        }

        if (!is_numeric($data['employee_id'])) {
            return ['success' => false, 'error' => 'Invalid employee ID.'];
        }

        // Verify employee exists
        $stmt = $this->db->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$data['employee_id']]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => 'Employee does not exist.'];
        }

        if (empty($data['trip_type'])) {
            return ['success' => false, 'error' => 'Trip type is required.'];
        }

        if (!in_array($data['trip_type'], ['NORMAL_TRIP', 'ROUND_TRIP'])) {
            return ['success' => false, 'error' => 'Invalid trip type. Allowed: NORMAL_TRIP, ROUND_TRIP'];
        }

        if (!empty($data['status']) && !in_array($data['status'], ['PLANNED', 'ACTIVE', 'COMPLETED', 'CANCELLED'])) {
            return ['success' => false, 'error' => 'Invalid trip status.'];
        }

        return ['success' => true];
    }

    private function normalizeInput(array $data)
    {
        $normalized = [];
        $normalized['employee_id'] = isset($data['employee_id']) ? (int) $data['employee_id'] : null;
        $normalized['trip_type'] = isset($data['trip_type']) ? trim((string) $data['trip_type']) : null;
        $normalized['status'] = isset($data['status']) ? trim((string) $data['status']) : 'PLANNED';
        $normalized['remarks'] = isset($data['remarks']) ? trim((string) $data['remarks']) : '';

        return $normalized;
    }

    private function checkDuplicateTrip($employeeId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM trips
             WHERE employee_id = ? AND status IN ('PLANNED', 'ACTIVE')"
        );
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['count'] > 0;
    }
}
