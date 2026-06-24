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

        if (empty($data['department_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name is required']);
            return;
        }

        if ($this->department->existsByName($data['department_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name already exists']);
            return;
        }

        $insertId = $this->department->create($data);

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

        if (empty($data['department_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name is required']);
            return;
        }

        if ($this->department->existsByName($data['department_name'], $id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Department name already exists']);
            return;
        }

        $result = $this->department->update($id, $data);

        echo json_encode([
            'success' => $result
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
