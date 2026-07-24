<?php

class Vehicle
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll($status = null)
    {
        $sql = "SELECT id, vehicle_name, license_plate, status FROM vehicles";
        $params = [];

        if (!empty($status)) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY vehicle_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT id, vehicle_name, license_plate, status FROM vehicles WHERE id = ?");
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

        if ($this->licensePlateExists($data['license_plate'])) {
            return ['success' => false, 'error' => 'A vehicle with this license plate already exists.'];
        }

        try {
            // First attempt: include `plate_number` column (newer schemas).
            $plateNumber = $data['license_plate'] !== '' ? $data['license_plate'] : '';
            $stmt = $this->db->prepare(
                "INSERT INTO vehicles (vehicle_name, license_plate, plate_number, status) VALUES (?, ?, ?, ?)"
            );

            $success = $stmt->execute([
                $data['vehicle_name'],
                $data['license_plate'],
                $plateNumber,
                $data['status'],
            ]);
        } catch (PDOException $e) {
            $message = $e->getMessage();

            // If the production DB doesn't have `plate_number`, retry without it.
            if (stripos($message, "Unknown column 'plate_number'") !== false || stripos($message, '1054') !== false) {
                try {
                    $stmt = $this->db->prepare(
                        "INSERT INTO vehicles (vehicle_name, license_plate, status) VALUES (?, ?, ?)"
                    );

                    $success = $stmt->execute([
                        $data['vehicle_name'],
                        $data['license_plate'],
                        $data['status'],
                    ]);
                } catch (PDOException $inner) {
                    $innerMsg = $inner->getMessage();
                    if (stripos($innerMsg, 'Duplicate entry') !== false) {
                        return ['success' => false, 'error' => 'A vehicle with this license plate already exists.'];
                    }

                    return ['success' => false, 'error' => 'Unable to save vehicle. ' . $innerMsg];
                }
            }

            if (stripos($message, 'Duplicate entry') !== false) {
                return ['success' => false, 'error' => 'A vehicle with this license plate already exists.'];
            }

            return ['success' => false, 'error' => 'Unable to save vehicle. ' . $message];
        }

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to save vehicle.'];
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
            "UPDATE vehicles SET vehicle_name = ?, license_plate = ?, status = ? WHERE id = ?"
        );

        $success = $stmt->execute([
            $data['vehicle_name'],
            $data['license_plate'],
            $data['status'],
            $id,
        ]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to update vehicle.'];
        }

        return ['success' => true];
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM vehicles WHERE id = ?");
        $success = $stmt->execute([$id]);

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete vehicle.'];
        }

        return ['success' => true];
    }

    private function validate(array $data): array
    {
        if (empty(trim((string) ($data['vehicle_name'] ?? '')))) {
            return ['success' => false, 'error' => 'Vehicle name is required'];
        }

        if (!in_array($this->normalizeStatus($data['status'] ?? ''), ['Available', 'Inactive'], true)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        return ['success' => true];
    }

    private function normalizeInput(array $data): array
    {
        return [
            'vehicle_name' => trim((string) ($data['vehicle_name'] ?? '')),
            'license_plate' => trim((string) ($data['license_plate'] ?? '')),
            'status' => $this->normalizeStatus($data['status'] ?? 'Available'),
        ];
    }

    private function licensePlateExists(string $licensePlate): bool
    {
        if ($licensePlate === '') {
            return false;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) AS count FROM vehicles WHERE license_plate = ?");
        $stmt->execute([$licensePlate]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && (int) $row['count'] > 0;
    }

    private function normalizeStatus($status): string
    {
        $status = trim((string) $status);
        return $status === 'Inactive' ? 'Inactive' : 'Available';
    }
}
