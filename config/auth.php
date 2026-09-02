<?php

declare(strict_types=1);

function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function isAuthenticated(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function requireAuthentication(): void
{
    if (!isAuthenticated()) {
        header('Location: /cepre_untels/public/login.php');
        exit;
    }
}

function currentUser(): ?array
{
    startSession();
    return $_SESSION['user'] ?? null;
}

function logout(): void
{
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
