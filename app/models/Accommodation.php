<?php

class Accommodation
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT * FROM accommodations ORDER BY accommodation_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM accommodations WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO accommodations (accommodation_name, accommodation_type, address, contact_person, contact_number, status) VALUES (?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['accommodation_name'],
            $data['accommodation_type'],
            $data['address'] ?? '',
            $data['contact_person'] ?? '',
            $data['contact_number'] ?? '',
            $data['status'] ?? 'Active'
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE accommodations SET accommodation_name=?, accommodation_type=?, address=?, contact_person=?, contact_number=?, status=? WHERE id=?"
        );

        return $stmt->execute([
            $data['accommodation_name'],
            $data['accommodation_type'],
            $data['address'] ?? '',
            $data['contact_person'] ?? '',
            $data['contact_number'] ?? '',
            $data['status'] ?? 'Active',
            $id
        ]);
    }

    public function hasBuildings($id)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM buildings WHERE accommodation_id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetchColumn() > 0;
    }

    public function delete($id)
    {
        if ($this->hasBuildings($id)) {
            return ['success' => false, 'error' => 'Delete buildings first before deleting this accommodation.'];
        }

        $stmt = $this->db->prepare(
            "DELETE FROM accommodations WHERE id=?"
        );

        try {
            $success = $stmt->execute([$id]);
        } catch (PDOException $e) {
            $dbMessage = $e->getMessage();
            $message = 'Unable to delete accommodation.';

            if (stripos($dbMessage, 'foreign key') !== false || stripos($dbMessage, 'constraint') !== false || stripos($dbMessage, 'SQLSTATE[23000]') !== false) {
                $message = 'Cannot delete accommodation because it is referenced by buildings, floors, rooms, or assignments.';
            }

            return ['success' => false, 'error' => $message];
        }

        if (!$success) {
            return ['success' => false, 'error' => 'Unable to delete accommodation.'];
        }

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Accommodation not found or already deleted.'];
        }

        return true;
    }
}
