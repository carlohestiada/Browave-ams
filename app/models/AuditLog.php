<?php

class AuditLog
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function log($userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null, $remarks = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, remarks, timestamp, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
        );

        return $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $remarks,
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    }

    public function getAll($limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             ORDER BY a.timestamp DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser($userId, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.user_id = ?
             ORDER BY a.timestamp DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByEntity($entityType, $entityId)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.entity_type = ? AND a.entity_id = ?
             ORDER BY a.timestamp DESC"
        );

        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange($startDate, $endDate, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE DATE(a.timestamp) BETWEEN ? AND ?
             ORDER BY a.timestamp DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->bindValue(1, $startDate, PDO::PARAM_STR);
        $stmt->bindValue(2, $endDate, PDO::PARAM_STR);
        $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByAction($action, $limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.username
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.action = ?
             ORDER BY a.timestamp DESC
             LIMIT ? OFFSET ?"
        );

        $stmt->bindValue(1, $action, PDO::PARAM_STR);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalCount()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM audit_logs");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM audit_logs WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteOlderThan($days)
    {
        $stmt = $this->db->prepare("DELETE FROM audit_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
        return $stmt->execute([$days]);
    }
}
