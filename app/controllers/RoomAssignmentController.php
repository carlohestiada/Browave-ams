<?php

require_once __DIR__ . '/../models/RoomAssignment.php';

class RoomAssignmentController
{
    private $assignment;

    public function __construct($db)
    {
        $this->assignment = new RoomAssignment($db);
    }

    public function index()
    {
        echo json_encode($this->assignment->getAll());
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['employee_id']) || empty($data['room_id']) || empty($data['checkin_date']) || empty($data['expected_checkout_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        if ($data['expected_checkout_date'] < $data['checkin_date']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Departure date cannot be before arrival date.']);
            return;
        }

        $result = $this->assignment->create($data);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function transfer($id)
    {
        parse_str(file_get_contents('php://input'), $data);

        if (empty($data['new_room_id']) || empty($data['transfer_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->assignment->transfer($id, $data['new_room_id'], $data['transfer_date']);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $result = $this->assignment->delete($id);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
