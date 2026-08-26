<?php

require_once __DIR__ . '/../models/TripLeg.php';
require_once __DIR__ . '/../models/Trip.php';

class TripLegController
{
    private $tripLeg;
    private $trip;

    public function __construct($db)
    {
        $this->tripLeg = new TripLeg($db);
        $this->trip = new Trip($db);
    }

    public function index($tripId = null)
    {
        if (!$tripId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Trip ID is required.']);
            return;
        }

        // Verify trip exists
        $trip = $this->trip->getById($tripId);
        if (!$trip) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found.']);
            return;
        }

        $filters = ['trip_id' => $tripId];
        $result = $this->tripLeg->getAll($filters);
        echo json_encode($result);
    }

    public function show($id)
    {
        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        echo json_encode($leg);
    }

    public function store($tripId = null)
    {
        if (!$tripId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Trip ID is required.']);
            return;
        }

        // Verify trip exists
        $trip = $this->trip->getById($tripId);
        if (!$trip) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip not found.']);
            return;
        }

        $data = $_POST;
        $data['trip_id'] = $tripId;

        $result = $this->tripLeg->create($data);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Trip leg created successfully.',
            'id' => $result['id']
        ]);
    }

    public function update($id)
    {
        parse_str(file_get_contents('php://input'), $data);

        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        $result = $this->tripLeg->update($id, $data);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Trip leg updated successfully.']);
    }

    public function destroy($id)
    {
        $leg = $this->tripLeg->getById($id);
        if (!$leg) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Trip leg not found.']);
            return;
        }

        $result = $this->tripLeg->delete($id);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Trip leg deleted successfully.']);
    }
}
