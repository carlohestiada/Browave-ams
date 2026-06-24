<?php

class Employee
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll($excludeArrivedDate = null, $markArrivedDate = null, $status = null, $departmentId = null, $search = null)
    {
        $selectExtra = '';
        $params = [];

        if (!empty($markArrivedDate)) {
            $selectExtra = ", (
                SELECT COUNT(*) FROM transactions t2
                WHERE t2.employee_id = e.id AND t2.transaction_type = 'arrival' AND DATE(t2.transaction_date) = ?
            ) as arrived_count";
            $params[] = $markArrivedDate;
        }

        $sql = "
            SELECT e.*, d.department_name $selectExtra
            FROM employees e
            LEFT JOIN departments d
                ON e.department_id = d.id
        ";

        $conditions = [];
        if (!empty($status)) {
            $conditions[] = 'e.status = ?';
            $params[] = $status;
        }

        if (!empty($departmentId)) {
            $conditions[] = 'e.department_id = ?';
            $params[] = $departmentId;
        }

        if (!empty($search)) {
            $conditions[] = "(
                e.employee_code LIKE ? OR
                e.full_name LIKE ? OR
                d.department_name LIKE ?
            )";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($excludeArrivedDate)) {
            $conditions[] = "e.id NOT IN (
                SELECT employee_id FROM transactions
                WHERE transaction_type = 'arrival' AND DATE(transaction_date) = ?
            )";
            $params[] = $excludeArrivedDate;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM employees WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO employees
            (
                employee_code,
                full_name,
                gender,
                department_id,
                status
            )
            VALUES
            (?,?,?,?,?)
        ");

        return $stmt->execute([
            $data['employee_code'],
            $data['full_name'],
            $data['gender'],
            $data['department_id'],
            $data['status']
        ]);
    }

    public function update($id,$data)
    {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET
                employee_code=?,
                full_name=?,
                gender=?,
                department_id=?,
                status=?
            WHERE id=?
        ");

        return $stmt->execute([
            $data['employee_code'],
            $data['full_name'],
            $data['gender'],
            $data['department_id'],
            $data['status'],
            $id
        ]);
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare(
            "UPDATE employees SET status=? WHERE id=?"
        );

        return $stmt->execute([$status, $id]);
    }

    public function syncStatusesByTransactions($date = null, $employeeId = null)
    {
        $date = $date ?: date('Y-m-d');

        $sql = "
            UPDATE employees e
            SET e.status = (
                SELECT CASE
                    WHEN t.transaction_type = 'arrival' THEN 'Active'
                    ELSE 'Inactive'
                END
                FROM transactions t
                WHERE t.employee_id = e.id
                    AND DATE(t.transaction_date) <= ?
                ORDER BY DATE(t.transaction_date) DESC, t.id DESC
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1
                FROM transactions tx
                WHERE tx.employee_id = e.id
                    AND DATE(tx.transaction_date) <= ?
            )
        ";

        $params = [$date, $date];

        if (!empty($employeeId)) {
            $sql .= " AND e.id = ?";
            $params[] = $employeeId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM employees WHERE id=?"
        );

        try {
            $success = $stmt->execute([$id]);
        } catch (PDOException $e) {
            $dbMessage = $e->getMessage();
            $message = 'Cannot delete employee because: ' . $dbMessage;

            if (stripos($dbMessage, 'foreign key') !== false || stripos($dbMessage, 'constraint') !== false || stripos($dbMessage, 'SQLSTATE[23000]') !== false) {
                $message = 'Cannot delete employee because it is referenced by other records, such as arrivals or room assignments.';
            }

            return ['success' => false, 'error' => $message];
        }

        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            $dbMessage = $errorInfo[2] ?? 'Unknown database error';
            $message = 'Cannot delete employee because: ' . $dbMessage;

            if (stripos($dbMessage, 'foreign key') !== false || stripos($dbMessage, 'constraint') !== false) {
                $message = 'Cannot delete employee because it is referenced by other records, such as arrivals or room assignments.';
            }

            return ['success' => false, 'error' => $message];
        }

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Employee not found or already deleted.'];
        }

        return ['success' => true];
    }
}
