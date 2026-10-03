<?php

require_once __DIR__ . '/../models/TransportationRequest.php';
require_once __DIR__ . '/../models/Employee.php';

class TransportationController
{
    private $transportation;
    private $employee;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->transportation = new TransportationRequest($db);
        $this->employee = new Employee($db);
    }

    private function syncTripEmployeeStatus($tripId)
    {
        $stmt = $this->db->prepare("SELECT employee_id FROM trips WHERE id = ?");
        $stmt->execute([$tripId]);
        $employeeId = $stmt->fetchColumn();
        if ($employeeId !== false) {
            $this->employee->syncStatusesByTransactions(date('Y-m-d'), $employeeId);
        }
    }

    public function index()
    {
        if (isset($_GET['stats'])) {
            echo json_encode($this->transportation->getStats());
            return;
        }

        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'pickup_date' => $_GET['pickup_date'] ?? null,
            'transportation_type' => $_GET['transportation_type'] ?? null,
            'vehicle_id' => $_GET['vehicle_id'] ?? null,
            'driver_id' => $_GET['driver_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        echo json_encode($this->transportation->getAll($filters));
    }

    public function edit($id)
    {
        echo json_encode($this->transportation->getById($id));
    }

    public function store()
    {
        $data = $_POST;
        $result = $this->transportation->create($data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to save transportation request.']);
            return;
        }

        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $data['employee_id']);
        echo json_encode(['success' => true, 'id' => $result['id']]);
    }

    public function storeBulk()
    {
        $data = $_POST;
        $result = $this->transportation->createBulk($data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to save bulk transportation requests.']);
            return;
        }

        $employeeIds = $data['employee_ids'] ?? [];
        if (!is_array($employeeIds)) {
            $employeeIds = array_filter(array_map('trim', explode(',', (string) $employeeIds)));
        }
        foreach (array_unique($employeeIds) as $employeeId) {
            $this->employee->syncStatusesByTransactions(date('Y-m-d'), $employeeId);
        }
        echo json_encode(['success' => true, 'count' => $result['count'] ?? 0, 'ids' => $result['ids'] ?? []]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);
        $existing = $this->transportation->getById($id);
        $result = $this->transportation->update($id, $data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to update transportation request.']);
            return;
        }

        if ($existing) {
            $this->employee->syncStatusesByTransactions(date('Y-m-d'), $existing['employee_id']);
        }
        if (!empty($data['employee_id']) && (!$existing || (string) $data['employee_id'] !== (string) $existing['employee_id'])) {
            $this->employee->syncStatusesByTransactions(date('Y-m-d'), $data['employee_id']);
        }
        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $existing = $this->transportation->getById($id);
        $result = $this->transportation->delete($id);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to delete transportation request.']);
            return;
        }

        if ($existing) {
            $this->employee->syncStatusesByTransactions(date('Y-m-d'), $existing['employee_id']);
        }
        echo json_encode(['success' => true]);
    }

    public function getEmployeeDetails($employeeId)
    {
        $employee = $this->transportation->getEmployeeDetails($employeeId, $_GET['trip_leg_id'] ?? null);

        if (!$employee) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Employee not found']);
            return;
        }

        echo json_encode($employee);
    }

    public function getTripDetails($tripId)
    {
        $data = $this->transportation->getTripDetails((int) $tripId);
        if (!$data) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $data]);
    }

    public function updateTripStatuses($tripId)
    {
        parse_str(file_get_contents('php://input'), $data);
        $result = $this->transportation->updateTripLegStatuses((int) $tripId, $data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to update trip leg statuses.']);
            return;
        }

        $this->syncTripEmployeeStatus($tripId);
        echo json_encode(['success' => true]);
    }

    /**
     * Phase 4: Get transportation for a specific trip leg
     * Used when displaying trip details to show transportation for each leg
     */
    public function getByTripLegId($tripLegId)
    {
        $transportation = $this->transportation->getByTripLegId($tripLegId);
        
        if (!$transportation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No transportation found for this trip leg']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $transportation]);
    }
}
