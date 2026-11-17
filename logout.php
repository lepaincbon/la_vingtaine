<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (validateCsrf()) {
        logoutProfessional();
    }
}

header('Location: index.php');
exit;