<?php

require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Floor.php';

class RoomController
{
    private $room;
    private $floor;

    public function __construct($db)
    {
        $this->room = new Room($db);
        $this->floor = new Floor($db);
    }

    public function index()
    {
        echo json_encode($this->room->getAll());
    }

    public function getByFloor($floorId)
    {
        echo json_encode($this->room->getByFloor($floorId));
    }

    public function edit($id)
    {
        echo json_encode($this->room->getById($id));
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['floor_id']) || empty($data['room_no']) || empty($data['room_type']) || empty($data['capacity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->room->create($data);

        echo json_encode(['success' => $result]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['floor_id']) || empty($data['room_no']) || empty($data['room_type']) || empty($data['capacity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->room->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->room->delete($id);

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
