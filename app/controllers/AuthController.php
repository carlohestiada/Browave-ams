<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/login_rate_limiter.php';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function login($username, $password, $role)
    {
        $rateLimiter = new LoginRateLimiter();
        $addressKey = hash('sha256', 'ip|' . $this->clientAddress());
        $accountKey = hash('sha256', 'account|' . strtolower(trim((string) $username)));

        if ($rateLimiter->isBlocked($addressKey) || $rateLimiter->isBlocked($accountKey)) {
            return false;
        }

        $db = (new Database())->connect();

        $userModel = new User($db);

        $user = $userModel->findByUsername($username);

        if (!$user) {
            $this->recordLoginFailure($rateLimiter, $addressKey, $accountKey);
            return false;
        }

        if ($user['status'] != 'Active') {
            $this->recordLoginFailure($rateLimiter, $addressKey, $accountKey);
            return false;
        }

        if ($user['role'] !== $role) {
            $this->recordLoginFailure($rateLimiter, $addressKey, $accountKey);
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $rateLimiter->clear($addressKey);
            $rateLimiter->clear($accountKey);

            return true;
        }

        $this->recordLoginFailure($rateLimiter, $addressKey, $accountKey);
        return false;
    }

    private function recordLoginFailure(LoginRateLimiter $rateLimiter, string $addressKey, string $accountKey): void
    {
        $rateLimiter->recordFailure($addressKey);
        $rateLimiter->recordFailure($accountKey);
    }

    private function clientAddress(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}