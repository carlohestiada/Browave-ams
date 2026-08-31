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
     * The trip/leg flow is the source of truth for arrival/departure movement.
     * Employees are active if their latest leg on or before the date is an ARRIVAL.
     * If they have no recorded trip legs, fall back to the employee's Active status.
     */
    public function calculateActiveCount($date)
    {
        $targetDate = date('Y-m-d', strtotime($date));
        return count($this->getLunchboxEligibleEmployees($targetDate));
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

    public function getLunchboxEligibleEmployees($date)
    {
        $normalizedDate = date('Y-m-d', strtotime($date));
        if (!$this->isValidDate($normalizedDate)) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT e.id, e.employee_code, e.english_name, e.chinese_name, e.gender, e.status, e.department_id, d.department_name, e.created_at
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE DATE(e.created_at) <= ?
             ORDER BY e.id ASC"
        );
        $stmt->execute([$normalizedDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eligible = [];
        foreach ($employees as $employee) {
            $latestTripLeg = $this->getLatestTripLegForEmployee($employee['id'], $normalizedDate);

            if ($latestTripLeg) {
                if (strtoupper((string) $latestTripLeg['leg_type']) === 'ARRIVAL') {
                    $eligible[] = $employee;
                }
                continue;
            }

            if (($employee['status'] ?? '') === 'Active') {
                $eligible[] = $employee;
            }
        }

        return $eligible;
    }

    public function getTransactionsForDateRange($startDate, $endDate)
    {
        $stmt = $this->db->prepare(
            "SELECT
                tl.id,
                DATE(tl.leg_date) AS transaction_date,
                LOWER(tl.leg_type) AS transaction_type,
                e.employee_code,
                e.english_name
             FROM trip_legs tl
             JOIN trips t ON t.id = tl.trip_id
             JOIN employees e ON e.id = t.employee_id
             WHERE DATE(tl.leg_date) BETWEEN ? AND ?
             ORDER BY DATE(tl.leg_date) ASC, e.english_name ASC, tl.id ASC"
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

    private function getLatestTripLegForEmployee($employeeId, $date)
    {
        $stmt = $this->db->prepare(
            "SELECT tl.leg_type, DATE(tl.leg_date) AS leg_date
             FROM trip_legs tl
             JOIN trips t ON t.id = tl.trip_id
             WHERE t.employee_id = ? AND DATE(tl.leg_date) <= ?
             ORDER BY DATE(tl.leg_date) DESC, tl.id DESC
             LIMIT 1"
        );

        $stmt->execute([$employeeId, $date]);
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
