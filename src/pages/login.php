<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php?page=home');
    exit;
}

require_once __DIR__ . '/../core/db.php';

$message = '';
$successMessage = isset($_GET['registered']) ? 'Registration complete. Please wait for teacher or admin verification before signing in.' : '';
if (isset($_GET['reset'])) {
    $successMessage = 'Password updated. You can sign in now.';
}
$csrfToken = getCsrfToken();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($message === '') {
        $throttleMessage = checkLoginThrottle($email);
        if ($throttleMessage !== null) {
            $message = $throttleMessage;
        }
    }

    if ($message === '' && ($email === '' || $password === '')) {
        $message = 'Please enter both email and password.';
    } elseif ($message === '') {
        $stmt = $pdo->prepare('SELECT id, email, password_hash, role, account_status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            recordLoginFailure($email);
            $message = 'Invalid email or password.';
        } elseif (($user['account_status'] ?? 'pending') !== 'verified') {
            $message = 'Your account is still waiting for teacher or admin verification.';
        } else {
            session_regenerate_id(true);
            clearLoginFailures($email);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php?page=home');
            exit;
        }
    }
}

portalRenderAuthStart(
    'Login - School Records Database',
    'School Records Database',
    'Sign in to your academic workspace',
    'Use the same portal for student submissions, teacher review, and admin operations.'
);
?>
        <?php if ($successMessage !== ''): ?>
          <div class="status-badge status-approved"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form class="auth-form" method="post" action="index.php?page=login">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />

          <div class="field">
            <label for="login-email">Email</label>
            <input id="login-email" name="email" type="email" placeholder="you@school.edu" autocomplete="username" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="field">
            <label for="login-password">Password</label>
            <div class="password-field">
              <input id="login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required />
              <button class="password-toggle" type="button" aria-label="Show password" aria-controls="login-password" onclick="togglePasswordVisibility('login-password', this)">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="auth-actions">
            <button type="submit">Sign In</button>
            <a class="btn btn-secondary" href="index.php?page=register">Create an account</a>
          </div>
          <p class="small" style="margin-top:0.75rem;"><a class="inline-link" href="index.php?page=forgot_password">Forgot password?</a></p>

          <div class="message<?php echo $message !== '' ? ' error' : ''; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </form>
        <script>
          function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
          }
        </script>
        <p class="auth-footnote">Need access first? Register with a student account and continue into the shared portal.</p>
<?php
portalRenderAuthAside(
    'What you can expect after login',
    'Every screen follows the same spacing, typography, controls, and calm green surface language.',
    [
        ['title' => 'Focused navigation', 'copy' => 'Role-based pages stay organized without blending student, teacher, and admin tasks together.'],
        ['title' => 'Clear form states', 'copy' => 'Inputs, buttons, and focus states are tuned for readability on both desktop and mobile.'],
        ['title' => 'Consistent data views', 'copy' => 'Tables, metrics, and status labels now use one component system across the app.'],
    ]
);
portalRenderAuthEnd();
