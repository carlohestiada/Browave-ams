<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Room.php';

$db = (new Database())->connect();
$roomModel = new Room($db);

$db->exec("DELETE FROM rooms WHERE room_no = 'GR-OPTIONAL-TEST'");

$accommodationStmt = $db->prepare('INSERT INTO accommodations (accommodation_name, accommodation_type) VALUES (?, ?)');
$accommodationStmt->execute(['Test Optional Gender', 'Hotel']);
$accommodationId = $db->lastInsertId();

$buildingStmt = $db->prepare('INSERT INTO buildings (accommodation_id, building_name) VALUES (?, ?)');
$buildingStmt->execute([$accommodationId, 'Test Optional Building']);
$buildingId = $db->lastInsertId();

$floorStmt = $db->prepare('INSERT INTO floors (building_id, floor_name) VALUES (?, ?)');
$floorStmt->execute([$buildingId, 'Test Optional Floor']);
$floorId = $db->lastInsertId();

$result = $roomModel->create([
    'floor_id' => $floorId,
    'room_no' => 'GR-OPTIONAL-TEST',
    'room_type' => 'Single',
    'capacity' => 1,
    'current_occupancy' => 0,
    'status' => 'Available',
    'gender_restriction' => '',
    'remarks' => ''
]);

if ($result !== true) {
    fwrite(STDERR, "Expected room creation to succeed with blank gender restriction, got: " . var_export($result, true) . "\n");
    exit(1);
}

$room = $db->query("SELECT gender_restriction FROM rooms WHERE room_no = 'GR-OPTIONAL-TEST' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (($room['gender_restriction'] ?? null) !== 'Any') {
    fwrite(STDERR, "Expected gender restriction to normalize to Any, got: " . ($room['gender_restriction'] ?? 'null') . "\n");
    exit(1);
}

echo "Optional gender restriction works.\n";

$db->exec("DELETE FROM rooms WHERE room_no = 'GR-OPTIONAL-TEST'");
$db->exec("DELETE FROM floors WHERE id = {$floorId}");
$db->exec("DELETE FROM buildings WHERE id = {$buildingId}");
$db->exec("DELETE FROM accommodations WHERE id = {$accommodationId}");
