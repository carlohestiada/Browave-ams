<?php

require_once __DIR__ . '/../models/WorkCalendar.php';

class MealCalculationService
{
    private const DEPARTURE_LUNCH_START_TIME = '15:00:00';
    private const DEPARTURE_LUNCH_END_TIME = '20:00:00';
    private const ARRIVAL_LUNCH_START_TIME = '08:00:00';
    private const ARRIVAL_LUNCH_CUTOFF_TIME = '14:00:00';

    private $db;
    private $workCalendar;

    public function __construct($db)
    {
        $this->db = $db;
        $this->workCalendar = new WorkCalendar($db);
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
            $override = $overrides[$date] ?? null;
            $workDayStatus = $this->workCalendar->getStatusForDate($date);
            $isWorkingDay = $workDayStatus === 'working_day';
            $activeCount = $isWorkingDay ? $this->calculateActiveCount($date) : 0;

            $headcount = $activeCount;
            $companyPay = $activeCount;
            $lunchBox = $activeCount;

            if (!$isWorkingDay && $override) {
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
                'meal_count' => $lunchBox,
                'headcount' => $headcount,
                'company_pay' => $companyPay,
                'lunch_box' => $lunchBox,
                'work_day_status' => $isWorkingDay ? 'Working Day' : 'Non-Working Day',
                'is_working_day' => $isWorkingDay,
                'is_sunday' => (new DateTime($date))->format('w') === '0',
                'can_edit_lunch_box' => !$isWorkingDay,
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
                OR EXISTS (
                    SELECT 1
                    FROM trip_legs employee_leg
                    JOIN trips employee_trip ON employee_trip.id = employee_leg.trip_id
                    WHERE employee_trip.employee_id = e.id
                      AND DATE(employee_leg.leg_date) <= ?
                )
             ORDER BY e.id ASC"
        );
        $stmt->execute([$normalizedDate, $normalizedDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eligible = [];
        foreach ($employees as $employee) {
            $latestTripLeg = $this->getLatestTripLegForEmployee($employee['id'], $normalizedDate);

            if ($latestTripLeg) {
                $legType = strtoupper((string) $latestTripLeg['leg_type']);
                $getsLunch = $legType === 'ARRIVAL';

                if ($latestTripLeg['leg_date'] === $normalizedDate) {
                    $pickupTime = $this->getEarliestCompanyCarPickupTime($latestTripLeg['id']);
                    if ($pickupTime !== null) {
                        $pickupTime = strlen($pickupTime) === 5 ? $pickupTime . ':00' : $pickupTime;

                        if ($legType === 'DEPARTURE') {
                            $getsLunch = $pickupTime >= self::DEPARTURE_LUNCH_START_TIME
                                && $pickupTime <= self::DEPARTURE_LUNCH_END_TIME;
                        } elseif ($legType === 'ARRIVAL') {
                            $getsLunch = $pickupTime >= self::ARRIVAL_LUNCH_START_TIME
                                && $pickupTime <= self::ARRIVAL_LUNCH_CUTOFF_TIME;
                        }
                    }
                }

                if ($getsLunch) {
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
            "SELECT tl.id, tl.leg_type, DATE(tl.leg_date) AS leg_date
             FROM trip_legs tl
             JOIN trips t ON t.id = tl.trip_id
             WHERE t.employee_id = ? AND DATE(tl.leg_date) <= ?
             ORDER BY DATE(tl.leg_date) DESC, tl.id DESC
             LIMIT 1"
        );

        $stmt->execute([$employeeId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getEarliestCompanyCarPickupTime($tripLegId)
    {
        $stmt = $this->db->prepare(
            "SELECT MIN(pickup_time) AS pickup_time
             FROM transportation_requests
             WHERE trip_leg_id = ? AND transportation_type = ? AND status <> ?"
        );
        $stmt->execute([$tripLegId, 'Company Car', 'Cancelled']);
        $pickupTime = $stmt->fetchColumn();

        return $pickupTime === false || $pickupTime === null ? null : substr((string) $pickupTime, 0, 8);
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
