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
        echo json_encode($this->dailyHeadcount->getAll());
    }

    public function getByDate($date)
    {
        $headcount = $this->dailyHeadcount->getByDate($date);
        
        if (!$headcount) {
            $activeCount = $this->dailyHeadcount->calculateActiveCount($date);
            $headcount = [
                'date' => $date,
                'active_count' => $activeCount,
                'meal_count' => 0
            ];
        }

        echo json_encode($headcount);
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
}
