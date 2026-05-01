<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

if ((string) ($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Location: index.php?page=home');
exit;
