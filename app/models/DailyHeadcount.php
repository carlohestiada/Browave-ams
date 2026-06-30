<?php

class DailyHeadcount
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT * FROM daily_headcount ORDER BY date DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBetween($startDate, $endDate)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM daily_headcount WHERE date BETWEEN ? AND ? ORDER BY date ASC"
        );

        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDate($date)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM daily_headcount WHERE date=?"
        );

        $stmt->execute([$date]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM daily_headcount WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO daily_headcount (date, active_count, meal_count) VALUES (?, ?, ?)"
        );

        return $stmt->execute([
            $data['date'],
            $data['active_count'] ?? 0,
            $data['meal_count'] ?? 0
        ]);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE daily_headcount SET date=?, active_count=?, meal_count=? WHERE id=?"
        );

        return $stmt->execute([
            $data['date'],
            $data['active_count'] ?? 0,
            $data['meal_count'] ?? 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM daily_headcount WHERE id=?"
        );

        return $stmt->execute([$id]);
    }

    public function calculateActiveCount($date)
    {
        $targetDate = date('Y-m-d', strtotime($date));
        $stmt = $this->db->prepare(
            "SELECT id, status, created_at FROM employees WHERE DATE(created_at) <= ?"
        );

        $stmt->execute([$targetDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activeCount = 0;

        foreach ($employees as $employee) {
            $latestStmt = $this->db->prepare(
                "SELECT transaction_type, DATE(transaction_date) AS transaction_date
                 FROM transactions
                 WHERE employee_id = ?
                   AND DATE(transaction_date) <= ?
                 ORDER BY DATE(transaction_date) DESC, id DESC
                 LIMIT 1"
            );
            $latestStmt->execute([$employee['id'], $targetDate]);
            $latestTransaction = $latestStmt->fetch(PDO::FETCH_ASSOC);

            if ($latestTransaction) {
                if ($latestTransaction['transaction_type'] === 'arrival') {
                    $activeCount++;
                }
                continue;
            }

            if ($employee['status'] === 'Active') {
                $activeCount++;
            }
        }

        return $activeCount;
    }

    public function updateHeadcount($date, $activeCount, $mealCount)
    {
        $existing = $this->getByDate($date);

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE daily_headcount SET active_count=?, meal_count=? WHERE date=?"
            );
            return $stmt->execute([$activeCount, $mealCount, $date]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO daily_headcount (date, active_count, meal_count) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$date, $activeCount, $mealCount]);
        }
    }

    public function getTransactionsByDateRange($startDate, $endDate)
    {
        $stmt = $this->db->prepare(
            "SELECT
                t.id,
                DATE(t.transaction_date) AS transaction_date,
                t.transaction_type,
                e.employee_code,
                e.full_name
             FROM transactions t
             LEFT JOIN employees e ON t.employee_id = e.id
             WHERE DATE(t.transaction_date) BETWEEN ? AND ?
             ORDER BY DATE(t.transaction_date) ASC, e.full_name ASC"
        );

        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
