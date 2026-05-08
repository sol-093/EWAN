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
$fullName = '';
$programId = 0;
$currentYearLevel = 1;
$currentSemester = '1st Semester';
$agreedToTerms = false;
$programs = $pdo->query('SELECT id, name FROM programs ORDER BY name ASC')->fetchAll();
$allowedYearLevels = [1, 2, 3, 4];
$allowedSemesters = ['1st Semester', '2nd Semester'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['_csrf'] ?? null)) {
        $message = 'Security token mismatch. Please refresh and try again.';
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $programId = (int) ($_POST['program_id'] ?? 0);
    $currentYearLevel = (int) ($_POST['current_year_level'] ?? 0);
    $currentSemester = trim((string) ($_POST['current_semester'] ?? ''));
    $password = $_POST['password'] ?? '';
    $agreedToTerms = isset($_POST['agreement']);
    $role = 'student';

    if ($message === '' && ($email === '' || $password === '' || $fullName === '' || $programId <= 0)) {
        $message = 'All fields are required.';
    } elseif ($message === '' && !in_array($currentYearLevel, $allowedYearLevels, true)) {
        $message = 'Please select a valid year level.';
    } elseif ($message === '' && !in_array($currentSemester, $allowedSemesters, true)) {
        $message = 'Please select a valid semester.';
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
            $programStmt = $pdo->prepare('SELECT id FROM programs WHERE id = :id LIMIT 1');
            $programStmt->execute(['id' => $programId]);
            if (!$programStmt->fetchColumn()) {
                $message = 'Selected program was not found.';
            }
        }

        if ($message === '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare(
                'INSERT INTO users (
                    email, password_hash, role, full_name, program_id, current_year_level,
                    current_semester, account_status
                 )
                 VALUES (
                    :email, :password_hash, :role, :full_name, :program_id, :current_year_level,
                    :current_semester, "pending"
                 )'
            );
            $insertStmt->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'full_name' => $fullName,
                'program_id' => $programId,
                'current_year_level' => $currentYearLevel,
                'current_semester' => $currentSemester,
            ]);

            setFlashToast('Registration complete. You can sign in now.', 'success');
            header('Location: index.php?page=login');
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
            <label for="register-full-name">Full Name</label>
            <input id="register-full-name" name="full_name" type="text" placeholder="Student full name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" required />
          </div>

          <div class="field-grid">
            <div class="field">
              <label for="register-program">Program</label>
              <select id="register-program" name="program_id" required>
                <option value="">-- Select Program --</option>
                <?php foreach ($programs as $program): ?>
                  <option value="<?php echo (int) $program['id']; ?>" <?php echo (int) $program['id'] === $programId ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $program['name'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label for="register-year">Year Level</label>
              <select id="register-year" name="current_year_level" required>
                <?php foreach ($allowedYearLevels as $yearLevel): ?>
                  <option value="<?php echo $yearLevel; ?>" <?php echo $currentYearLevel === $yearLevel ? 'selected' : ''; ?>><?php echo $yearLevel; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="register-semester">Current Semester</label>
            <select id="register-semester" name="current_semester" required>
              <?php foreach ($allowedSemesters as $semester): ?>
                <option value="<?php echo htmlspecialchars($semester, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentSemester === $semester ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($semester, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
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
        <p class="auth-footnote">New registrations are reviewed by a teacher or administrator before the account can sign in.</p>
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
                <li>Your account details may be used for registration, account verification, grade submission, teacher review, and administrative management.</li>
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
