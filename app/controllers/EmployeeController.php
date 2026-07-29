<?php

require_once __DIR__ . '/../models/Employee.php';

class EmployeeController
{
    private $employee;

    public function __construct($db)
    {
        $this->employee = new Employee($db);
    }

    public function index()
    {
        $this->employee->syncStatusesByTransactions();

        $excludeDate = $_GET['exclude_arrived_date'] ?? null;
        $markDate = $_GET['mark_arrived_date'] ?? null;
        $excludeTransactionType = $_GET['exclude_transaction_type'] ?? null;
        $excludeTransactionDate = $_GET['exclude_transaction_date'] ?? null;
        $status = $_GET['status'] ?? null;
        $departmentId = $_GET['department_id'] ?? null;
        $search = $_GET['search'] ?? null;

        echo json_encode(
            $this->employee->getAll(
                $excludeDate,
                $markDate,
                $status,
                $departmentId,
                $search,
                $excludeTransactionType,
                $excludeTransactionDate
            )
        );
    }

    public function store()
    {
        $data = $_POST;

        try {
            $result = $this->employee->create($data);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            return;
        }

        if ($result === false) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unable to create employee. Please check the required fields and try again.'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'id' => (int) $result,
            'employee_code' => $data['employee_code'] ?? '',
            'full_name' => $data['full_name'] ?? ''
        ]);
    }

    public function edit($id)
    {
        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $id);

        echo json_encode(
            $this->employee->getById($id)
        );
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"),$data);

        $result = $this->employee->update($id,$data);

        echo json_encode([
            'success'=>$result
        ]);
    }

    public function destroy($id)
    {
        $result = $this->employee->delete($id);

        if (is_array($result) && !$result['success']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'Unable to delete employee.'
            ]);
            return;
        }

        echo json_encode([
            'success' => true
        ]);
    }
}
