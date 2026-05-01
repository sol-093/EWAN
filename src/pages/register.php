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
$csrfToken = getCsrfToken();
$email = '';
$agreedToTerms = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $agreedToTerms = isset($_POST['agreement']);
    $role = 'student';

    if ($message === '' && ($email === '' || $password === '')) {
        $message = 'All fields are required.';
    } elseif ($message === '' && strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($message === '' && !$agreedToTerms) {
        $message = 'You must agree to the registration terms before creating an account.';
    } elseif ($message === '') {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $message = 'This email is already registered.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)');
            $insertStmt->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
            ]);

            header('Location: index.php?page=login&registered=1');
            exit;
        }
    }
}

portalRenderAuthStart(
    'Register - School Records Database',
    'School Records Database',
    'Create your student account',
    'Start with a student profile, then continue into the same academic system used across review and administration.'
);
?>
        <form class="auth-form" method="post" action="index.php?page=register">
          <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />

          <div class="field">
            <label for="register-email">Email</label>
            <input id="register-email" name="email" type="email" placeholder="you@school.edu" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="field">
            <label for="register-password">Password</label>
            <div class="password-field">
              <input id="register-password" name="password" type="password" placeholder="At least 6 characters" required />
              <button class="password-toggle" type="button" aria-label="Show password" aria-controls="register-password" onclick="togglePasswordVisibility('register-password', this)">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="agreement-field">
            <input id="register-agreement" name="agreement" type="checkbox" value="1" <?php echo $agreedToTerms ? 'checked' : ''; ?> required />
            <label for="register-agreement">I agree to the <button class="inline-link" type="button" onclick="openTermsModal()">Terms and Conditions</button>.</label>
          </div>

          <div class="auth-actions">
            <button type="submit">Create Account</button>
            <a class="btn btn-secondary" href="index.php?page=login">Back to sign in</a>
          </div>

          <div class="message<?php echo $message !== '' ? ' error' : ''; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </form>
        <p class="auth-footnote">New registrations are created as student accounts and can be updated later by administrators.</p>
        <div class="modal-backdrop" id="terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">
          <div class="modal">
            <div class="modal-head">
              <div>
                <p class="eyebrow">Account Agreement</p>
                <h2 id="terms-title" style="margin-top:0.35rem;">Terms and Conditions</h2>
              </div>
              <button class="compact btn-secondary" type="button" onclick="closeTermsModal()">Close</button>
            </div>
            <div class="modal-content">
              <p>By creating an account, you agree to use the School Records Database for legitimate academic purposes only.</p>
              <ul>
                <li>You must provide accurate account and student profile information.</li>
                <li>Your account details may be used for registration, academic profile setup, grade submission, teacher review, and administrative management.</li>
                <li>You are responsible for keeping your login credentials private.</li>
                <li>Submitted grades and academic records may be reviewed by authorized teachers and administrators.</li>
                <li>Misuse of the portal, false information, or unauthorized access may result in account restriction.</li>
              </ul>
              <p>Continue only if you understand and accept these terms.</p>
            </div>
          </div>
        </div>
        <script>
          const termsModal = document.getElementById('terms-modal');

          function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
          }

          function openTermsModal() {
            termsModal.classList.add('open');
          }

          function closeTermsModal() {
            termsModal.classList.remove('open');
          }

          termsModal.addEventListener('click', event => {
            if (event.target === termsModal) {
              closeTermsModal();
            }
          });

          document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
              closeTermsModal();
            }
          });
        </script>
<?php
portalRenderAuthAside(
    'Why this flow feels cleaner',
    'The same design primitives now guide first-time users into the product without switching visual language halfway through.',
    [
        ['title' => 'Calmer surfaces', 'copy' => 'Fewer decorative effects keep attention on the form and next action.'],
        ['title' => 'Stronger readability', 'copy' => 'Spacing, labels, and button placement reinforce the order of steps.'],
        ['title' => 'Mobile friendly', 'copy' => 'The auth layout collapses into one clear column while keeping controls easy to tap.'],
    ]
);
portalRenderAuthEnd();
