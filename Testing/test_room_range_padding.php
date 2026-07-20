<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Room.php';

$db = (new Database())->connect();
$roomModel = new Room($db);

$cleanup = function () use ($db) {
    $db->exec("DELETE FROM rooms WHERE room_no IN ('C09','C10','C11','C12','C13','C14','C15','C9','C10','C11','C12','C13','C14','C15','D9','D10','D11','D12','D13','D14','D15')");
};

$cleanup();

$accommodationStmt = $db->prepare('INSERT INTO accommodations (accommodation_name, accommodation_type) VALUES (?, ?)');
$accommodationStmt->execute(['Test Padding Accommodation', 'Hotel']);
$accommodationId = $db->lastInsertId();

$buildingStmt = $db->prepare('INSERT INTO buildings (accommodation_id, building_name) VALUES (?, ?)');
$buildingStmt->execute([$accommodationId, 'Test Padding Building']);
$buildingId = $db->lastInsertId();

$floorStmt = $db->prepare('INSERT INTO floors (building_id, floor_name) VALUES (?, ?)');
$floorStmt->execute([$buildingId, 'Test Padding Floor']);
$floorId = $db->lastInsertId();

$withZeros = $roomModel->createRange([
    'floor_id' => $floorId,
    'room_type' => 'Single',
    'capacity' => 1,
    'current_occupancy' => 0,
    'status' => 'Available',
    'gender_restriction' => 'Any',
    'remarks' => ''
], 'C09', 'C11');

if (($withZeros['room_nos'] ?? []) !== ['C09','C10','C11']) {
    fwrite(STDERR, "Expected zero-padded range, got: " . json_encode($withZeros['room_nos'] ?? []) . "\n");
    exit(1);
}

$withoutZeros = $roomModel->createRange([
    'floor_id' => $floorId,
    'room_type' => 'Single',
    'capacity' => 1,
    'current_occupancy' => 0,
    'status' => 'Available',
    'gender_restriction' => 'Any',
    'remarks' => ''
], 'D9', 'D11');

if (($withoutZeros['room_nos'] ?? []) !== ['D9','D10','D11']) {
    fwrite(STDERR, "Expected non-padded range, got: " . json_encode($withoutZeros['room_nos'] ?? []) . "\n");
    exit(1);
}

$cleanup();
$db->exec("DELETE FROM rooms WHERE floor_id = {$floorId}");
$db->exec("DELETE FROM floors WHERE id = {$floorId}");
$db->exec("DELETE FROM buildings WHERE id = {$buildingId}");
$db->exec("DELETE FROM accommodations WHERE id = {$accommodationId}");

echo "Room range padding verified.\n";
