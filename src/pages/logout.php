<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/security.php';
ensureSessionStarted();

setFlashToast('You have been signed out.', 'success');

unset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['role']);
session_regenerate_id(true);

header('Location: index.php?page=login');
exit;
