<?php
require_once __DIR__ . '/../app/config/database.php';

$db = (new Database())->connect();

$users = [
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'Admin'],
    ['username' => 'hr', 'password' => 'hr123', 'role' => 'HR'],
    ['username' => 'viewer', 'password' => 'viewer123', 'role' => 'Viewer'],
];

foreach ($users as $u) {
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$u['username']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $passwordHash = password_hash($u['password'], PASSWORD_BCRYPT);

    if ($existing) {
        // update password, role, status
        $update = $db->prepare('UPDATE users SET password_hash = ?, role = ?, status = ? WHERE id = ?');
        $ok = $update->execute([$passwordHash, $u['role'], 'Active', $existing['id']]);
        echo "Updated user: {$u['username']} (id={$existing['id']}) => " . ($ok ? "OK" : "FAILED") . "\n";
    } else {
        $insert = $db->prepare('INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, ?)');
        $ok = $insert->execute([$u['username'], $passwordHash, $u['role'], 'Active']);
        $id = $ok ? $db->lastInsertId() : null;
        echo "Created user: {$u['username']} (id={$id}) => " . ($ok ? "OK" : "FAILED") . "\n";
    }
}

// Show resulting rows
echo "\nCurrent users (admin/hr/viewer):\n";
$names = ['admin','hr','viewer'];
$in = str_repeat('?,', count($names) - 1) . '?';
$select = $db->prepare("SELECT id, username, role, status, created_at FROM users WHERE username IN ($in)");
$select->execute($names);
$rows = $select->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "- id={$r['id']} username={$r['username']} role={$r['role']} status={$r['status']} created_at={$r['created_at']}\n";
}

echo "\nDone.\n";
