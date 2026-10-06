<?php

require_once __DIR__ . '/../models/WorkCalendar.php';

class MealCalculationService
{
    private const DEPARTURE_LUNCH_START_TIME = '15:00:00';
    private const DEPARTURE_LUNCH_END_TIME = '23:59:00';
    private const ARRIVAL_LUNCH_START_TIME = '00:00:00';
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
     * Employees without a non-cancelled trip leg are not included.
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
            "SELECT e.id, e.employee_code, e.english_name, e.chinese_name, e.gender, e.department_id, d.department_name, e.created_at
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE EXISTS (
                SELECT 1
                FROM trip_legs employee_leg
                JOIN trips employee_trip ON employee_trip.id = employee_leg.trip_id
                WHERE employee_trip.employee_id = e.id
                    AND employee_trip.status <> 'CANCELLED'
                    AND DATE(employee_leg.leg_date) <= ?
             )
             ORDER BY e.id ASC"
        );
        $stmt->execute([$normalizedDate]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eligible = [];
        foreach ($employees as $employee) {
            $latestTripLegs = $this->getLatestTripLegsForEmployee($employee['id'], $normalizedDate);

            if ($latestTripLegs) {
                if ($latestTripLegs[0]['leg_date'] === $normalizedDate) {
                    $getsLunch = false;
                    foreach ($latestTripLegs as $tripLeg) {
                        $legType = strtoupper((string) $tripLeg['leg_type']);
                        $pickupTime = $tripLeg['pickup_time'];
                        if ($pickupTime !== null) {
                            $pickupTime = substr((string) $pickupTime, 0, 8);
                        }

                        if ($legType === 'ARRIVAL') {
                            $legGetsLunch = $pickupTime === null
                                || (
                                    $pickupTime >= self::ARRIVAL_LUNCH_START_TIME
                                    && $pickupTime <= self::ARRIVAL_LUNCH_CUTOFF_TIME
                                );
                        } else {
                            $legGetsLunch = $pickupTime !== null
                                && $pickupTime >= self::DEPARTURE_LUNCH_START_TIME
                                && $pickupTime <= self::DEPARTURE_LUNCH_END_TIME;
                        }

                        if ($legGetsLunch) {
                            $getsLunch = true;
                            break;
                        }
                    }
                } else {
                    $latestStatusLeg = null;
                    $latestLegByTrip = [];
                    foreach ($latestTripLegs as $tripLeg) {
                        $tripType = strtoupper((string) $tripLeg['trip_type']);
                        $legType = strtoupper((string) $tripLeg['leg_type']);
                        $isFinalLeg = ($tripType === 'NORMAL_TRIP' && $legType === 'DEPARTURE')
                            || ($tripType === 'ROUND_TRIP' && $legType === 'ARRIVAL');
                        $tripRank = $isFinalLeg ? 1 : 0;
                        $currentTripLeg = $latestLegByTrip[$tripLeg['trip_id']] ?? null;

                        if (
                            $currentTripLeg === null
                            || $tripRank > $currentTripLeg['trip_rank']
                            || ($tripRank === $currentTripLeg['trip_rank'] && (int) $tripLeg['id'] > (int) $currentTripLeg['id'])
                        ) {
                            $tripLeg['trip_rank'] = $tripRank;
                            $latestLegByTrip[$tripLeg['trip_id']] = $tripLeg;
                        }
                    }

                    foreach ($latestLegByTrip as $tripLeg) {
                        if (
                            $latestStatusLeg === null
                            || (
                                strtoupper((string) $tripLeg['leg_type']) === 'ARRIVAL'
                                && strtoupper((string) $latestStatusLeg['leg_type']) !== 'ARRIVAL'
                            )
                            || (
                                strtoupper((string) $tripLeg['leg_type']) === strtoupper((string) $latestStatusLeg['leg_type'])
                                && (int) $tripLeg['id'] > (int) $latestStatusLeg['id']
                            )
                        ) {
                            $latestStatusLeg = $tripLeg;
                        }
                    }

                    $getsLunch = strtoupper((string) $latestStatusLeg['leg_type']) === 'ARRIVAL';
                }

                if ($getsLunch) {
                    $eligible[] = $employee;
                }
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
             WHERE t.status <> 'CANCELLED'
                AND DATE(tl.leg_date) BETWEEN ? AND ?
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

    private function getLatestTripLegsForEmployee($employeeId, $date)
    {
        $stmt = $this->db->prepare(
            "SELECT tl.id, tl.trip_id, t.trip_type, tl.leg_type, DATE(tl.leg_date) AS leg_date, MIN(tr.pickup_time) AS pickup_time
             FROM trip_legs tl
             JOIN trips t ON t.id = tl.trip_id
             LEFT JOIN transportation_requests tr
                ON tr.trip_leg_id = tl.id
                AND tr.status <> ?
             WHERE t.employee_id = ?
                AND t.status <> ?
                AND DATE(tl.leg_date) = (
                    SELECT MAX(DATE(latest_leg.leg_date))
                    FROM trip_legs latest_leg
                    JOIN trips latest_trip ON latest_trip.id = latest_leg.trip_id
                    WHERE latest_trip.employee_id = ?
                        AND latest_trip.status <> ?
                        AND DATE(latest_leg.leg_date) <= ?
                )
             GROUP BY tl.id, tl.trip_id, t.trip_type, tl.leg_type, DATE(tl.leg_date)
             ORDER BY tl.id DESC"
        );

        $stmt->execute(['Cancelled', $employeeId, 'CANCELLED', $employeeId, 'CANCELLED', $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
