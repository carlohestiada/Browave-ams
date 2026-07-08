<?php
require_once __DIR__ . '/../app/config/database.php';

$env = getApplicationEnvironment();
$config = loadDatabaseConfig($env);

printf("Environment: %s\n", $env);
printf("Host: %s\n", $config['host']);
printf("Database: %s\n", $config['dbname']);
printf("Username: %s\n", $config['username']);
