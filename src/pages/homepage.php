<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$email = (string) ($_SESSION['email'] ?? '');
$role = (string) ($_SESSION['role'] ?? 'student');

portalRenderStart(
    'School Records Database Home',
    'home',
    $email,
    $role,
    'Clean navigation for every academic workflow',
    'Open each feature from its own focused page. No more crowded mixed-role screens.'
);

$cards = [
    ['roles' => ['student'], 'href' => 'index.php?page=student_calculator', 'title' => 'Grade Calculator', 'copy' => 'Simulate grades and watch the GWA update instantly.'],
    ['roles' => ['student'], 'href' => 'index.php?page=student_submit', 'title' => 'Submit Actual Grades', 'copy' => 'Load assigned subjects and send grades to teacher review.'],
    ['roles' => ['student'], 'href' => 'index.php?page=student_history', 'title' => 'Grade History', 'copy' => 'Check pending, approved, and rejected grade submissions.'],
    ['roles' => ['student'], 'href' => 'index.php?page=student_prospectus', 'title' => 'Prospectus', 'copy' => 'View the curriculum roadmap with approved grades marked.'],
    ['roles' => ['teacher', 'admin'], 'href' => 'index.php?page=teacher_dashboard', 'title' => 'Teacher Dashboard', 'copy' => 'See pending, approved, and rejected submission totals.'],
    ['roles' => ['teacher', 'admin'], 'href' => 'index.php?page=teacher_queue', 'title' => 'Review Queue', 'copy' => 'Approve or reject submitted grades one by one.'],
    ['roles' => ['teacher', 'admin'], 'href' => 'index.php?page=teacher_history', 'title' => 'Submission History', 'copy' => 'Review current and previous student grade submissions.'],
    ['roles' => ['teacher', 'admin'], 'href' => 'index.php?page=teacher_placement', 'title' => 'Student Placement', 'copy' => 'Move students across program, year, and semester assignments.'],
    ['roles' => ['admin'], 'href' => 'index.php?page=admin_courses', 'title' => 'Programs and Courses', 'copy' => 'Manage academic programs, course inventory, and term labels.'],
    ['roles' => ['admin'], 'href' => 'index.php?page=admin_users', 'title' => 'Users and Roles', 'copy' => 'Update roles and student profiles from one place.'],
];
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Feature Pages</h2>
          <p class="section-copy">Choose a page designed for one task at a time.</p>
        </div>
      </div>
      <div class="feature-grid">
        <?php foreach ($cards as $card): ?>
          <?php if (in_array($role, $card['roles'], true)): ?>
            <a href="<?php echo htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <h3><?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars($card['copy'], ENT_QUOTES, 'UTF-8'); ?></p>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </section>
<?php portalRenderEnd(); ?>
