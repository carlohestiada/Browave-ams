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

    public function show($id)
    {
        $details = $this->assignment->getAssignmentDetails($id);

        if (!$details) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Room assignment not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $details]);
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['employee_id']) || empty($data['room_id']) || empty($data['checkin_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Employee, room, and check-in date are required.']);
            return;
        }

        $checkoutDate = trim((string) ($data['expected_checkout_date'] ?? ''));
        $data['expected_checkout_date'] = $checkoutDate === '' ? null : $checkoutDate;
        if (!$this->isValidDate($data['checkin_date']) || ($data['expected_checkout_date'] !== null && !$this->isValidDate($data['expected_checkout_date']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Enter valid check-in and check-out dates.']);
            return;
        }

        if ($data['expected_checkout_date'] !== null && $data['expected_checkout_date'] < $data['checkin_date']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Check-out date cannot be earlier than Check-in date.']);
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

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);

        if (!array_key_exists('checkin_date', $data) || trim((string) $data['checkin_date']) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Check-in date is required.']);
            return;
        }

        $data['checkin_date'] = trim((string) $data['checkin_date']);
        $checkoutDate = trim((string) ($data['expected_checkout_date'] ?? ''));
        $data['expected_checkout_date'] = $checkoutDate === '' ? null : $checkoutDate;

        if (!$this->isValidDate($data['checkin_date']) || ($data['expected_checkout_date'] !== null && !$this->isValidDate($data['expected_checkout_date']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Enter valid check-in and check-out dates.']);
            return;
        }

        if ($data['expected_checkout_date'] !== null && $data['expected_checkout_date'] < $data['checkin_date']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Check-out date cannot be earlier than Check-in date.']);
            return;
        }

        $result = $this->assignment->updateAssignment($id, $data);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true]);
    }

    private function isValidDate($date)
    {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        return $parsedDate && $parsedDate->format('Y-m-d') === $date;
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
