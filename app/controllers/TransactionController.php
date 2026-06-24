<?php

require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Employee.php';

class TransactionController
{
    private $transaction;
    private $employee;

    public function __construct($db)
    {
        $this->transaction = new Transaction($db);
        $this->employee = new Employee($db);
    }

    private function refreshEmployeeStatusAfterTransaction($employeeId, $type, $transactionDate)
    {
        $today = date('Y-m-d');

        if ($transactionDate <= $today) {
            $this->employee->updateStatus($employeeId, $type === 'arrival' ? 'Active' : 'Inactive');
            return;
        }

        $this->employee->syncStatusesByTransactions($today, $employeeId);

        if ($type === 'arrival') {
            $employee = $this->employee->getById($employeeId);
            if ($employee && $employee['status'] === 'Active') {
                $this->employee->updateStatus($employeeId, 'Inactive');
            }
        }
    }

    public function storeArrival()
    {
        $data = $_POST;

        if (empty($data['employee_id']) || empty($data['transaction_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        // prevent duplicate arrival for same employee on same date
        $exists = $this->transaction->exists($data['employee_id'], 'arrival', $data['transaction_date']);
        if ($exists) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Employee is already assigned to that date.']);
            return;
        }

        $this->transaction->create([
            'employee_id' => $data['employee_id'],
            'transaction_type' => 'arrival',
            'transaction_date' => $data['transaction_date'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->refreshEmployeeStatusAfterTransaction($data['employee_id'], 'arrival', $data['transaction_date']);

        echo json_encode(['success' => true]);
    }

    public function storeDeparture()
    {
        $data = $_POST;

        if (empty($data['employee_id']) || empty($data['transaction_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        // prevent duplicate departure for same employee on same date
        $exists = $this->transaction->exists($data['employee_id'], 'departure', $data['transaction_date']);
        if ($exists) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Departure already recorded for this employee on this date']);
            return;
        }

        $this->transaction->create([
            'employee_id' => $data['employee_id'],
            'transaction_type' => 'departure',
            'transaction_date' => $data['transaction_date'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->refreshEmployeeStatusAfterTransaction($data['employee_id'], 'departure', $data['transaction_date']);

        echo json_encode(['success' => true]);
    }

    public function listByType($type)
    {
        $this->employee->syncStatusesByTransactions();

        // The model reads optional $_GET filters: date_from, date_to, department_id, employee_id
        echo json_encode($this->transaction->getByType($type));
    }

    public function show($id)
    {
        $transaction = $this->transaction->getById($id);
        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transaction not found']);
            return;
        }
        echo json_encode($transaction);
    }

    public function update($id)
    {
        parse_str(file_get_contents("php://input"), $data);

        if (empty($data['employee_id']) || empty($data['transaction_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            return;
        }

        // Get the existing transaction to know its type
        $existingTx = $this->transaction->getById($id);
        if (!$existingTx) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transaction not found']);
            return;
        }

        // Check for duplicates (excluding current transaction)
        $isDuplicate = $this->transaction->exists(
            $data['employee_id'],
            $existingTx['transaction_type'],
            $data['transaction_date'],
            $id // exclude current ID
        );

        if ($isDuplicate) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Employee already has a ' . $existingTx['transaction_type'] . ' record on this date'
            ]);
            return;
        }

        $this->transaction->update($id, [
            'employee_id' => $data['employee_id'],
            'transaction_date' => $data['transaction_date'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $existingTx['employee_id']);
        $this->refreshEmployeeStatusAfterTransaction(
            $data['employee_id'],
            $existingTx['transaction_type'],
            $data['transaction_date']
        );

        echo json_encode(['success' => true]);
    }

    public function destroy($id)
    {
        $transaction = $this->transaction->getById($id);
        if (!$transaction) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transaction not found']);
            return;
        }

        $result = $this->transaction->delete($id);
        if (!$result) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unable to delete transaction']);
            return;
        }

        $today = date('Y-m-d');
        $this->employee->syncStatusesByTransactions($today, $transaction['employee_id']);

        if (!$this->transaction->hasTransactionOnOrBefore($transaction['employee_id'], $today)) {
            $this->employee->updateStatus($transaction['employee_id'], 'Active');
        }

        echo json_encode(['success' => true]);
    }
}
