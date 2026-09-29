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

        if (empty($data['accommodation_id']) || empty($data['room_type']) || empty($data['capacity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Accommodation, room type, and capacity are required.']);
            return;
        }

        if (!empty($data['generate_range']) && !empty($data['start_room_no']) && !empty($data['end_room_no'])) {
            $result = $this->room->createRange($data, $data['start_room_no'], $data['end_room_no']);
            echo json_encode($result);
            return;
        }

        if (empty($data['room_no'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Room number is required.']);
            return;
        }

        $result = $this->room->create($data);

        if ($result === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unable to create room. Please check the accommodation, building, floor, and room number.']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['accommodation_id']) || empty($data['room_no']) || empty($data['room_type']) || empty($data['capacity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Accommodation, room number, room type, and capacity are required.']);
            return;
        }

        $result = $this->room->update($id, $data);

        if ($result === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unable to update room. Please check the selected accommodation, building, floor, and room number.']);
            return;
        }

        echo json_encode(['success' => true]);
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
