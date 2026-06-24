<?php
// Verify accommodations API POST saves a new accommodation row.
chdir(__DIR__ . '/../public/api');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'accommodation_name' => 'Test Accommodation API',
    'accommodation_type' => 'Hotel',
    'address' => '123 Test Lane',
    'contact_person' => 'Test Contact',
    'contact_number' => '09171234567',
    'status' => 'Active'
];

ob_start();
include __DIR__ . '/../public/api/accommodations.php';
$output = ob_get_clean();

echo "API output: $output\n";

$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Save API test failed.\n";
    exit(1);
}

$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$select = $db->prepare('SELECT * FROM accommodations WHERE accommodation_name = ?');
$select->execute(['Test Accommodation API']);
$accommodation = $select->fetch(PDO::FETCH_ASSOC);

if (!$accommodation) {
    echo "Accommodation row not found in database after save.\n";
    exit(1);
}

echo "Accommodation saved successfully: ID {$accommodation['id']} | {$accommodation['accommodation_name']} | {$accommodation['accommodation_type']}\n";

$delete = $db->prepare('DELETE FROM accommodations WHERE id = ?');
$delete->execute([$accommodation['id']]);

echo "Cleanup successful.\n";
