<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfTokenFromRequest(): string
{
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if ($headerToken !== '') {
        return $headerToken;
    }

    return $_POST['csrf_token'] ?? '';
}

function csrfRequestIsValid(): bool
{
    $token = csrfTokenFromRequest();

    return $token !== '' && hash_equals(csrfToken(), $token);
}