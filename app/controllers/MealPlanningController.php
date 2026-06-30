<?php

require_once __DIR__ . '/../models/DailyHeadcount.php';
require_once __DIR__ . '/../models/Employee.php';

class MealPlanningController
{
    private $dailyHeadcount;
    private $employee;

    public function __construct($db)
    {
        $this->dailyHeadcount = new DailyHeadcount($db);
        $this->employee = new Employee($db);
    }

    public function index()
    {
        $headcounts = $this->dailyHeadcount->getAll();
        $normalized = [];

        foreach ($headcounts as $headcount) {
            $headcount['active_count'] = $this->dailyHeadcount->calculateActiveCount($headcount['date']);
            $normalized[] = $headcount;
        }

        echo json_encode(array_values($this->attachTransactions($normalized)));
    }

    public function getByDate($date)
    {
        $headcount = $this->dailyHeadcount->getByDate($date);
        $activeCount = $this->dailyHeadcount->calculateActiveCount($date);
        
        if (!$headcount) {
            $headcount = [
                'date' => $date,
                'active_count' => $activeCount,
                'meal_count' => 0
            ];
        } else {
            $headcount['active_count'] = $activeCount;
        }

        echo json_encode($this->attachTransactions([$headcount])[$date] ?? $headcount);
    }

    public function getRange($startDate, $endDate)
    {
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate) || $startDate > $endDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid date range']);
            return;
        }

        $savedHeadcounts = $this->dailyHeadcount->getBetween($startDate, $endDate);
        $headcountsByDate = [];

        foreach ($savedHeadcounts as $headcount) {
            $headcount['active_count'] = $this->dailyHeadcount->calculateActiveCount($headcount['date']);
            $headcountsByDate[$headcount['date']] = $headcount;
        }

        $rows = [];
        $current = new DateTime($startDate);
        $last = new DateTime($endDate);

        while ($current <= $last) {
            $date = $current->format('Y-m-d');
            $rows[] = $headcountsByDate[$date] ?? [
                'date' => $date,
                'active_count' => $this->dailyHeadcount->calculateActiveCount($date),
                'meal_count' => 0
            ];

            $current->modify('+1 day');
        }

        echo json_encode(array_values($this->attachTransactions($rows, $startDate, $endDate)));
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

    public function recalculate()
    {
        $date = $_POST['date'] ?? date('Y-m-d');

        $activeCount = $this->dailyHeadcount->calculateActiveCount($date);
        $mealCount = $_POST['meal_count'] ?? $activeCount;

        $this->dailyHeadcount->updateHeadcount($date, $activeCount, $mealCount);

        echo json_encode([
            'success' => true,
            'active_count' => $activeCount,
            'meal_count' => $mealCount
        ]);
    }

    private function attachTransactions($headcounts, $startDate = null, $endDate = null)
    {
        if (empty($headcounts)) {
            return [];
        }

        $dates = array_column($headcounts, 'date');
        $startDate = $startDate ?? min($dates);
        $endDate = $endDate ?? max($dates);
        $transactions = $this->dailyHeadcount->getTransactionsByDateRange($startDate, $endDate);
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

    private function isValidDate($date)
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
