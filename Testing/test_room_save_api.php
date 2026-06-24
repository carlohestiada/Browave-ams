<?php
// Verify rooms API POST saves a room and the insert works in the database.
chdir(__DIR__ . '/../public/api');

chdir(__DIR__ . '/../public/api');

$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create supporting accommodation, building, and floor rows so room creation can work.
$accommodationStmt = $db->prepare('INSERT INTO accommodations (accommodation_name, accommodation_type) VALUES (?, ?)');
$accommodationStmt->execute(['Test Accommodation', 'Hotel']);
$accommodationId = $db->lastInsertId();

$buildingStmt = $db->prepare('INSERT INTO buildings (accommodation_id, building_name) VALUES (?, ?)');
$buildingStmt->execute([$accommodationId, 'Test Building']);
$buildingId = $db->lastInsertId();

$floorStmt = $db->prepare('INSERT INTO floors (building_id, floor_name) VALUES (?, ?)');
$floorStmt->execute([$buildingId, 'Test Floor']);
$floorId = $db->lastInsertId();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'floor_id' => $floorId,
    'room_no' => 'TEST-101',
    'room_type' => 'Single',
    'capacity' => 2,
    'status' => 'Available',
    'gender_restriction' => 'Any',
    'remarks' => 'API save test'
];

ob_start();
include __DIR__ . '/../public/api/rooms.php';
$output = ob_get_clean();

echo "API output: $output\n";

$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Save API test failed.\n";
    exit(1);
}

$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$select = $db->prepare('SELECT * FROM rooms WHERE room_no = ?');
$select->execute(['TEST-101']);
$room = $select->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    echo "Room row not found in database after save.\n";
    exit(1);
}

echo "Room saved successfully: ID {$room['id']} | {$room['room_no']} | {$room['room_type']}\n";

$delete = $db->prepare('DELETE FROM rooms WHERE id = ?');
$delete->execute([$room['id']]);

$deleteFloor = $db->prepare('DELETE FROM floors WHERE id = ?');
$deleteFloor->execute([$floorId]);

$deleteBuilding = $db->prepare('DELETE FROM buildings WHERE id = ?');
$deleteBuilding->execute([$buildingId]);

$deleteAccommodation = $db->prepare('DELETE FROM accommodations WHERE id = ?');
$deleteAccommodation->execute([$accommodationId]);

echo "Cleanup successful.\n";
