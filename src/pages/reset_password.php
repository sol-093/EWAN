<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
ensureSessionStarted();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=home');
    exit;
}

require_once __DIR__ . '/../core/db.php';

$csrfToken = getCsrfToken();
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$message = '';

if ($token === '') {
    $message = 'Reset token is missing.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        $message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $message = 'Password confirmation does not match.';
        } else {
            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare(
                'SELECT id
                 FROM users
                 WHERE reset_token_hash = :token_hash
                   AND reset_token_expires_at IS NOT NULL
                   AND reset_token_expires_at > NOW()
                 LIMIT 1'
            );
            $stmt->execute(['token_hash' => $tokenHash]);
            $user = $stmt->fetch();

            if (!is_array($user)) {
                $message = 'Reset link is invalid or expired.';
            } else {
                $updateStmt = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash,
                         reset_token_hash = NULL,
                         reset_token_expires_at = NULL
                     WHERE id = :id'
                );
                $updateStmt->execute([
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]);

                header('Location: index.php?page=login&reset=1');
                exit;
            }
        }
    }
}

portalRenderAuthStart(
    'Reset Password - School Records Database',
    'Password Reset',
    'Choose a new password',
    'Use the reset link from your email to set a new password.'
);
?>
        <form class="auth-form" method="post" action="index.php?page=reset_password">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="field">
            <label for="reset-password">New Password</label>
            <input id="reset-password" name="password" type="password" placeholder="At least 6 characters" required />
          </div>
          <div class="field">
            <label for="reset-confirm-password">Confirm Password</label>
            <input id="reset-confirm-password" name="confirm_password" type="password" placeholder="Repeat new password" required />
          </div>
          <div class="auth-actions">
            <button type="submit" <?php echo $token === '' ? 'disabled' : ''; ?>>Update Password</button>
            <a class="btn btn-secondary" href="index.php?page=login">Back to sign in</a>
          </div>
          <div class="message<?php echo $message !== '' ? ' error' : ''; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </form>
<?php
portalRenderAuthAside(
    'Secure reset',
    'After the password is changed, the reset link is cleared automatically.',
    [
        ['title' => 'Single use', 'copy' => 'A completed reset invalidates the token.'],
        ['title' => 'Same account', 'copy' => 'Only the password changes on this screen.'],
        ['title' => 'Verification kept', 'copy' => 'Account approval status is not changed by password reset.'],
    ]
);
portalRenderAuthEnd();
