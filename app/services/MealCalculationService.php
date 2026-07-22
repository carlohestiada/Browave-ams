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

        $stmt = $this->db->prepare(
            "SELECT id, status, created_at FROM employees WHERE DATE(created_at) <= ?"
        );
        $stmt->execute([$targetDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activeCount = 0;

        foreach ($employees as $employee) {
            $latestStmt = $this->db->prepare(
                "SELECT transaction_type
                 FROM transactions
                 WHERE employee_id = ? AND DATE(transaction_date) <= ?
                 ORDER BY DATE(transaction_date) DESC, id DESC
                 LIMIT 1"
            );
            $latestStmt->execute([$employee['id'], $targetDate]);
            $latestTransaction = $latestStmt->fetch(PDO::FETCH_ASSOC);

            if ($latestTransaction) {
                if ($latestTransaction['transaction_type'] === 'arrival') {
                    $activeCount++;
                }
            } else {
                if ($employee['status'] === 'Active') {
                    $activeCount++;
                }
            }
        }

        return $activeCount;
    }

    public function calculateMealCount($date)
    {
        return $this->calculateActiveCount($date);
    }

    public function getHeadcountsForDateRange($startDate, $endDate)
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate) || $startDate > $endDate) {
            return [];
        }

        $rows = [];
        $overrides = $this->getDailyHeadcountOverrides($startDate, $endDate);
        $current = new DateTime($startDate);
        $last = new DateTime($endDate);

        while ($current <= $last) {
            $date = $current->format('Y-m-d');
            $activeCount = $this->calculateActiveCount($date);
            $override = $overrides[$date] ?? null;
            $isSunday = (new DateTime($date))->format('w') === '0';

            $headcount = $activeCount;
            $companyPay = $activeCount;
            $lunchBox = $activeCount;

            if ($isSunday && $override) {
                $overrideValue = $this->resolveOverrideValue($override);
                if ($overrideValue !== null) {
                    $headcount = $overrideValue;
                    $companyPay = $overrideValue;
                    $lunchBox = $overrideValue;
                }
            }

            $rows[] = [
                'date' => $date,
                'active_count' => $activeCount,
                'meal_count' => $activeCount,
                'headcount' => $headcount,
                'company_pay' => $companyPay,
                'lunch_box' => $lunchBox,
                'is_sunday' => $isSunday,
                'can_edit_lunch_box' => $isSunday,
            ];

            $current->modify('+1 day');
        }

        return $rows;
    }

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
            $headcount['remarks'] = $this->buildRemarks(
                $headcount['lunch_box'] ?? $headcount['meal_count'] ?? 0,
                $headcount['arrivals'],
                $headcount['departures']
            );
            $withTransactions[$date] = $headcount;
        }

        return $withTransactions;
    }

    public function saveSundayLunchBoxOverride($date, $value)
    {
        $normalizedDate = date('Y-m-d', strtotime($date));
        $normalizedValue = max(0, (int) $value);

        $existing = $this->getDailyHeadcountOverride($normalizedDate);

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE daily_headcount SET active_count=?, meal_count=? WHERE date=?"
            );
            return $stmt->execute([$normalizedValue, $normalizedValue, $normalizedDate]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO daily_headcount (date, active_count, meal_count) VALUES (?, ?, ?)"
        );

        return $stmt->execute([$normalizedDate, $normalizedValue, $normalizedValue]);
    }

    private function getDailyHeadcountOverrides($startDate, $endDate)
    {
        $stmt = $this->db->prepare(
            "SELECT date, active_count, meal_count FROM daily_headcount WHERE date BETWEEN ? AND ?"
        );
        $stmt->execute([$startDate, $endDate]);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[$row['date']] = $row;
        }

        return $rows;
    }

    private function getDailyHeadcountOverride($date)
    {
        $stmt = $this->db->prepare(
            "SELECT date, active_count, meal_count FROM daily_headcount WHERE date=?"
        );
        $stmt->execute([$date]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function resolveOverrideValue($override)
    {
        if (!$override) {
            return null;
        }

        if (array_key_exists('meal_count', $override) && $override['meal_count'] !== null) {
            return max(0, (int) $override['meal_count']);
        }

        if (array_key_exists('active_count', $override) && $override['active_count'] !== null) {
            return max(0, (int) $override['active_count']);
        }

        return null;
    }

    private function buildRemarks($lunchBox, $arrivals, $departures)
    {
        $parts = [];
        $parts[] = $lunchBox . ' Lunch Box';

        if (!empty($arrivals)) {
            $parts[] = '+' . count($arrivals) . ' Arrivals';
        }

        if (!empty($departures)) {
            $parts[] = '-' . count($departures) . ' Departure' . (count($departures) > 1 ? 's' : '');
        }

        return implode("\n", $parts);
    }

    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
