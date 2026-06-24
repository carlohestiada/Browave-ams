<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$today = date('Y-m-d');

// Determine expected active employees count.
$stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM employees WHERE status = 'Active' AND DATE(created_at) <= ?");
$stmt->execute([$today]);
$expected = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/recalculate';
$_POST = ['date' => $today];

ob_start();
include __DIR__ . '/../public/api/meals/index.php';
$output = ob_get_clean();

$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Meals recalculate API failed. Output: $output\n";
    exit(1);
}

if (!isset($result['active_count']) || !isset($result['meal_count'])) {
    echo "Meals recalculate API returned incomplete data. Output: $output\n";
    exit(1);
}

if ((int)$result['active_count'] !== $expected) {
    echo "Meals recalculate API returned wrong active_count. Expected $expected, got {$result['active_count']}.\n";
    exit(1);
}

echo "Meals recalculate API test passed. active_count={$result['active_count']}, meal_count={$result['meal_count']}.\n";
