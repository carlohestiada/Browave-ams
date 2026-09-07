<?php

require_once __DIR__ . '/../models/DailyHeadcount.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../services/MealCalculationService.php';

class MealPlanningController
{
    private $dailyHeadcount;
    private $employee;
    private $calculationService;

    public function __construct($db)
    {
        $this->dailyHeadcount = new DailyHeadcount($db);
        $this->employee = new Employee($db);
        $this->calculationService = new MealCalculationService($db);
    }

    public function index()
    {
        $headcounts = $this->dailyHeadcount->getAll();
        if (empty($headcounts)) {
            echo json_encode([]);
            return;
        }

        $dates = array_column($headcounts, 'date');
        $calculated = $this->calculationService->getHeadcountsForDateRange(min($dates), max($dates));
        $byDate = [];
        foreach ($calculated as $headcount) {
            $byDate[$headcount['date']] = $headcount;
        }

        $normalized = array_values(array_filter(array_map(
            static fn($headcount) => $byDate[$headcount['date']] ?? null,
            $headcounts
        )));

        echo json_encode(array_values($this->calculationService->attachTransactionsToHeadcounts($normalized)));
    }

    public function getByDate($date)
    {
        $rows = $this->calculationService->getHeadcountsForDateRange($date, $date);
        $withTransactions = $this->calculationService->attachTransactionsToHeadcounts($rows, $date, $date);

        echo json_encode($withTransactions[$date] ?? ($rows[0] ?? ['date' => $date]));
    }

    public function saveWorkDayStatus($date)
    {
        if (!$this->isValidDate($date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date']);
            return;
        }

        $status = trim((string) ($_POST['status'] ?? ''));
        if (!$this->calculationService->saveWorkDayStatus($date, $status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid work day status']);
            return;
        }

        echo json_encode(['success' => true]);
    }

    public function getRange($startDate, $endDate)
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate) || $startDate > $endDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date range']);
            return;
        }

        $rows = $this->calculationService->getHeadcountsForDateRange($startDate, $endDate);

        echo json_encode(array_values($this->calculationService->attachTransactionsToHeadcounts($rows, $startDate, $endDate)));
    }

    public function getLunchboxEmployees($date)
    {
        if (!$this->isValidDate($date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date']);
            return;
        }

        $employees = $this->calculationService->getLunchboxEligibleEmployees($date);
        echo json_encode($employees);
    }

    public function saveSundayLunchBox($date)
    {
        if (!$this->isValidDate($date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date']);
            return;
        }

        $value = isset($_POST['lunch_box']) ? (int) $_POST['lunch_box'] : 0;
        $saved = $this->calculationService->saveSundayLunchBoxOverride($date, $value);

        echo json_encode(['success' => $saved]);
    }

    public function edit($id)
    {
        echo json_encode(
            $this->dailyHeadcount->getById($id)
        );
    }

    public function store()
    {
        $data = $_POST;

        if (empty($data['date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Date is required']);
            return;
        }

        $existing = $this->dailyHeadcount->getByDate($data['date']);
        if ($existing && isset($existing['id'])) {
            $result = $this->dailyHeadcount->update($existing['id'], $data);
            echo json_encode(['success' => $result, 'updated' => true, 'id' => $existing['id']]);
            return;
        }

        $result = $this->dailyHeadcount->create($data);
        echo json_encode(['success' => $result, 'created' => true]);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Date is required']);
            return;
        }

        $result = $this->dailyHeadcount->update($id, $data);

        echo json_encode(['success' => $result]);
    }

    public function destroy($id)
    {
        $result = $this->dailyHeadcount->delete($id);

        echo json_encode(['success' => $result]);
    }

    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
