<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Room.php';

$db = (new Database())->connect();
$roomModel = new Room($db);

$db->exec("DELETE FROM rooms WHERE room_no IN ('A01','A02','A03','A04')");

$stmt = $db->query("SELECT id FROM departments LIMIT 1");
$departmentId = $stmt->fetchColumn();
if (!$departmentId) {
    $db->exec("INSERT INTO departments (department_name) VALUES ('Test Department')");
    $departmentId = $db->lastInsertId();
}

$stmt = $db->query("SELECT id FROM accommodations LIMIT 1");
$accommodationId = $stmt->fetchColumn();
if (!$accommodationId) {
    $db->exec("INSERT INTO accommodations (accommodation_name) VALUES ('Test Accommodation')");
    $accommodationId = $db->lastInsertId();
}

$stmt = $db->query("SELECT id FROM buildings WHERE accommodation_id = {$accommodationId} LIMIT 1");
$buildingId = $stmt->fetchColumn();
if (!$buildingId) {
    $db->exec("INSERT INTO buildings (accommodation_id, building_name) VALUES ({$accommodationId}, 'Test Building')");
    $buildingId = $db->lastInsertId();
}

$stmt = $db->query("SELECT id FROM floors WHERE building_id = {$buildingId} LIMIT 1");
$floorId = $stmt->fetchColumn();
if (!$floorId) {
    $db->exec("INSERT INTO floors (building_id, floor_name) VALUES ({$buildingId}, 'Test Floor')");
    $floorId = $db->lastInsertId();
}

$result = $roomModel->createRange([
    'floor_id' => $floorId,
    'room_type' => 'Single',
    'capacity' => 1,
    'current_occupancy' => 0,
    'status' => 'Available',
    'gender_restriction' => '',
    'remarks' => ''
], 'A01', 'A03');

if (!is_array($result) || ($result['created_count'] ?? 0) !== 3) {
    fwrite(STDERR, "Expected 3 created rooms, got: " . json_encode($result) . "\n");
    exit(1);
}

$createdRoomNos = $result['room_nos'] ?? [];
if ($createdRoomNos !== ['A01','A02','A03']) {
    fwrite(STDERR, "Unexpected generated room numbers: " . json_encode($createdRoomNos) . "\n");
    exit(1);
}

echo "Room range generation verified.\n";
exit(0);
