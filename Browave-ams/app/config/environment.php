<?php

/**
 * Minimal .env loader (no Composer dependency).
 * Only used for local convenience — on a real server, set environment
 * variables via Apache/nginx/systemd instead of relying on a file.
 * Existing environment variables always take priority over .env values.
 */
function loadDotEnvIfPresent(): void
{
    $envFile = dirname(__DIR__, 2) . '/.env';

    if (!is_file($envFile) || !is_readable($envFile)) {
        return;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($name === '' || getenv($name) !== false) {
            continue; // don't override real env vars
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }
}

loadDotEnvIfPresent();

/**
 * Resolve the current application environment.
 *
 * Priority:
 * 1. APP_ENV environment variable (recommended for CLI and containers)
 * 2. Server/OS detection for local development vs production Linux
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

    if (PHP_OS_FAMILY === 'Windows' || $host === 'localhost' || $host === '127.0.0.1') {
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
