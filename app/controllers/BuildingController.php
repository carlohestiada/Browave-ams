<?php

require_once __DIR__ . '/../models/Building.php';
require_once __DIR__ . '/../models/Floor.php';

class BuildingController
{
    private $building;
    private $floor;

    public function __construct($db)
    {
        $this->building = new Building($db);
        $this->floor = new Floor($db);
    }

    public function index()
    {
        echo json_encode($this->building->getAll());
    }

    public function getFloors($buildingId)
    {
        echo json_encode($this->floor->getByBuilding($buildingId));
    }

    public function edit($id)
    {
        echo json_encode($this->building->getById($id));
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['accommodation_id']) || empty($data['building_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->building->create($data);

        echo json_encode(['success' => $result]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['accommodation_id']) || empty($data['building_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        $result = $this->building->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->building->delete($id);

        echo json_encode(['success' => $result]);
    }
}
