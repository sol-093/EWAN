<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$role = (string) ($_SESSION['role'] ?? '');
if (!in_array($role, ['teacher', 'admin'], true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$email = (string) ($_SESSION['email'] ?? '');

portalRenderStart(
    'Teacher Workspace',
    'teacher',
    $email,
    $role,
    'Teacher workspace',
    'Review, track, and manage student placement using dedicated pages built for staff workflows.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Teacher Features</h2>
          <p class="section-copy">Jump into the staff view you need right now.</p>
        </div>
      </div>
      <div class="feature-grid">
        <a href="index.php?page=teacher_dashboard">
          <h3>Dashboard</h3>
          <p>View submission totals and student approval progress.</p>
        </a>
        <a href="index.php?page=teacher_queue">
          <h3>Review Queue</h3>
          <p>Approve or reject student grade submissions.</p>
        </a>
        <a href="index.php?page=teacher_placement">
          <h3>Student Placement</h3>
          <p>Update assigned program, year level, and semester.</p>
        </a>
      </div>
    </section>
<?php portalRenderEnd(); ?>
