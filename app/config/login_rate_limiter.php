<?php

class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private string $storageFile;

    public function __construct(?string $storageFile = null)
    {
        $this->storageFile = $storageFile ?? dirname(__DIR__) . '/logs/login_attempts.json';
    }

    public function isBlocked(string $key): bool
    {
        $attempts = $this->getAttempts();
        $now = time();

        return isset($attempts[$key])
            && count(array_filter($attempts[$key], static fn ($timestamp) => $timestamp > $now - self::WINDOW_SECONDS)) >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $key): void
    {
        $attempts = $this->getAttempts();
        $now = time();
        $recentAttempts = array_filter(
            $attempts[$key] ?? [],
            static fn ($timestamp) => $timestamp > $now - self::WINDOW_SECONDS
        );
        $recentAttempts[] = $now;
        $attempts[$key] = array_values($recentAttempts);
        $this->saveAttempts($attempts);
    }

    public function clear(string $key): void
    {
        $attempts = $this->getAttempts();
        unset($attempts[$key]);
        $this->saveAttempts($attempts);
    }

    private function getAttempts(): array
    {
        if (!is_file($this->storageFile)) {
            return [];
        }

        $contents = file_get_contents($this->storageFile);
        $attempts = json_decode($contents ?: '', true);

        return is_array($attempts) ? $attempts : [];
    }

    private function saveAttempts(array $attempts): void
    {
        $directory = dirname($this->storageFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        file_put_contents($this->storageFile, json_encode($attempts), LOCK_EX);
        @chmod($this->storageFile, 0600);
    }
}