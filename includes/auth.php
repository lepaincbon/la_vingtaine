<?php
declare(strict_types=1);

function currentProfessional(): ?array
{
    if (empty($_SESSION['professional']) || !is_array($_SESSION['professional'])) {
        return null;
    }
    return $_SESSION['professional'];
}

function requireAuth(): void
{
    if (currentProfessional() !== null) {
        return;
    }

    $redirect = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit;
}

function loginProfessional(array $professional): void
{
    $_SESSION['professional'] = [
        'id' => (int)$professional['id'],
        'company_name' => (string)$professional['company_name'],
        'customer_email' => (string)$professional['customer_email'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logoutProfessional(): void
{
    $_SESSION = [];
    if (session_cookie_name() !== '') {
        setcookie(session_cookie_name(), '', time() - 3600, '/');
    }
    session_destroy();
}