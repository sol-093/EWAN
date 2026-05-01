<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

if ((string) ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$email = (string) ($_SESSION['email'] ?? '');

portalRenderStart(
    'Admin Workspace',
    'admin',
    $email,
    'admin',
    'Admin workspace',
    'Keep system setup and user management separated into focused pages for cleaner operations.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Admin Features</h2>
          <p class="section-copy">Manage the system using dedicated admin pages.</p>
        </div>
      </div>
      <div class="feature-grid">
        <a href="index.php?page=admin_courses">
          <h3>Programs and Courses</h3>
          <p>Create programs, add subjects, and browse the course inventory.</p>
        </a>
        <a href="index.php?page=admin_students">
          <h3>Student Accounts</h3>
          <p>Maintain student roles, names, programs, and current term assignments.</p>
        </a>
        <a href="index.php?page=admin_staff">
          <h3>Staff Accounts</h3>
          <p>Separate teacher and administrator records so staffing changes are easier to review.</p>
        </a>
      </div>
    </section>
<?php portalRenderEnd(); ?>
