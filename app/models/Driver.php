<?php

class Driver
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll($status = null)
    {
        $sql = "SELECT id, driver_name, phone, status FROM drivers";
        $params = [];

        if (!empty($status)) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY driver_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT id, driver_name, phone, status FROM drivers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->normalizeInput($data);

        $stmt = $this->db->prepare(
            "INSERT INTO drivers (driver_name, phone, status) VALUES (?, ?, ?)"
        );

        $success = $stmt->execute([
            $data['driver_name'],
            $data['phone'],
            $data['status'],
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to save driver.'];
        }

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    public function update($id, array $data)
    {
        $validation = $this->validate($data);
        if (!$validation['success']) {
            return $validation;
        }

        $data = $this->normalizeInput($data);

        $stmt = $this->db->prepare(
            "UPDATE drivers SET driver_name = ?, phone = ?, status = ? WHERE id = ?"
        );

        $success = $stmt->execute([
            $data['driver_name'],
            $data['phone'],
            $data['status'],
            $id,
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update driver.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM drivers WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete driver.'];
        }

        return ['success' => true];
    }

    private function validate(array $data): array
    {
        if (empty(trim((string) ($data['driver_name'] ?? '')))) {
            return ['success' => false, 'error' => 'Driver name is required'];
        }

        if (!in_array($this->normalizeStatus($data['status'] ?? ''), ['Active', 'Inactive'], true)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        return ['success' => true];
    }

    private function normalizeInput(array $data): array
    {
        return [
            'driver_name' => trim((string) ($data['driver_name'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'status' => $this->normalizeStatus($data['status'] ?? 'Active'),
        ];
    }

    private function normalizeStatus($status): string
    {
        $status = trim((string) $status);
        return $status === 'Inactive' ? 'Inactive' : 'Active';
    }
}
