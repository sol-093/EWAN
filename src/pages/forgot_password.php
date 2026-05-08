<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
require_once __DIR__ . '/../core/mailer.php';
ensureSessionStarted();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=home');
    exit;
}

require_once __DIR__ . '/../core/db.php';

$csrfToken = getCsrfToken();
$email = '';
$message = '';
$isSuccess = false;

function buildResetUrl(string $token): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/EWAN/index.php'), '?');
    $basePath = str_ends_with($path, '/index.php') ? $path : dirname($path) . '/index.php';
    return $scheme . '://' . $host . $basePath . '?page=reset_password&token=' . urlencode($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        $message = 'Security token mismatch. Please refresh and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($email === '') {
            $message = 'Please enter your email address.';
        } else {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (is_array($user)) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $updateStmt = $pdo->prepare(
                    'UPDATE users
                     SET reset_token_hash = :token_hash,
                         reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                     WHERE id = :id'
                );
                $updateStmt->execute([
                    'token_hash' => $tokenHash,
                    'id' => (int) $user['id'],
                ]);

                $resetUrl = buildResetUrl($token);
                $subject = 'Password reset request';
                $body = "Use this link to reset your School Records Database password:\n\n" . $resetUrl . "\n\nThis link expires in 1 hour.";
                sendPortalEmail((string) $user['email'], $subject, $body);
            }

            $message = 'If that email exists, a password reset link has been sent.';
            $isSuccess = true;
        }
    }
}

portalRenderAuthStart(
    'Forgot Password - School Records Database',
    'Password Reset',
    'Reset your password',
    'Enter your account email and the system will send a reset link.'
);
?>
        <form class="auth-form" method="post" action="index.php?page=forgot_password">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <div class="field">
            <label for="forgot-email">Email</label>
            <input id="forgot-email" name="email" type="email" placeholder="you@school.edu" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>
          <div class="auth-actions">
            <button type="submit">Send Reset Link</button>
            <a class="btn btn-secondary" href="index.php?page=login">Back to sign in</a>
          </div>
          <div class="message<?php echo $message !== '' ? ($isSuccess ? ' success' : ' error') : ''; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </form>
<?php
portalRenderAuthAside(
    'Account recovery',
    'Reset links are single-use and expire after one hour.',
    [
        ['title' => 'Email based', 'copy' => 'The link is sent to the email saved on the account.'],
        ['title' => 'Limited window', 'copy' => 'Expired links are ignored and can be requested again.'],
        ['title' => 'Private reset', 'copy' => 'Only the password changes; roles and verification status stay untouched.'],
    ]
);
portalRenderAuthEnd();
