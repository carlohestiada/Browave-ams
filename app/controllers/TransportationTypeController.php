<?php

require_once __DIR__ . '/../models/TransportationType.php';

class TransportationTypeController
{
    private $transportationType;

    public function __construct($db)
    {
        $this->transportationType = new TransportationType($db);
    }

    public function index(): void
    {
        echo json_encode([
            'success' => true,
            'data' => $this->transportationType->getAll(),
        ]);
    }

    public function store(): void
    {
        $name = $_POST['transportation_name'] ?? '';
        $result = $this->transportationType->create((string) $name);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['error']]);
            return;
        }

        if ($result['created']) {
            http_response_code(201);
        }

        echo json_encode([
            'success' => true,
            'transportation_name' => $result['transportation_name'],
            'created' => $result['created'],
        ]);
    }
}