<?php

class MealCalculationService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Calculate active employee count for a specific date.
     * Employees are active if:
     * 1. Their latest transaction on or before the date is an "arrival", OR
     * 2. They have no transactions and status is "Active"
     */
    public function calculateActiveCount($date)
    {
        $targetDate = date('Y-m-d', strtotime($date));
        
        // Fetch all employees created on or before target date
        $stmt = $this->db->prepare(
            "SELECT id, status, created_at FROM employees WHERE DATE(created_at) <= ?"
        );
        $stmt->execute([$targetDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activeCount = 0;

        foreach ($employees as $employee) {
            // Get the latest transaction on or before the target date
            $latestStmt = $this->db->prepare(
                "SELECT transaction_type
                 FROM transactions
                 WHERE employee_id = ? AND DATE(transaction_date) <= ?
                 ORDER BY DATE(transaction_date) DESC, id DESC
                 LIMIT 1"
            );
            $latestStmt->execute([$employee['id'], $targetDate]);
            $latestTransaction = $latestStmt->fetch(PDO::FETCH_ASSOC);

            // Determine if employee is active
            if ($latestTransaction) {
                if ($latestTransaction['transaction_type'] === 'arrival') {
                    $activeCount++;
                }
            } else {
                // No transactions: check employee status
                if ($employee['status'] === 'Active') {
                    $activeCount++;
                }
            }
        }

        return $activeCount;
    }

    /**
     * Calculate meal count for a specific date.
     * Meal count is the number of active employees for that date.
     */
    public function calculateMealCount($date)
    {
        return $this->calculateActiveCount($date);
    }

    /**
     * Get calculations for a date range.
     * Returns data in format compatible with meal planning views.
     */
    public function getHeadcountsForDateRange($startDate, $endDate)
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate) || $startDate > $endDate) {
            return [];
        }

        $rows = [];
        $current = new DateTime($startDate);
        $last = new DateTime($endDate);

        while ($current <= $last) {
            $date = $current->format('Y-m-d');
            $activeCount = $this->calculateActiveCount($date);
            
            $rows[] = [
                'date' => $date,
                'active_count' => $activeCount,
                'meal_count' => $activeCount
            ];

            $current->modify('+1 day');
        }

        return $rows;
    }

    /**
     * Get transaction details for a date range (arrivals and departures).
     */
    public function getTransactionsForDateRange($startDate, $endDate)
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

    /**
     * Attach transaction data to headcount records.
     * Returns array keyed by date with arrivals/departures arrays.
     */
    public function attachTransactionsToHeadcounts($headcounts, $startDate = null, $endDate = null)
    {
        if (empty($headcounts)) {
            return [];
        }

        $dates = array_column($headcounts, 'date');
        $startDate = $startDate ?? min($dates);
        $endDate = $endDate ?? max($dates);

        $transactions = $this->getTransactionsForDateRange($startDate, $endDate);
        $grouped = [];

        foreach ($transactions as $transaction) {
            $date = $transaction['transaction_date'];
            $type = $transaction['transaction_type'] === 'departure' ? 'departures' : 'arrivals';

            if (!isset($grouped[$date])) {
                $grouped[$date] = ['arrivals' => [], 'departures' => []];
            }

            $grouped[$date][$type][] = $transaction;
        }

        $withTransactions = [];
        foreach ($headcounts as $headcount) {
            $date = $headcount['date'];
            $headcount['arrivals'] = $grouped[$date]['arrivals'] ?? [];
            $headcount['departures'] = $grouped[$date]['departures'] ?? [];
            $withTransactions[$date] = $headcount;
        }

        return $withTransactions;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     */
    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
