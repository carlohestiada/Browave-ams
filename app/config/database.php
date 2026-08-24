<?php

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/environment.php';

/**
 * Load the database configuration for the active environment.
 *
 * Additional environments can be supported later by creating files such as:
 * database.testing.php, database.staging.php, database.qa.php.
 */
function loadDatabaseConfig(?string $environment = null): array
{
    $environment = $environment ?? getApplicationEnvironment();
    $environment = strtolower(trim($environment));

    $configFile = __DIR__ . '/database.' . $environment . '.php';

    if (!is_file($configFile)) {
        throw new RuntimeException("Database configuration for environment '{$environment}' was not found.");
    }

    $config = require $configFile;

    if (!is_array($config)) {
        throw new RuntimeException("Database configuration for environment '{$environment}' must return an array.");
    }

    return $config;
}

class Database
{
    private $config;

    public function __construct(?string $environment = null)
    {
        $this->config = loadDatabaseConfig($environment);
    }

    public function connect(): PDO
    {
        $driver = strtolower((string) ($this->config['driver'] ?? 'pgsql'));

        if ($driver === 'pgsql') {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $driver,
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname']
            );
        } else {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $driver,
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset'] ?? 'utf8mb4'
            );
        }

        try {
            $pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            if ($driver === 'pgsql') {
                $charset = strtoupper($this->config['charset'] ?? 'utf8');
                $pdo->exec("SET TIME ZONE 'Asia/Manila'");
                $pdo->exec("SET client_encoding TO '{$charset}'");
            } else {
                $pdo->exec("SET time_zone = '+08:00'");
            }

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
