<?php

class Department
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT * FROM departments ORDER BY department_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM departments WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function existsByName($name, $location, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM departments WHERE LOWER(department_name) = LOWER(?) AND LOWER(location) = LOWER(?)";

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
        }

        $stmt = $this->db->prepare($sql);
        $params = [$name, $location];

        if ($excludeId !== null) {
            $params[] = $excludeId;
        }

        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }

    public function create($data)
    {
        if ($this->existsByName($data['department_name'], $data['location'])) {
            return ['error' => 'duplicate'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO departments (department_name, location) VALUES (?, ?)"
        );

        try {
            if ($stmt->execute([$data['department_name'], $data['location']])) {
                return (int) $this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                return ['error' => 'duplicate'];
            }
        }

        return false;
    }

    public function update($id, $data)
    {
        if ($this->existsByName($data['department_name'], $data['location'], $id)) {
            return ['error' => 'duplicate'];
        }

        $stmt = $this->db->prepare(
            "UPDATE departments SET department_name=?, location=? WHERE id=?"
        );

        try {
            return $stmt->execute([$data['department_name'], $data['location'], $id]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                return ['error' => 'duplicate'];
            }
        }

        return false;
    }

    public function hasEmployees($departmentId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM employees WHERE department_id = ?"
        );
        $stmt->execute([$departmentId]);
        return $stmt->fetchColumn() > 0;
    }

    public function delete($id)
    {
        if ($this->hasEmployees($id)) {
            return ['success' => false, 'error' => 'Delete employees first before deleting this department.'];
        }

        $stmt = $this->db->prepare(
            "DELETE FROM departments WHERE id=?"
        );

        if (!$stmt->execute([$id])) {
            return ['success' => false, 'error' => 'Unable to delete department.'];
        }

        return ['success' => true];
    }
}
