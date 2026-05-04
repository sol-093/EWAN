<?php
declare(strict_types=1);

function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    // Use @ to suppress notices if a session was started by another component
    @session_start();
}

function getCsrfToken(): string
{
    ensureSessionStarted();
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    ensureSessionStarted();
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function requestCsrfToken(): ?string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (is_string($header) && $header !== '') {
        return $header;
    }

    if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
        return $_POST['_csrf'];
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['_csrf']) && is_string($decoded['_csrf'])) {
        return $decoded['_csrf'];
    }

    return null;
}

function checkLoginThrottle(string $email): ?string
{
    ensureSessionStarted();
    $key = strtolower(trim($email)) . '|' . (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $attempts = $_SESSION['login_attempts'][$key] ?? null;
    if (!is_array($attempts)) {
        return null;
    }

    $lockedUntil = (int) ($attempts['locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $remaining = max(1, $lockedUntil - time());
        return 'Too many login attempts. Try again in ' . $remaining . ' seconds.';
    }

    return null;
}

function recordLoginFailure(string $email): void
{
    ensureSessionStarted();
    $key = strtolower(trim($email)) . '|' . (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $attempts = $_SESSION['login_attempts'][$key] ?? ['count' => 0, 'locked_until' => 0];
    $attempts['count'] = (int) ($attempts['count'] ?? 0) + 1;

    if ($attempts['count'] >= 5) {
        $attempts['locked_until'] = time() + 300;
        $attempts['count'] = 0;
    }

    $_SESSION['login_attempts'][$key] = $attempts;
}

function clearLoginFailures(string $email): void
{
    ensureSessionStarted();
    $key = strtolower(trim($email)) . '|' . (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    unset($_SESSION['login_attempts'][$key]);
}

function setFlashToast(string $message, string $type = 'success'): void
{
    ensureSessionStarted();

    $message = trim($message);
    if ($message === '') {
        return;
    }

    $allowedTypes = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'info';
    }

    if (!isset($_SESSION['flash_toasts']) || !is_array($_SESSION['flash_toasts'])) {
        $_SESSION['flash_toasts'] = [];
    }

    $_SESSION['flash_toasts'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlashToasts(): array
{
    ensureSessionStarted();

    $toasts = $_SESSION['flash_toasts'] ?? [];
    unset($_SESSION['flash_toasts']);

    if (!is_array($toasts)) {
        return [];
    }

    $filtered = [];
    foreach ($toasts as $toast) {
        if (!is_array($toast)) {
            continue;
        }

        $message = trim((string) ($toast['message'] ?? ''));
        if ($message === '') {
            continue;
        }

        $type = (string) ($toast['type'] ?? 'info');
        if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $type = 'info';
        }

        $filtered[] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    return $filtered;
}
