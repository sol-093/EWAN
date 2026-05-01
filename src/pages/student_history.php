<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
require_once __DIR__ . '/../core/portal.php';
ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

if ((string) ($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$email = (string) ($_SESSION['email'] ?? '');
$csrfToken = getCsrfToken();

portalRenderStart(
    'Grade Submission History',
    'student-history',
    $email,
    'student',
    'Grade submission history',
    'Review the status of every grade you submitted.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Submission History <span class="module-tag">Student View</span></h2>
          <p class="section-copy">Track which grades are pending, approved, or rejected.</p>
        </div>
      </div>
      <div id="grade-history"></div>
      <div id="history-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;

    async function api(action) {
      const response = await fetch('index.php?page=api&action=' + encodeURIComponent(action));
      const raw = await response.text();
      const data = JSON.parse(raw);
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Request failed');
      }
      return data;
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function renderStatusBadge(status) {
      const normalized = String(status || '').toLowerCase();
      let className = 'status-badge status-pending';
      if (normalized === 'approved') {
        className = 'status-badge status-approved';
      } else if (normalized === 'rejected') {
        className = 'status-badge status-rejected';
      }
      return '<span class="' + className + '">' + escapeHtml(normalized || 'pending') + '</span>';
    }

    function renderDate(value) {
      if (!value) {
        return '';
      }
      const date = new Date(String(value).replace(' ', 'T'));
      if (Number.isNaN(date.getTime())) {
        return escapeHtml(value);
      }
      return escapeHtml(date.toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
      }));
    }

    function renderHistory(items) {
      const container = document.getElementById('grade-history');
      if (!items.length) {
        container.innerHTML = '<p class="small">No grade submissions yet.</p>';
        return;
      }

      container.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Course</th><th>Semester</th><th>Grade</th><th>Status</th><th>Record</th><th>Last Updated</th></tr></thead><tbody>' +
        items.map(item =>
          '<tr>' +
            '<td><strong>' + escapeHtml(item.course_code) + '</strong><br><span class="small">' + escapeHtml(item.course_title) + '</span></td>' +
            '<td>' + escapeHtml(item.semester) + '</td>' +
            '<td>' + escapeHtml(item.grade) + '</td>' +
            '<td>' + renderStatusBadge(item.status) + '</td>' +
            '<td>' + escapeHtml(item.record_type === 'previous' ? 'Previous' : 'Current') + '</td>' +
            '<td>' + renderDate(item.updated_at) + '</td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
    }

    async function refresh() {
      const response = await api('student_grade_history');
      renderHistory(response.items || []);
    }

    refresh().catch(error => {
      const el = document.getElementById('history-message');
      el.textContent = error.message;
      el.className = 'message error';
    });
  </script>
<?php portalRenderEnd(); ?>
