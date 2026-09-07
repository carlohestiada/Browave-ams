<?php

class WorkCalendar
{
    private $db;

    private const STATUSES = ['working_day', 'non_working_day'];
    private const REASONS = ['Regular Work', 'Weekend', 'Company Holiday', 'Special Working Day', 'Other'];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getMonth(int $year, int $month, string $status = '', string $reason = ''): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
        $stmt = $this->db->prepare(
            'SELECT id, work_date, status, reason, notes, created_at, updated_at
             FROM browave_ams.work_calendar
             WHERE work_date BETWEEN ? AND ?
             ORDER BY work_date ASC'
        );
        $stmt->execute([$start, $end]);
        $overrides = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $overrides[$row['work_date']] = $row;
        }

        $rows = [];
        $date = new DateTime($start);
        $last = new DateTime($end);
        while ($date <= $last) {
            $dateString = $date->format('Y-m-d');
            $default = $this->defaultForDate($date);
            $row = $overrides[$dateString] ?? [
                'id' => null,
                'work_date' => $dateString,
                'status' => $default['status'],
                'reason' => $default['reason'],
                'notes' => '',
                'created_at' => null,
                'updated_at' => null,
            ];
            $row['day'] = $date->format('l');
            $row['is_override'] = isset($overrides[$dateString]);

            if (($status === '' || $row['status'] === $status) && ($reason === '' || $row['reason'] === $reason)) {
                $rows[] = $row;
            }
            $date->modify('+1 day');
        }

        return $rows;
    }

    public function getByDate(string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, work_date, status, reason, notes, created_at, updated_at
             FROM browave_ams.work_calendar WHERE work_date = ?'
        );
        $stmt->execute([$date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['day'] = (new DateTime($date))->format('l');
            $row['is_override'] = true;
            return $row;
        }

        $default = $this->defaultForDate(new DateTime($date));
        return array_merge([
            'id' => null,
            'work_date' => $date,
            'notes' => '',
            'created_at' => null,
            'updated_at' => null,
            'day' => (new DateTime($date))->format('l'),
            'is_override' => false,
        ], $default);
    }

    public function getStatusForDate(string $date): string
    {
        return $this->getByDate($date)['status'];
    }

    public function save(string $date, string $status, string $reason, string $notes = ''): array
    {
        if (!in_array($status, self::STATUSES, true)) {
            return ['success' => false, 'error' => 'Invalid work day status.'];
        }
        if (!in_array($reason, self::REASONS, true)) {
            return ['success' => false, 'error' => 'Invalid work day reason.'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO browave_ams.work_calendar (work_date, status, reason, notes)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (work_date) DO UPDATE SET
                status = EXCLUDED.status,
                reason = EXCLUDED.reason,
                notes = EXCLUDED.notes,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$date, $status, $reason, trim($notes)]);
        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $stmt = $this->db->prepare('DELETE FROM browave_ams.work_calendar WHERE id = ?');
        $stmt->execute([$id]);
        return ['success' => $stmt->rowCount() > 0];
    }

    public function getExportRows(int $year, int $month, string $status = '', string $reason = ''): array
    {
        return $this->getMonth($year, $month, $status, $reason);
    }

    private function defaultForDate(DateTime $date): array
    {
        $isWeekday = (int) $date->format('N') <= 5;
        return [
            'status' => $isWeekday ? 'working_day' : 'non_working_day',
            'reason' => $isWeekday ? 'Regular Work' : 'Weekend',
        ];
    }
}
