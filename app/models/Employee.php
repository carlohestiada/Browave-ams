<?php

class Employee
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll(
        $excludeArrivedDate = null,
        $markArrivedDate = null,
        $status = null,
        $departmentId = null,
        $search = null,
        $excludeTransactionType = null,
        $excludeTransactionDate = null
    )
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
            SELECT e.id, e.employee_code, e.english_name, e.chinese_name, e.gender, e.department_id, e.status, e.created_at, d.department_name $selectExtra
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
                e.english_name LIKE ? OR
                e.chinese_name LIKE ? OR
                d.department_name LIKE ?
            )";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
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

        if (!empty($excludeTransactionType) && !empty($excludeTransactionDate)) {
            $conditions[] = "e.id NOT IN (
                SELECT employee_id FROM transactions
                WHERE transaction_type = ? AND DATE(transaction_date) = ?
            )";
            $params[] = $excludeTransactionType;
            $params[] = $excludeTransactionDate;
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
            "SELECT id, employee_code, english_name, chinese_name, gender, department_id, status, created_at FROM employees WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function normalizeEnglishName($value)
    {
        $value = isset($value) ? trim((string) $value) : null;
        return $value === '' ? null : $value;
    }

    private function validateEnglishName(?string $value): bool
    {
        if ($value === null) {
            return true;
        }

        return preg_match("/^[\p{Latin}\s\-\.'’]+$/u", $value) === 1;
    }

    private function normalizeGender($value)
    {
        $value = isset($value) ? trim((string) $value) : '';

        if ($value === '') {
            return 'Other';
        }

        $value = ucfirst(strtolower($value));
        if (!in_array($value, ['Male', 'Female', 'Other'], true)) {
            throw new Exception('Invalid gender: ' . $value . '. Must be Male, Female, or Others.');
        }

        return $value;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO employees
            (
                employee_code,
                english_name,
                chinese_name,
                gender,
                department_id,
                status
            )
            VALUES
            (?,?,?,?,?,?)
        ");

        $englishName = $this->normalizeEnglishName($data['english_name'] ?? null);
        $chineseName = $this->normalizeEnglishName($data['chinese_name'] ?? null);
        $gender = $this->normalizeGender($data['gender'] ?? null);

        if (!$this->validateEnglishName($englishName)) {
            throw new Exception('English Name may only contain letters, spaces, hyphens, apostrophes, and periods.');
        }

        try {
            $success = $stmt->execute([
                $data['employee_code'],
                $englishName,
                $chineseName,
                $gender,
                $data['department_id'],
                $data['status']
            ]);
        } catch (PDOException $e) {
            $dbMessage = $e->getMessage();
            if (stripos($dbMessage, 'duplicate') !== false || stripos($dbMessage, 'unique') !== false || stripos($dbMessage, '1062') !== false || stripos($dbMessage, '23000') !== false) {
                throw new Exception('This employee already exists. Please use a different employee code or name and try again.');
            }

            throw $e;
        }

        if (!$success) {
            return false;
        }

        return (int) $this->db->lastInsertId();
    }

    public function update($id,$data)
    {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET
                employee_code=?,
                english_name=?,
                chinese_name=?,
                gender=?,
                department_id=?,
                status=?
            WHERE id=?
        ");

        $chineseName = $this->normalizeEnglishName($data['chinese_name'] ?? null);
        $englishName = $this->normalizeEnglishName($data['english_name'] ?? null);
        $gender = $this->normalizeGender($data['gender'] ?? null);

        if (!$this->validateEnglishName($englishName)) {
            throw new Exception('English Name may only contain letters, spaces, hyphens, apostrophes, and periods.');
        }

        return $stmt->execute([
            $data['employee_code'],
            $englishName,
            $chineseName,
            $gender,
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
            SET status = (
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
