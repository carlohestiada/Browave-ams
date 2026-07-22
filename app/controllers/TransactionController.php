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

    private function validateTransactionRequest($data, $type, $excludeId = null)
    {
        if (empty($data['employee_id']) || empty($data['transaction_date'])) {
            return ['valid' => false, 'statusCode' => 400, 'error' => 'Missing required fields'];
        }

        $transactionDate = trim($data['transaction_date']);

        $sameTypeRecord = $this->transaction->findByEmployeeAndDate($data['employee_id'], $transactionDate, $type, $excludeId);
        if ($sameTypeRecord) {
            $label = $type === 'arrival' ? 'an arrival' : 'a departure';
            return ['valid' => false, 'statusCode' => 400, 'error' => 'This employee already has ' . $label . ' on this date.'];
        }

        $otherType = $type === 'arrival' ? 'departure' : 'arrival';
        $otherTypeRecord = $this->transaction->findByEmployeeAndDate($data['employee_id'], $transactionDate, $otherType, $excludeId);
        if ($otherTypeRecord) {
            $label = $otherType === 'arrival' ? 'an arrival' : 'a departure';
            return ['valid' => false, 'statusCode' => 400, 'error' => 'This employee already has ' . $label . ' on this date.'];
        }

        return ['valid' => true, 'statusCode' => 200, 'transactionDate' => $transactionDate];
    }

    public function storeArrival()
    {
        $data = $_POST;

        $validation = $this->validateTransactionRequest($data, 'arrival');
        if (!$validation['valid']) {
            http_response_code($validation['statusCode']);
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            return;
        }

        $this->transaction->create([
            'employee_id' => $data['employee_id'],
            'transaction_type' => 'arrival',
            'transaction_date' => $validation['transactionDate'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->refreshEmployeeStatusAfterTransaction($data['employee_id'], 'arrival', $validation['transactionDate']);

        echo json_encode(['success' => true]);
    }

    public function storeDeparture()
    {
        $data = $_POST;

        $validation = $this->validateTransactionRequest($data, 'departure');
        if (!$validation['valid']) {
            http_response_code($validation['statusCode']);
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            return;
        }

        $this->transaction->create([
            'employee_id' => $data['employee_id'],
            'transaction_type' => 'departure',
            'transaction_date' => $validation['transactionDate'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->refreshEmployeeStatusAfterTransaction($data['employee_id'], 'departure', $validation['transactionDate']);

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

        // Get the existing transaction to know its type
        $existingTx = $this->transaction->getById($id);
        if (!$existingTx) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Transaction not found']);
            return;
        }

        $validation = $this->validateTransactionRequest($data, $existingTx['transaction_type'], $id);
        if (!$validation['valid']) {
            http_response_code($validation['statusCode']);
            echo json_encode(['success' => false, 'error' => $validation['error']]);
            return;
        }

        $this->transaction->update($id, [
            'employee_id' => $data['employee_id'],
            'transaction_date' => $validation['transactionDate'],
            'remarks' => $data['remarks'] ?? ''
        ]);

        $this->employee->syncStatusesByTransactions(date('Y-m-d'), $existingTx['employee_id']);
        $this->refreshEmployeeStatusAfterTransaction(
            $data['employee_id'],
            $existingTx['transaction_type'],
            $validation['transactionDate']
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
