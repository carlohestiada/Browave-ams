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
        $building = $this->getById($id);
        if (!$building) {
            return ['success' => false, 'message' => 'Building not found or already deleted.'];
        }

        $roomsCount = $this->db->prepare(
            "SELECT COUNT(*) FROM rooms r JOIN floors f ON r.floor_id = f.id WHERE f.building_id = ?"
        );
        $roomsCount->execute([$id]);
        $roomCount = (int) $roomsCount->fetchColumn();

        if ($roomCount > 0) {
            return [
                'success' => false,
                'message' => 'This building cannot be deleted because it has ' . $roomCount . ' room' . ($roomCount === 1 ? '' : 's') . ' assigned to it. Please remove or transfer the rooms first.'
            ];
        }

        $floorsCount = $this->db->prepare(
            "SELECT COUNT(*) FROM floors WHERE building_id = ?"
        );
        $floorsCount->execute([$id]);
        $floorCount = (int) $floorsCount->fetchColumn();

        if ($floorCount > 0) {
            return [
                'success' => false,
                'message' => 'This building cannot be deleted because it is still linked to existing floors. Please remove or transfer those records first.'
            ];
        }

        $stmt = $this->db->prepare(
            "DELETE FROM buildings WHERE id=?"
        );

        try {
            $success = $stmt->execute([$id]);

            if (!$success) {
                return ['success' => false, 'message' => 'Unable to delete building right now. Please try again later.'];
            }

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Building not found or already deleted.'];
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Building delete failed for building ID ' . $id . ': ' . $e->getMessage());

            $dbMessage = $e->getMessage();
            if (
                stripos($dbMessage, 'foreign key') !== false ||
                stripos($dbMessage, 'constraint') !== false ||
                stripos($dbMessage, 'SQLSTATE[23503]') !== false ||
                stripos($dbMessage, 'SQLSTATE[23000]') !== false
            ) {
                return [
                    'success' => false,
                    'message' => 'This building is currently linked to existing rooms or assignments. Please remove or transfer those records before deleting the building.'
                ];
            }

            return ['success' => false, 'message' => 'Unable to delete building right now. Please try again later.'];
        }
    }
}
