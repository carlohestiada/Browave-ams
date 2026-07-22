<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/TransactionController.php';

$db = (new Database())->connect();
$controller = new TransactionController($db);
$method = new ReflectionMethod(TransactionController::class, 'validateTransactionRequest');
$method->setAccessible(true);

$validation = $method->invoke($controller, ['employee_id' => 999999, 'transaction_date' => '2026-01-01'], 'arrival');
if ($validation['valid'] !== false || strpos($validation['error'], 'already has') === false) {
    echo "Transaction validation message test failed.\n";
    exit(1);
}

echo "Transaction message test passed.\n";
