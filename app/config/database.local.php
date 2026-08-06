<?php

// Local PostgreSQL database configuration.
// Keep this file separate from production credentials to avoid editing
// the same config on every deployment.
return [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'dbname' => 'browave_ams',
    'username' => 'postgres',
    'password' => 'alwaysBrowave123',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
