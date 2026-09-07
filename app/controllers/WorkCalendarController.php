<?php

require_once __DIR__ . '/../models/WorkCalendar.php';

class WorkCalendarController
{
    private $calendar;

    public function __construct($db)
    {
        $this->calendar = new WorkCalendar($db);
    }

    public function index(): void
    {
        $year = $this->validYear($_GET['year'] ?? date('Y'));
        $month = $this->validMonth($_GET['month'] ?? date('n'));
        $status = trim((string) ($_GET['status'] ?? ''));
        $reason = trim((string) ($_GET['reason'] ?? ''));
        $rows = $this->calendar->getMonth($year, $month, $status, $reason);

        $summary = [
            'working_days' => 0,
            'non_working_days' => 0,
            'special_working_days' => 0,
            'company_holidays' => 0,
        ];
        foreach ($rows as $row) {
            if ($row['status'] === 'working_day') {
                $summary['working_days']++;
                if ($row['reason'] === 'Special Working Day') {
                    $summary['special_working_days']++;
                }
            } else {
                $summary['non_working_days']++;
                if ($row['reason'] === 'Company Holiday') {
                    $summary['company_holidays']++;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'year' => $year,
            'month' => $month,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    public function show(string $date): void
    {
        echo json_encode(['success' => true, 'data' => $this->calendar->getByDate($date)]);
    }

    public function store(): void
    {
        $date = trim((string) ($_POST['work_date'] ?? ''));
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A valid work date is required.']);
            return;
        }

        $result = $this->calendar->save(
            $date,
            trim((string) ($_POST['status'] ?? '')),
            trim((string) ($_POST['reason'] ?? '')),
            (string) ($_POST['notes'] ?? '')
        );
        if (!$result['success']) {
            http_response_code(400);
        }
        echo json_encode($result);
    }

    public function destroy(string $id): void
    {
        if (!ctype_digit($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid calendar entry.']);
            return;
        }
        echo json_encode($this->calendar->delete((int) $id));
    }

    public function export(): void
    {
        $year = $this->validYear($_GET['year'] ?? date('Y'));
        $month = $this->validMonth($_GET['month'] ?? date('n'));
        $status = trim((string) ($_GET['status'] ?? ''));
        $reason = trim((string) ($_GET['reason'] ?? ''));
        $format = strtolower((string) ($_GET['format'] ?? 'csv'));
        $rows = $this->calendar->getExportRows($year, $month, $status, $reason);

        if (!$rows) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No records available to export.']);
            return;
        }

        if ($format === 'pdf') {
            $this->sendPrintableReport($rows, $year, $month, $status, $reason);
            return;
        }

        $delimiter = $format === 'excel' ? "\t" : ',';
        $extension = $format === 'excel' ? 'xls' : 'csv';
        header('Content-Type: ' . ($format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv') . '; charset=UTF-8');
        header('Content-Disposition: attachment; filename="browave-work-calendar-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.' . $extension . '"');
        $output = fopen('php://output', 'w');
        $write = static function ($row) use ($output, $delimiter): void {
            if ($delimiter === "\t") {
                fwrite($output, implode("\t", array_map(static fn($value) => str_replace(["\t", "\r", "\n"], ' ', (string) $value), $row)) . "\r\n");
                return;
            }
            fputcsv($output, $row);
        };
        $write(['Browave AMS']);
        $write(['Work Day Status Report']);
        $write(['Month', $month, 'Year', $year, 'Status', $status ?: 'All', 'Reason', $reason ?: 'All', 'Export Date', date('Y-m-d'), 'Total Records', count($rows)]);
        $write([]);
        $write(['Date', 'Day', 'Status', 'Reason', 'Notes']);
        foreach ($rows as $row) {
            $write([$row['work_date'], $row['day'], $row['status'], $row['reason'], $row['notes']]);
        }
        fclose($output);
    }

    private function sendPrintableReport(array $rows, int $year, int $month, string $status, string $reason): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="browave-work-calendar-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.html"');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Browave AMS Work Day Status Report</title><style>body{font-family:Arial,sans-serif;color:#122033}table{border-collapse:collapse;width:100%}th,td{border:1px solid #cbd5e1;padding:8px;text-align:left}h1{color:#003686}small{color:#64748b}</style></head><body>';
        echo '<h1>Browave AMS</h1><h2>Work Day Status Report</h2><p>Month: ' . htmlspecialchars((string) $month) . ' | Year: ' . $year . ' | Status: ' . htmlspecialchars($status ?: 'All') . ' | Reason: ' . htmlspecialchars($reason ?: 'All') . '<br><small>Export Date: ' . date('Y-m-d') . ' | Total Records: ' . count($rows) . '</small></p><table><thead><tr><th>Date</th><th>Day</th><th>Status</th><th>Reason</th><th>Notes</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr><td>' . htmlspecialchars($row['work_date']) . '</td><td>' . htmlspecialchars($row['day']) . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . htmlspecialchars($row['reason']) . '</td><td>' . htmlspecialchars($row['notes']) . '</td></tr>';
        }
        echo '</tbody></table></body></html>';
    }

    private function validYear($year): int
    {
        $year = (int) $year;
        return $year >= 2000 && $year <= 2100 ? $year : (int) date('Y');
    }

    private function validMonth($month): int
    {
        $month = (int) $month;
        return $month >= 1 && $month <= 12 ? $month : (int) date('n');
    }
}
