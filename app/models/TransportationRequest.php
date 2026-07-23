<?php

class TransportationRequest
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(array $filters = [])
    {
        $sql = "SELECT tr.*, e.employee_code, e.full_name, e.chinese_name, e.gender, d.department_name, dr.driver_name, v.vehicle_name, v.license_plate
                FROM transportation_requests tr
                JOIN employees e ON tr.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN drivers dr ON tr.driver_id = dr.id
                LEFT JOIN vehicles v ON tr.vehicle_id = v.id";

        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 'tr.employee_id = ?';
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['pickup_date'])) {
            $conditions[] = 'tr.pickup_date = ?';
            $params[] = $filters['pickup_date'];
        }

        if (!empty($filters['transportation_type'])) {
            $conditions[] = 'tr.transportation_type = ?';
            $params[] = $filters['transportation_type'];
        }

        if (!empty($filters['vehicle_id'])) {
            $conditions[] = 'tr.vehicle_id = ?';
            $params[] = $filters['vehicle_id'];
        }

        if (!empty($filters['driver_id'])) {
            $conditions[] = 'tr.driver_id = ?';
            $params[] = $filters['driver_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'tr.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(
                e.employee_code LIKE ? OR
                e.full_name LIKE ? OR
                e.chinese_name LIKE ? OR
                d.department_name LIKE ? OR
                tr.pickup_location LIKE ? OR
                dr.driver_name LIKE ? OR
                v.vehicle_name LIKE ?
            )";
            $searchTerm = '%' . $filters['search'] . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY tr.pickup_date DESC, tr.pickup_time ASC, tr.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT tr.*, e.employee_code, e.full_name, e.chinese_name, e.gender, d.department_name, dr.driver_name, v.vehicle_name, v.license_plate
             FROM transportation_requests tr
             JOIN employees e ON tr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN drivers dr ON tr.driver_id = dr.id
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             WHERE tr.id = ?"
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

        $stmt = $this->db->prepare(
            "INSERT INTO transportation_requests
             (employee_id, transportation_type, driver_id, vehicle_id, pickup_date, pickup_time, pickup_location, status, remarks)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $success = $stmt->execute([
            $data['employee_id'],
            $data['transportation_type'],
            $data['driver_id'],
            $data['vehicle_id'],
            $data['pickup_date'],
            $data['pickup_time'],
            $data['pickup_location'],
            $data['status'],
            $data['remarks']
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to save transportation request.'];
        }

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function update($id, array $data)
    {
        $validation = $this->validate($data, $id);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->normalizeInput($data);

        $stmt = $this->db->prepare(
            "UPDATE transportation_requests SET
             employee_id = ?,
             transportation_type = ?,
             driver_id = ?,
             vehicle_id = ?,
             pickup_date = ?,
             pickup_time = ?,
             pickup_location = ?,
             status = ?,
             remarks = ?
             WHERE id = ?"
        );

        $success = $stmt->execute([
            $data['employee_id'],
            $data['transportation_type'],
            $data['driver_id'],
            $data['vehicle_id'],
            $data['pickup_date'],
            $data['pickup_time'],
            $data['pickup_location'],
            $data['status'],
            $data['remarks'],
            $id
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update transportation request.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM transportation_requests WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete transportation request.'];
        }

        return ['success' => true];
    }

    public function getStats()
    {
        $today = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN pickup_date = ? THEN 1 ELSE 0 END) AS todays_requests,
                SUM(CASE WHEN pickup_date = ? AND status IN ('Scheduled', 'Pending', 'Picked Up') THEN 1 ELSE 0 END) AS scheduled_today,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_assignment
             FROM transportation_requests"
        );
        $stmt->execute([$today, $today]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $vehicleStmt = $this->db->prepare("SELECT COUNT(*) AS available_vehicles FROM vehicles WHERE status = 'Available'");
        $vehicleStmt->execute();
        $vehicleRow = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'todays_requests' => (int) ($row['todays_requests'] ?? 0),
            'scheduled_today' => (int) ($row['scheduled_today'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'pending_assignment' => (int) ($row['pending_assignment'] ?? 0),
            'available_vehicles' => (int) ($vehicleRow['available_vehicles'] ?? 0)
        ];
    }

    public function getEmployeeDetails($employeeId)
    {
        $stmt = $this->db->prepare(
            "SELECT e.id, e.employee_code, e.full_name, e.chinese_name, e.gender, d.department_name,
                (SELECT transaction_date FROM transactions t2 WHERE t2.employee_id = e.id AND t2.transaction_type = 'arrival' ORDER BY transaction_date DESC LIMIT 1) AS last_arrival_date,
                (SELECT transaction_date FROM transactions t3 WHERE t3.employee_id = e.id AND t3.transaction_type = 'departure' ORDER BY transaction_date DESC LIMIT 1) AS last_departure_date,
                r.room_no AS room_number,
                a.accommodation_name AS accommodation_name
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN room_assignments ra ON ra.employee_id = e.id AND ra.status = 'Active'
             LEFT JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             WHERE e.id = ?"
        );
        $stmt->execute([$employeeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $required = ['employee_id', 'transportation_type', 'pickup_date', 'pickup_time', 'pickup_location', 'status'];

        foreach ($required as $field) {
            if (empty($data[$field]) && $data[$field] !== '0') {
                return ['success' => false, 'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
            }
        }

        if (!in_array($data['status'], ['Pending', 'Scheduled', 'Picked Up', 'Completed', 'Cancelled'], true)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        if (!in_array($data['transportation_type'], ['Company Car', 'Airport Transfer', 'Shuttle Service', 'Private Hire', 'Other'], true)) {
            return ['success' => false, 'error' => 'Invalid transportation type'];
        }

        if (!$this->employeeExists($data['employee_id'])) {
            return ['success' => false, 'error' => 'Employee is not valid'];
        }

        $conflict = $this->checkAssignmentConflicts($data, $excludeId);
        if ($conflict !== null) {
            return ['success' => false, 'error' => $conflict];
        }

        return ['success' => true];
    }

    private function normalizeInput(array $data): array
    {
        return [
            'employee_id' => (int) $data['employee_id'],
            'transportation_type' => trim($data['transportation_type']),
            'driver_id' => empty($data['driver_id']) ? null : (int) $data['driver_id'],
            'vehicle_id' => empty($data['vehicle_id']) ? null : (int) $data['vehicle_id'],
            'pickup_date' => trim($data['pickup_date']),
            'pickup_time' => trim($data['pickup_time']),
            'pickup_location' => trim($data['pickup_location']),
            'status' => trim($data['status']),
            'remarks' => trim($data['remarks'] ?? ''),
        ];
    }

    private function employeeExists($employeeId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && (int) $row['count'] > 0;
    }

    private function checkAssignmentConflicts(array $data, ?int $excludeId = null)
    {
        $data = $this->normalizeInput($data);

        if (!empty($data['driver_id'])) {
            $conflict = $this->findConflict('driver_id', $data['driver_id'], $data['pickup_date'], $data['pickup_time'], $excludeId);
            if ($conflict) {
                return 'Selected driver is already assigned to another pickup at the same date and time.';
            }
        }

        if (!empty($data['vehicle_id'])) {
            $conflict = $this->findConflict('vehicle_id', $data['vehicle_id'], $data['pickup_date'], $data['pickup_time'], $excludeId);
            if ($conflict) {
                return 'Selected vehicle is already assigned to another pickup at the same date and time.';
            }
        }

        return null;
    }

    private function findConflict(string $field, int $value, string $pickupDate, string $pickupTime, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM transportation_requests WHERE {$field} = ? AND pickup_date = ? AND pickup_time = ? AND status IN ('Pending', 'Scheduled', 'Picked Up')";
        $params = [$value, $pickupDate, $pickupTime];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && (int) $row['count'] > 0;
    }
}
