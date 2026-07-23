<?php

require_once __DIR__ . '/../models/TransportationRequest.php';

class TransportationController
{
    private $transportation;

    public function __construct($db)
    {
        $this->transportation = new TransportationRequest($db);
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

        echo json_encode(['success' => true, 'id' => $result['id']]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);
        $result = $this->transportation->update($id, $data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to update transportation request.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $result = $this->transportation->delete($id);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to delete transportation request.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function getEmployeeDetails($employeeId)
    {
        echo json_encode($this->transportation->getEmployeeDetails($employeeId));
    }
}
