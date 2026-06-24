<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=browave_ams', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = 'testuser_' . time();

// Clean up any existing test users
$cleanup = $db->prepare('DELETE FROM users WHERE username = ?');
$cleanup->execute([$username]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '';
$_POST = [
    'username' => $username,
    'password' => 'Password123!',
    'role' => 'Viewer',
    'status' => 'Active'
];

ob_start();
include __DIR__ . '/../public/api/users.php';
$output = ob_get_clean();
$result = json_decode($output, true);
if (!$result || empty($result['success'])) {
    echo "Users API save failed. Output: $output\n";
    exit(1);
}

$select = $db->prepare('SELECT * FROM users WHERE username = ?');
$select->execute([$username]);
$user = $select->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "User row not found in database after save.\n";
    exit(1);
}

// Update user via method override
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/' . $user['id'];
$_POST = [
    '_method' => 'PUT',
    'username' => $username,
    'role' => 'HR',
    'status' => 'Inactive'
];

ob_start();
include __DIR__ . '/../public/api/users.php';
$output2 = ob_get_clean();
$result2 = json_decode($output2, true);
if (!$result2 || empty($result2['success'])) {
    echo "Users API update failed. Output: $output2\n";
    exit(1);
}

$select->execute([$username]);
$user2 = $select->fetch(PDO::FETCH_ASSOC);
if (!$user2 || $user2['role'] !== 'HR' || $user2['status'] !== 'Inactive') {
    echo "Users API update did not persist changes.\n";
    print_r($user2);
    exit(1);
}

// Delete user
$_SERVER['REQUEST_METHOD'] = 'DELETE';
$_SERVER['PATH_INFO'] = '/' . $user2['id'];
ob_start();
include __DIR__ . '/../public/api/users.php';
$output3 = ob_get_clean();
$result3 = json_decode($output3, true);
if (!$result3 || empty($result3['success'])) {
    echo "Users API delete failed. Output: $output3\n";
    exit(1);
}

$select->execute([$username]);
$deleted = $select->fetch(PDO::FETCH_ASSOC);
if ($deleted) {
    echo "Users API delete did not remove the user.\n";
    exit(1);
}

echo "Users API create/update/delete test passed.\n";
