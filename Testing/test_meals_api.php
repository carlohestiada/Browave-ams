<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$date = date('Y-m-d', strtotime('+30 days'));

// Ensure no duplicate row for the chosen test date.
$cleanup = $db->prepare('DELETE FROM daily_headcount WHERE date = ?');
$cleanup->execute([$date]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'date' => $date,
    'active_count' => 5,
    'meal_count' => 5
];

ob_start();
include __DIR__ . '/../public/api/meals/index.php';
$output = ob_get_clean();

$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Meals API save failed. Output: $output\n";
    exit(1);
}

$select = $db->prepare('SELECT * FROM daily_headcount WHERE date = ?');
$select->execute([$date]);
$meal = $select->fetch(PDO::FETCH_ASSOC);
if (!$meal) {
    echo "Meal plan row not found in database after save.\n";
    exit(1);
}

$delete = $db->prepare('DELETE FROM daily_headcount WHERE id = ?');
$delete->execute([$meal['id']]);

echo "Meals API save test passed. Inserted meal plan ID {$meal['id']} for date {$meal['date']}.\n";
