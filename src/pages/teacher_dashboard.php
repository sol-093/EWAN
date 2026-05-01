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
$csrfToken = getCsrfToken();

portalRenderStart(
    'Teacher Dashboard',
    'teacher-dashboard',
    $email,
    $role,
    'Teacher dashboard',
    'Watch the overall review pipeline from one focused metrics page.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Submission Metrics</h2>
          <p class="section-copy">Pending, approved, and rejected activity by student and program.</p>
        </div>
      </div>
      <div id="teacher-metrics" class="metric-grid"></div>
      <div class="table-responsive" style="margin-top:1rem;">
        <table>
          <thead>
            <tr><th>Student</th><th>Program</th><th>Total</th><th>Approved</th><th>Pending</th></tr>
          </thead>
          <tbody id="teacher-dashboard-table"></tbody>
        </table>
      </div>
      <div id="teacher-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;

    async function api(action) {
      const response = await fetch('index.php?page=api&action=' + encodeURIComponent(action), { headers: { 'X-CSRF-Token': csrfToken } });
      const raw = await response.text();
      const data = JSON.parse(raw);
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Request failed');
      }
      return data;
    }

    function escapeHtml(value) {
      return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function setMessage(text, ok = false) {
      const el = document.getElementById('teacher-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    async function refresh() {
      const data = await api('teacher_dashboard');
      const counts = data.counts || { pending: 0, approved: 0, rejected: 0 };
      document.getElementById('teacher-metrics').innerHTML =
        '<div class="metric-card"><span class="metric-label">Pending</span><span class="metric-value">' + escapeHtml(counts.pending) + '</span></div>' +
        '<div class="metric-card"><span class="metric-label">Approved</span><span class="metric-value">' + escapeHtml(counts.approved) + '</span></div>' +
        '<div class="metric-card"><span class="metric-label">Rejected</span><span class="metric-value">' + escapeHtml(counts.rejected) + '</span></div>';

      const students = data.students || [];
      document.getElementById('teacher-dashboard-table').innerHTML = students.length
        ? students.map(row =>
            '<tr><td>' + escapeHtml(row.student_name) + '</td><td>' + escapeHtml(row.program_name) + '</td><td>' + escapeHtml(row.total_submissions) + '</td><td>' + escapeHtml(row.approved_submissions) + '</td><td>' + escapeHtml(row.pending_submissions) + '</td></tr>'
          ).join('')
        : '<tr><td colspan="5">No student submissions yet.</td></tr>';
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
