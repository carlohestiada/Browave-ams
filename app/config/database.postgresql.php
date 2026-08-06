<?php

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
