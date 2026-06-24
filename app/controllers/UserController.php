<?php

require_once __DIR__ . '/../models/User.php';

class UserController
{
    private $user;

    public function __construct($db)
    {
        $this->user = new User($db);
    }

    public function index()
    {
        $filters = [
            'username' => $_GET['username'] ?? null,
            'role' => $_GET['role'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        echo json_encode(
            $this->user->getAll($filters)
        );
    }

    public function edit($id)
    {
        echo json_encode(
            $this->user->getById($id)
        );
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username and password are required']);
            return;
        }

        if ($this->user->findByUsername($data['username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            return;
        }

        $result = $this->user->create($data);

        echo json_encode(['success' => $result]);
    }

    public function update($id)
    {
        $data = [];
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
        }

        if (empty($data['username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username is required']);
            return;
        }

        $existing = $this->user->findByUsername($data['username']);
        if ($existing && $existing['id'] != $id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            return;
        }

        $result = $this->user->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->user->delete($id);

        echo json_encode(['success' => $result]);
    }
}
