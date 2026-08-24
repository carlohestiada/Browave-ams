<?php

require_once __DIR__ . '/../models/Department.php';

class DepartmentController
{
    private $department;

    public function __construct($db)
    {
        $this->department = new Department($db);
    }

    public function index()
    {
        echo json_encode(
            $this->department->getAll()
        );
    }

    public function edit($id)
    {
        echo json_encode(
            $this->department->getById($id)
        );
    }

    public function store()
    {
        $data = $_POST;

        $data['department_name'] = trim($data['department_name'] ?? '');
        $data['location'] = trim($data['location'] ?? '');

        if ($data['department_name'] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name is required']);
            return;
        }

        if ($data['location'] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Location is required']);
            return;
        }


        if ($this->department->existsByName($data['department_name'], $data['location'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department already exists for this location.']);
            return;
        }

        $insertId = $this->department->create($data);

        if (is_array($insertId) && ($insertId['error'] ?? '') === 'duplicate') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department already exists for this location.']);
            return;
        }

        if ($insertId === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Unable to save department']);
            return;
        }

        echo json_encode([
            'success' => true,
            'id' => $insertId
        ]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        $data['department_name'] = trim($data['department_name'] ?? '');
        $data['location'] = trim($data['location'] ?? '');

        if ($data['department_name'] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name is required']);
            return;
        }

        if ($data['location'] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Location is required']);
            return;
        }

        if ($this->department->existsByName($data['department_name'], $data['location'], $id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department already exists for this location.']);
            return;
        }

        $result = $this->department->update($id, $data);

        if (is_array($result) && ($result['error'] ?? '') === 'duplicate') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department already exists for this location.']);
            return;
        }

        echo json_encode([
            'success' => (bool) $result
        ]);
    }

    public function destroy($id)
    {
        $result = $this->department->delete($id);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
            return;
        }

        echo json_encode([
            'success' => true
        ]);
    }
}
