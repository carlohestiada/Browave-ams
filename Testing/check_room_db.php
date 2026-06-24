<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=browave_ams","root","");
foreach (['accommodations','buildings','floors','rooms'] as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "$table: $count\n";
}
