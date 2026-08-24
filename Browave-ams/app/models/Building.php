<?php

class Building
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT b.*, a.accommodation_name FROM buildings b LEFT JOIN accommodations a ON b.accommodation_id = a.id ORDER BY b.building_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAccommodation($accommodationId)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM buildings WHERE accommodation_id=? ORDER BY building_name ASC"
        );

        $stmt->execute([$accommodationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM buildings WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO buildings (accommodation_id, building_name) VALUES (?, ?)"
        );

        return $stmt->execute([
            $data['accommodation_id'],
            $data['building_name']
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE buildings SET accommodation_id=?, building_name=? WHERE id=?"
        );

        return $stmt->execute([
            $data['accommodation_id'],
            $data['building_name'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM buildings WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}
