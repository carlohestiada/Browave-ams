<?php

class User
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT id, username, role, status, created_at FROM users";
        $conditions = [];
        $params = [];

        if (!empty($filters['username'])) {
            $conditions[] = 'username LIKE ?';
            $params[] = '%' . $filters['username'] . '%';
        }

        if (!empty($filters['role']) && $filters['role'] !== 'All') {
            $conditions[] = 'role = ?';
            $params[] = $filters['role'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, username, role, status, created_at FROM users WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)"
        );

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        return $stmt->execute([
            $data['username'],
            $passwordHash,
            $this->sanitizeRole($data['role'] ?? 'Viewer'),
            $this->sanitizeStatus($data['status'] ?? 'Active')
        ]);
    }

    public function update($id, $data)
    {
        if (!empty($data['password'])) {
            $stmt = $this->conn->prepare(
                "UPDATE users SET username=?, password_hash=?, role=?, status=? WHERE id=?"
            );

            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

            return $stmt->execute([
                $data['username'],
                $passwordHash,
                $this->sanitizeRole($data['role'] ?? 'Viewer'),
                $this->sanitizeStatus($data['status'] ?? 'Active'),
                $id
            ]);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users SET username=?, role=?, status=? WHERE id=?"
            );

            return $stmt->execute([
                $data['username'],
                $this->sanitizeRole($data['role'] ?? 'Viewer'),
                $this->sanitizeStatus($data['status'] ?? 'Active'),
                $id
            ]);
        }
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM users WHERE id=?"
        );

        return $stmt->execute([$id]);
    }

    private function sanitizeRole($role)
    {
        $validRoles = ['Admin', 'HR', 'Viewer'];
        return in_array($role, $validRoles, true) ? $role : 'Viewer';
    }

    private function sanitizeStatus($status)
    {
        $validStatuses = ['Active', 'Inactive'];
        return in_array($status, $validStatuses, true) ? $status : 'Active';
    }

    public function verifyPassword($username, $password)
    {
        $user = $this->findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
    }
}