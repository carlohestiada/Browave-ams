<?php

// Local XAMPP database configuration.
// Keep this file separate from production credentials to avoid editing
// the same config on every deployment.
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'browave_ams',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
