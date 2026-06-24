<?php

class Transaction
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO transactions (employee_id, transaction_type, transaction_date, remarks) VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['employee_id'],
            $data['transaction_type'],
            $data['transaction_date'],
            $data['remarks'] ?? ''
        ]);
    }

    public function exists($employee_id, $type, $date, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM transactions WHERE employee_id = ? AND transaction_type = ? AND DATE(transaction_date) = ?";
        $params = [$employee_id, $type, $date];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($row) && intval($row['cnt']) > 0;
    }

    public function getByType($type)
    {
        $sql = "SELECT t.*, DATE(t.transaction_date) AS transaction_date, e.employee_code, e.full_name, d.department_name
             FROM transactions t
             LEFT JOIN employees e ON t.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE t.transaction_type = ?";

        $params = [$type];

        // optional filters: date_from, date_to, department_id, employee_id
        if (!empty($_GET['date_from'])) {
            $sql .= " AND DATE(t.transaction_date) >= ?";
            $params[] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $sql .= " AND DATE(t.transaction_date) <= ?";
            $params[] = $_GET['date_to'];
        }

        if (!empty($_GET['department_id'])) {
            $sql .= " AND d.id = ?";
            $params[] = $_GET['department_id'];
        }

        if (!empty($_GET['employee_id'])) {
            $sql .= " AND e.id = ?";
            $params[] = $_GET['employee_id'];
        }

        $sql .= " ORDER BY t.transaction_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, DATE(t.transaction_date) AS transaction_date, e.employee_code, e.full_name, d.department_name
             FROM transactions t
             LEFT JOIN employees e ON t.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE t.id = ?"
        );

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE transactions SET employee_id = ?, transaction_date = ?, remarks = ? WHERE id = ?"
        );

        return $stmt->execute([
            $data['employee_id'],
            $data['transaction_date'],
            $data['remarks'] ?? '',
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function hasTransactionOnOrBefore($employeeId, $date)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM transactions WHERE employee_id = ? AND DATE(transaction_date) <= ?"
        );
        $stmt->execute([$employeeId, $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !empty($row) && intval($row['count']) > 0;
    }
}
