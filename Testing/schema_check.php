<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=browave_ams","root","");
foreach ($pdo->query("SHOW CREATE TABLE departments") as $row) {
    echo $row['Create Table'] . "\n";
}
