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
        $sql = "SELECT
                    t.id AS trip_id,
                    t.employee_id,
                    e.employee_code,
                    e.english_name,
                    e.chinese_name,
                    e.gender,
                    d.department_name,
                    MIN(tr.id) AS id,
                    MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN tl.leg_date END) AS arrival_date,
                    MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN tl.leg_date END) AS departure_date,
                    COALESCE(
                        MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN tr.transportation_type END),
                        MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN tr.transportation_type END)
                    ) AS transportation_type,
                    COALESCE(
                        MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN dr.driver_name END),
                        MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN dr.driver_name END)
                    ) AS driver_name,
                    COALESCE(
                        MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN v.vehicle_name END),
                        MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN v.vehicle_name END)
                    ) AS vehicle_name,
                    COALESCE(
                        MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN tr.pickup_location END),
                        MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN tr.pickup_location END)
                    ) AS pickup_location,
                    COALESCE(
                        MIN(CASE WHEN tl.leg_type = 'ARRIVAL' THEN tr.status END),
                        MIN(CASE WHEN tl.leg_type = 'DEPARTURE' THEN tr.status END)
                    ) AS status,
                    MIN(tr.remarks) AS remarks,
                    MIN(tl.id) AS arrival_trip_leg_id,
                    MAX(tl.id) AS departure_trip_leg_id,
                    MIN(tl.arrival_airport) AS arrival_airport,
                    MIN(tl.departure_airport) AS departure_airport
                FROM trips t
                JOIN employees e ON t.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN trip_legs tl ON tl.trip_id = t.id
                LEFT JOIN transportation_requests tr ON tr.trip_leg_id = tl.id
                LEFT JOIN drivers dr ON tr.driver_id = dr.id
                LEFT JOIN vehicles v ON tr.vehicle_id = v.id
                WHERE t.id IS NOT NULL";

        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = 't.employee_id = ?';
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['trip_id'])) {
            $conditions[] = 't.id = ?';
            $params[] = $filters['trip_id'];
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

        if (!empty($filters['leg_type'])) {
            $conditions[] = 'tl1.leg_type = ? OR tl2.leg_type = ?';
            $params[] = $filters['leg_type'];
            $params[] = $filters['leg_type'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(
                e.employee_code LIKE ? OR
                e.english_name LIKE ? OR
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
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY t.id, t.employee_id, e.employee_code, e.english_name, e.chinese_name, e.gender, d.department_name, dr.driver_name, v.vehicle_name, v.license_plate, t.trip_type, t.status
                ORDER BY MIN(tr.pickup_date) DESC, MIN(tr.pickup_time) ASC, t.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get transportation for a specific trip leg
     * Phase 4: Used to display transportation in trip details
     */
    public function getByTripLegId($tripLegId)
    {
        $stmt = $this->db->prepare(
            "SELECT tr.*, 
                e.employee_code, e.english_name, e.chinese_name, e.gender, 
                d.department_name, 
                dr.driver_name, 
                v.vehicle_name, v.license_plate,
                t.id AS trip_id, t.trip_type, t.status AS trip_status,
                tl.id AS trip_leg_id_val, tl.leg_type, tl.leg_date, tl.origin, tl.destination,
                tl.arrival_airport, tl.departure_airport
             FROM transportation_requests tr
             JOIN employees e ON tr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN drivers dr ON tr.driver_id = dr.id
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             LEFT JOIN trip_legs tl ON tr.trip_leg_id = tl.id
             LEFT JOIN trips t ON tl.trip_id = t.id
             WHERE tr.trip_leg_id = ?"
        );
        $stmt->execute([$tripLegId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTripDetails(int $tripId): ?array
    {
        $trip = $this->db->prepare(
            "SELECT
                t.id AS trip_id,
                t.employee_id,
                e.employee_code,
                e.english_name,
                e.chinese_name,
                d.department_name,
                t.trip_type,
                t.status AS trip_status,
                t.remarks
             FROM trips t
             JOIN employees e ON t.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE t.id = ?"
        );
        $trip->execute([$tripId]);
        $tripRow = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$tripRow) {
            return null;
        }

        $legs = $this->db->prepare(
            "SELECT
                tl.id AS trip_leg_id,
                tl.leg_type,
                tl.leg_date,
                tl.origin,
                tl.destination,
                tl.arrival_airport,
                tl.departure_airport,
                tr.id AS transportation_id,
                tr.transportation_type,
                tr.driver_id,
                dr.driver_name,
                tr.vehicle_id,
                v.vehicle_name,
                tr.pickup_date,
                tr.pickup_time,
                tr.pickup_location,
                tr.status,
                tr.remarks AS transportation_remarks
             FROM trip_legs tl
             LEFT JOIN transportation_requests tr ON tr.trip_leg_id = tl.id
             LEFT JOIN drivers dr ON tr.driver_id = dr.id
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             WHERE tl.trip_id = ?
             ORDER BY tl.leg_type ASC, tl.leg_date ASC"
        );
        $legs->execute([$tripId]);
        $legRows = $legs->fetchAll(PDO::FETCH_ASSOC);

        $tripRow['legs'] = $legRows;
        return $tripRow;
    }

    public function updateTripLegStatuses(int $tripId, array $data): array
    {
        $allowed = ['Pending', 'Scheduled', 'Picked Up', 'Completed', 'Cancelled'];
        $arrivalStatus = trim((string) ($data['arrival_status'] ?? ''));
        $departureStatus = trim((string) ($data['departure_status'] ?? ''));
        if (!in_array($arrivalStatus, $allowed, true) && $arrivalStatus !== '') {
            return ['success' => false, 'error' => 'Invalid arrival status'];
        }
        if (!in_array($departureStatus, $allowed, true) && $departureStatus !== '') {
            return ['success' => false, 'error' => 'Invalid departure status'];
        }

        $arrivalLeg = $this->db->prepare("SELECT id FROM trip_legs WHERE trip_id = ? AND leg_type = 'ARRIVAL' LIMIT 1");
        $arrivalLeg->execute([$tripId]);
        $arrivalLegId = $arrivalLeg->fetchColumn();

        $departureLeg = $this->db->prepare("SELECT id FROM trip_legs WHERE trip_id = ? AND leg_type = 'DEPARTURE' LIMIT 1");
        $departureLeg->execute([$tripId]);
        $departureLegId = $departureLeg->fetchColumn();

        if (!$arrivalLegId && !$departureLegId) {
            return ['success' => false, 'error' => 'Trip has no legs'];
        }

        $this->db->beginTransaction();
        try {
            if ($arrivalLegId && $arrivalStatus !== '') {
                $this->db->prepare("UPDATE transportation_requests SET status = ? WHERE trip_leg_id = ?")->execute([$arrivalStatus, $arrivalLegId]);
            }
            if ($departureLegId && $departureStatus !== '') {
                $this->db->prepare("UPDATE transportation_requests SET status = ? WHERE trip_leg_id = ?")->execute([$departureStatus, $departureLegId]);
            }
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Unable to save leg status changes'];
        }
    }

    public function getById($id)
    {
        // Include trip and trip_leg context for Phase 4 integration
        $stmt = $this->db->prepare(
            "SELECT tr.*, 
                e.employee_code, e.english_name, e.chinese_name, e.gender, 
                d.department_name, 
                dr.driver_name, 
                v.vehicle_name, v.license_plate,
                t.id AS trip_id, t.trip_type, t.status AS trip_status,
                tl.id AS trip_leg_id_val, tl.leg_type, tl.leg_date, tl.origin, tl.destination
             FROM transportation_requests tr
             JOIN employees e ON tr.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN drivers dr ON tr.driver_id = dr.id
             LEFT JOIN vehicles v ON tr.vehicle_id = v.id
             LEFT JOIN trip_legs tl ON tr.trip_leg_id = tl.id
             LEFT JOIN trips t ON tl.trip_id = t.id
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
             (employee_id, transportation_type, driver_id, vehicle_id, pickup_date, pickup_time, pickup_location, status, remarks, trip_leg_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
            $data['trip_leg_id']
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to save transportation request.'];
        }

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function createBulk(array $data)
    {
        $employeeIds = $this->extractEmployeeIds($data);
        if (empty($employeeIds)) {
            return ['success' => false, 'error' => 'Select at least one employee.'];
        }

        $baseData = $this->normalizeInput($data);
        $createdIds = [];

        $this->db->beginTransaction();

        try {
            foreach ($employeeIds as $employeeId) {
                $rowData = $baseData;
                $rowData['employee_id'] = (int) $employeeId;

                $validation = $this->validate($rowData, null, true);
                if (!$validation['success']) {
                    throw new Exception($validation['error']);
                }

                $stmt = $this->db->prepare(
                    "INSERT INTO transportation_requests
                     (employee_id, transportation_type, driver_id, vehicle_id, pickup_date, pickup_time, pickup_location, status, remarks, trip_leg_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $success = $stmt->execute([
                    $rowData['employee_id'],
                    $rowData['transportation_type'],
                    $rowData['driver_id'],
                    $rowData['vehicle_id'],
                    $rowData['pickup_date'],
                    $rowData['pickup_time'],
                    $rowData['pickup_location'],
                    $rowData['status'],
                    $rowData['remarks'],
                    $rowData['trip_leg_id']
                ]);

                if (!$success) {
                    throw new Exception('Unable to save transportation request.');
                }

                $createdIds[] = (int) $this->db->lastInsertId();
            }

            $this->db->commit();
            return ['success' => true, 'count' => count($createdIds), 'ids' => $createdIds];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
             remarks = ?,
             trip_leg_id = ?
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
            $data['trip_leg_id'],
            $id
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update transportation request.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        $tripStmt = $this->db->prepare("SELECT id FROM trips WHERE id = ?");
        $tripStmt->execute([$id]);
        $trip = $tripStmt->fetch(PDO::FETCH_ASSOC);

        if ($trip) {
            $legStmt = $this->db->prepare("SELECT id FROM trip_legs WHERE trip_id = ?");
            $legStmt->execute([$id]);
            $legIds = $legStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$legIds) {
                return ['success' => false, 'error' => 'No transportation trip legs found.'];
            }

            $placeholders = implode(',', array_fill(0, count($legIds), '?'));
            $deleteStmt = $this->db->prepare("DELETE FROM transportation_requests WHERE trip_leg_id IN ($placeholders)");
            $success = $deleteStmt->execute($legIds);
            if (!$success) {
                return ['success' => false, 'error' => 'Unable to delete trip transportation requests.'];
            }
            return ['success' => true];
        }

        $stmt = $this->db->prepare("DELETE FROM transportation_requests WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete transportation request.'];
        }

        return ['success' => true];
    }

    public function getStats()
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(CASE WHEN transportation_type = 'Company Car' AND status IN ('Pending','Scheduled','Picked Up') THEN 1 ELSE 0 END) AS active_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' AND status = 'Pending' THEN 1 ELSE 0 END) AS pending_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' AND status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' AND status = 'Picked Up' THEN 1 ELSE 0 END) AS picked_up_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' AND status = 'Completed' THEN 1 ELSE 0 END) AS completed_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' AND status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_company_car_requests,
                SUM(CASE WHEN transportation_type = 'Company Car' THEN 1 ELSE 0 END) AS total_company_car_requests
             FROM transportation_requests"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $vehicleStmt = $this->db->prepare(
            "SELECT COUNT(*) AS available_vehicles
             FROM vehicles v
             WHERE v.status = 'Available'
               AND v.id NOT IN (
                   SELECT DISTINCT vehicle_id
                   FROM transportation_requests
                   WHERE transportation_type = 'Company Car'
                     AND status IN ('Pending','Scheduled','Picked Up')
                     AND vehicle_id IS NOT NULL
               )"
        );
        $vehicleStmt->execute();
        $vehicleRow = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'active_company_car_requests' => (int) ($row['active_company_car_requests'] ?? 0),
            'pending_company_car_requests' => (int) ($row['pending_company_car_requests'] ?? 0),
            'scheduled_company_car_requests' => (int) ($row['scheduled_company_car_requests'] ?? 0),
            'picked_up_company_car_requests' => (int) ($row['picked_up_company_car_requests'] ?? 0),
            'completed_company_car_requests' => (int) ($row['completed_company_car_requests'] ?? 0),
            'cancelled_company_car_requests' => (int) ($row['cancelled_company_car_requests'] ?? 0),
            'total_company_car_requests' => (int) ($row['total_company_car_requests'] ?? 0),
            'available_vehicles' => (int) ($vehicleRow['available_vehicles'] ?? 0)
        ];
    }

    public function getEmployeeDetails($employeeId, $tripLegId = null)
    {
        $tripId = null;
        if (!empty($tripLegId)) {
            $tripStmt = $this->db->prepare(
                "SELECT t.id
                 FROM trip_legs tl
                 JOIN trips t ON tl.trip_id = t.id
                 WHERE tl.id = ? AND t.employee_id = ?"
            );
            $tripStmt->execute([$tripLegId, $employeeId]);
            $tripId = $tripStmt->fetchColumn();
        }

        $dateJoins = '';
        $dateParams = [];
        if ($tripId) {
            $dateJoins = "
             LEFT JOIN LATERAL (
                 SELECT MIN(tl.leg_date) AS arrival_date
                 FROM trip_legs tl
                 WHERE tl.trip_id = ? AND tl.leg_type = 'ARRIVAL'
             ) trip_arrival ON TRUE
             LEFT JOIN LATERAL (
                 SELECT MAX(tl.leg_date) AS departure_date
                 FROM trip_legs tl
                 WHERE tl.trip_id = ? AND tl.leg_type = 'DEPARTURE'
             ) trip_departure ON TRUE";
            $dateParams = [$tripId, $tripId];
            $arrivalDate = 'trip_arrival.arrival_date';
            $departureDate = 'trip_departure.departure_date';
        } else {
            $dateJoins = "
             LEFT JOIN LATERAL (
                 SELECT t.transaction_date AS last_arrival_date
                 FROM transactions t
                 WHERE t.employee_id = e.id AND LOWER(t.transaction_type) = 'arrival'
                 ORDER BY t.transaction_date DESC
                 LIMIT 1
             ) arrival ON TRUE
             LEFT JOIN LATERAL (
                 SELECT t.transaction_date AS last_departure_date
                 FROM transactions t
                 WHERE t.employee_id = e.id AND LOWER(t.transaction_type) = 'departure'
                 ORDER BY t.transaction_date DESC
                 LIMIT 1
             ) departure ON TRUE";
            $arrivalDate = 'arrival.last_arrival_date';
            $departureDate = 'departure.last_departure_date';
        }

        $stmt = $this->db->prepare(
            "SELECT e.id, e.employee_code, e.english_name, e.chinese_name, e.gender,
                d.department_name,
                {$arrivalDate} AS last_arrival_date,
                {$departureDate} AS last_departure_date,
                r.room_no AS room_number,
                a.accommodation_name AS accommodation_name
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN LATERAL (
                 SELECT ra.room_id
                 FROM room_assignments ra
                 WHERE ra.employee_id = e.id AND ra.status = 'Active'
                 ORDER BY ra.id DESC
                 LIMIT 1
             ) ra ON TRUE
             LEFT JOIN rooms r ON ra.room_id = r.id
             LEFT JOIN floors f ON r.floor_id = f.id
             LEFT JOIN buildings b ON f.building_id = b.id
             LEFT JOIN accommodations a ON b.accommodation_id = a.id
             {$dateJoins}
             WHERE e.id = ?"
        );
        $stmt->execute(array_merge($dateParams, [$employeeId]));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function calculateOverallStatus(?string $arrivalStatus, ?string $departureStatus): string
    {
        $statuses = array_filter([$arrivalStatus, $departureStatus], static fn($value) => $value !== null && $value !== '');
        if (in_array('Cancelled', $statuses, true)) {
            return 'Cancelled';
        }
        if (in_array('Completed', $statuses, true)) {
            return 'Completed';
        }
        if (in_array('Picked Up', $statuses, true)) {
            return 'Picked Up';
        }
        if (in_array('Scheduled', $statuses, true)) {
            return 'Scheduled';
        }
        if (in_array('Pending', $statuses, true)) {
            return 'Pending';
        }
        return 'Pending';
    }

    private function validate(array $data, ?int $excludeId = null, bool $skipConflicts = false): array
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

        // Validate trip_leg_id if provided
        if (!empty($data['trip_leg_id'])) {
            $tripLegValidation = $this->validateTripLeg($data['trip_leg_id'], $data['employee_id']);
            if (!$tripLegValidation['success']) {
                return $tripLegValidation;
            }

            // Check for duplicate transportation requests for the same trip_leg (MVP requirement)
            $existingTransportation = $this->getTransportationForTripLeg($data['trip_leg_id'], $excludeId);
            if ($existingTransportation) {
                return ['success' => false, 'error' => 'Transportation has already been assigned to this trip leg.'];
            }
        }

        if ($skipConflicts) {
            return ['success' => true];
        }

        $conflict = $this->checkAssignmentConflicts($data, $excludeId);
        if ($conflict !== null) {
            return ['success' => false, 'error' => $conflict];
        }

        return ['success' => true];
    }

    private function validateTripLeg(int $tripLegId, int $employeeId): array
    {
        // Verify trip leg exists and belongs to the specified employee
        $stmt = $this->db->prepare(
            "SELECT tl.id, t.employee_id
             FROM trip_legs tl
             JOIN trips t ON tl.trip_id = t.id
             WHERE tl.id = ?"
        );
        $stmt->execute([$tripLegId]);
        $tripLeg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tripLeg) {
            return ['success' => false, 'error' => 'Trip leg does not exist.'];
        }

        if ((int) $tripLeg['employee_id'] !== $employeeId) {
            return ['success' => false, 'error' => 'Trip leg does not belong to the specified employee.'];
        }

        return ['success' => true];
    }

    /**
     * Check if transportation already exists for a given trip_leg
     * Phase 4: MVP requirement - One transportation request per trip leg
     */
    private function getTransportationForTripLeg(int $tripLegId, ?int $excludeId = null): ?array
    {
        $sql = "SELECT id FROM transportation_requests WHERE trip_leg_id = ?";
        $params = [$tripLegId];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function normalizeInput(array $data): array
    {
        return [
            'employee_id' => isset($data['employee_id']) ? (int) $data['employee_id'] : 0,
            'transportation_type' => trim($data['transportation_type'] ?? ''),
            'driver_id' => empty($data['driver_id']) ? null : (int) $data['driver_id'],
            'vehicle_id' => empty($data['vehicle_id']) ? null : (int) $data['vehicle_id'],
            'pickup_date' => trim($data['pickup_date'] ?? ''),
            'pickup_time' => trim($data['pickup_time'] ?? ''),
            'pickup_location' => trim($data['pickup_location'] ?? ''),
            'status' => trim($data['status'] ?? ''),
            'remarks' => trim($data['remarks'] ?? ''),
            'trip_leg_id' => empty($data['trip_leg_id']) ? null : (int) $data['trip_leg_id'],
        ];
    }

    private function extractEmployeeIds(array $data): array
    {
        $rawIds = $data['employee_ids'] ?? [];
        if (!is_array($rawIds)) {
            $rawIds = array_filter(array_map('trim', explode(',', (string) $rawIds)), static fn($value) => $value !== '');
        }

        return array_values(array_unique(array_filter(array_map(static function ($value) {
            return is_numeric($value) ? (int) $value : null;
        }, $rawIds), static function ($value) {
            return $value !== null && $value > 0;
        })));
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
