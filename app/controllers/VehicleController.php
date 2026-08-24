<?php

require_once __DIR__ . '/../models/Vehicle.php';

class VehicleController
{
    private $vehicle;

    public function __construct($db)
    {
        $this->vehicle = new Vehicle($db);
    }

    public function index()
    {
        $status = $_GET['status'] ?? null;
        echo json_encode($this->vehicle->getAll($status));
    }

    public function edit($id)
    {
        echo json_encode($this->vehicle->getById($id));
    }

    public function store()
    {
        $data = $_POST;
        $result = $this->vehicle->create($data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to save vehicle.']);
            return;
        }

        echo json_encode(['success' => true, 'id' => $result['id']]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);
        $result = $this->vehicle->update($id, $data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to update vehicle.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $result = $this->vehicle->delete($id);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to delete vehicle.']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
