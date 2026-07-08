<?php

/**
 * Resolve the current application environment.
 *
 * Priority:
 * 1. APP_ENV environment variable (recommended for CLI and containers)
 * 2. Server/OS detection for local XAMPP vs production Linux
 * 3. Default to local for development convenience
 */
function getApplicationEnvironment(): string
{
    if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] !== '') {
        return strtolower(trim($_SERVER['APP_ENV']));
    }

    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== '') {
        return strtolower(trim($_ENV['APP_ENV']));
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $serverSoftware = strtolower((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));

    if (PHP_OS_FAMILY === 'Windows' || $host === 'localhost' || $host === '127.0.0.1' || strpos($serverSoftware, 'xampp') !== false) {
        return 'local';
    }

    return 'production';
}

function getEnvironmentConfigFile(?string $environment = null): string
{
    $environment = $environment ?? getApplicationEnvironment();
    $environment = strtolower(trim($environment));

    return __DIR__ . '/database.' . $environment . '.php';
}
