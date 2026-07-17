<?php

// Production database configuration.
// This file is used when APP_ENV is not local and the application
// resolves the environment as production.
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'browave_ams',
    'username' => 'browave',
    'password' => 'alwaysBrowave123',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];