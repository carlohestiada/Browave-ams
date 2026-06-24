<?php

class Floor
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT f.*, b.building_name FROM floors f LEFT JOIN buildings b ON f.building_id = b.id ORDER BY f.floor_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByBuilding($buildingId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM floors WHERE building_id=? ORDER BY floor_name ASC"
        );

        $stmt->execute([$buildingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM floors WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO floors (building_id, floor_name) VALUES (?, ?)"
        );

        return $stmt->execute([
            $data['building_id'],
            $data['floor_name']
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE floors SET building_id=?, floor_name=? WHERE id=?"
        );

        return $stmt->execute([
            $data['building_id'],
            $data['floor_name'],
            $id
        ]);
    }

    public function hasRooms($floorId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM rooms WHERE floor_id = ?"
        );
        $stmt->execute([$floorId]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete($id)
    {
        if ($this->hasRooms($id)) {
            return ['success' => false, 'error' => 'Delete rooms first before deleting this floor.'];
        }

        $stmt = $this->db->prepare(
            "DELETE FROM floors WHERE id=?"
        );

        if (!$stmt->execute([$id])) {
            return ['success' => false, 'error' => 'Unable to delete floor.'];
        }

        return ['success' => true];
    }
}
