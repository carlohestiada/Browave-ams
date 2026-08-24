<?php

require_once __DIR__ . '/../models/Floor.php';

class FloorController
{
    private $floor;

    public function __construct($db)
    {
        $this->floor = new Floor($db);
    }

    public function index()
    {
        echo json_encode($this->floor->getAll());
    }

    public function edit($id)
    {
        echo json_encode($this->floor->getById($id));
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['building_id']) || empty($data['floor_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->floor->create($data);

        echo json_encode(['success' => $result]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['building_id']) || empty($data['floor_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->floor->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->floor->delete($id);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
