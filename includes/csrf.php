<?php
declare(strict_types=1);

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function validateCsrf(): bool
{
    $token = $_SESSION['csrf_token'] ?? '';
    $provided = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || $provided === '') {
        return false;
    }
    return hash_equals($token, $provided);
}