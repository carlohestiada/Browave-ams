<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$date = date('Y-m-d', strtotime('+31 days'));
$cleanup = $db->prepare('DELETE FROM daily_headcount WHERE date = ?');
$cleanup->execute([$date]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'date' => $date,
    'active_count' => 20,
    'meal_count' => 20
];
ob_start();
include __DIR__ . '/../public/api/meals/index.php';
$output1 = ob_get_clean();
$result1 = json_decode($output1, true);
if (!$result1 || empty($result1['success']) || empty($result1['created'])) {
    echo "Meals upsert API initial save failed. Output: $output1\n";
    exit(1);
}

$select = $db->prepare('SELECT * FROM daily_headcount WHERE date = ?');
$select->execute([$date]);
$meal1 = $select->fetch(PDO::FETCH_ASSOC);
if (!$meal1) {
    echo "Initial meal plan row not found after save.\n";
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'date' => $date,
    'active_count' => 25,
    'meal_count' => 30
];
ob_start();
include __DIR__ . '/../public/api/meals/index.php';
$output2 = ob_get_clean();
$result2 = json_decode($output2, true);
if (!$result2 || empty($result2['success']) || empty($result2['updated'])) {
    echo "Meals upsert API update failed. Output: $output2\n";
    exit(1);
}

$select->execute([$date]);
$meal2 = $select->fetch(PDO::FETCH_ASSOC);
if (!$meal2 || $meal2['active_count'] != 25 || $meal2['meal_count'] != 30) {
    echo "Meals upsert API update did not persist updated values.\n";
    print_r($meal2);
    exit(1);
}

if ($meal1['id'] != $meal2['id']) {
    echo "Meals upsert API created a new row instead of updating existing one.\n";
    exit(1);
}

$delete = $db->prepare('DELETE FROM daily_headcount WHERE id = ?');
$delete->execute([$meal2['id']]);

echo "Meals upsert API test passed. Row {$meal2['id']} updated successfully.\n";
