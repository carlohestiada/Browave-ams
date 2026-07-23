<?php

require_once __DIR__ . '/../models/Driver.php';

class DriverController
{
    private $driver;

    public function __construct($db)
    {
        $this->driver = new Driver($db);
    }

    public function index()
    {
        $status = $_GET['status'] ?? null;
        echo json_encode($this->driver->getAll($status));
    }

    public function edit($id)
    {
        echo json_encode($this->driver->getById($id));
    }

    public function store()
    {
        $data = $_POST;
        $result = $this->driver->create($data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to save driver.']);
            return;
        }

        echo json_encode(['success' => true, 'id' => $result['id']]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);
        $result = $this->driver->update($id, $data);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to update driver.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $result = $this->driver->delete($id);

        if (!isset($result['success']) || !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Unable to delete driver.']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
