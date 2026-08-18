<?php

// Production database configuration.
// This file is used when APP_ENV is not local and the application
// resolves the environment as production.
// PostgreSQL is running on the same Ubuntu server as the PHP app, so use 127.0.0.1.
return [
    'driver' => 'pgsql',
    'host' => '127.0.0.1',
    'port' => 5432,
    'dbname' => 'browave_ams',
    'username' => 'browave_user',
    'password' => 'alwaysBrowave123',
    'charset' => 'utf8',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];