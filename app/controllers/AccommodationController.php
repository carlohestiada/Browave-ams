<?php

require_once __DIR__ . '/../models/Accommodation.php';
require_once __DIR__ . '/../models/Building.php';

class AccommodationController
{
    private $accommodation;
    private $building;

    public function __construct($db)
    {
        $this->accommodation = new Accommodation($db);
        $this->building = new Building($db);
    }

    public function index()
    {
        echo json_encode($this->accommodation->getAll());
    }

    public function getBuildings($accommodationId)
    {
        echo json_encode($this->building->getByAccommodation($accommodationId));
    }

    public function edit($id)
    {
        echo json_encode($this->accommodation->getById($id));
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['accommodation_name']) || empty($data['accommodation_type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->accommodation->create($data);

        echo json_encode(['success' => $result]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['accommodation_name']) || empty($data['accommodation_type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->accommodation->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->accommodation->delete($id);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Unable to delete accommodation.'
            ]);
            return;
        }

        echo json_encode(['success' => true]);
    }
}
