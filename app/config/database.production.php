<?php

// Production database configuration.
// This file is used when APP_ENV is not local and the application
// resolves the environment as production.
return [
    'driver' => 'pgsql',
    'host' => 'your-postgres-host',
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