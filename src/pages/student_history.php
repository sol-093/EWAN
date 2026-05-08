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
      <div id="history-pagination" class="row" style="margin-top:0.85rem;"></div>
      <div id="history-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { items: [], page: 1, perPage: 10 };

    async function api(action, method = 'GET', payload = null) {
      const options = { method, headers: {} };
      if (method !== 'GET' && payload) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = csrfToken;
        options.body = JSON.stringify({ action, _csrf: csrfToken, ...payload });
      }
      const response = await fetch('index.php?page=api&action=' + encodeURIComponent(action), options);
      const raw = await response.text();
      const data = JSON.parse(raw);
      if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Request failed');
      }
      return data;
    }

    function setMessage(text, ok = false) {
      const el = document.getElementById('history-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
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

    function paginate(items) {
      const totalPages = Math.max(1, Math.ceil(items.length / state.perPage));
      state.page = Math.min(Math.max(1, state.page), totalPages);
      const start = (state.page - 1) * state.perPage;
      return { totalPages, pageItems: items.slice(start, start + state.perPage) };
    }

    function renderPagination(totalPages, totalItems) {
      const el = document.getElementById('history-pagination');
      if (totalItems <= state.perPage) {
        el.innerHTML = '';
        return;
      }
      el.innerHTML =
        '<button class="compact btn-secondary" onclick="changePage(-1)" ' + (state.page <= 1 ? 'disabled' : '') + '>Previous</button>' +
        '<span class="small">Page ' + state.page + ' of ' + totalPages + '</span>' +
        '<button class="compact btn-secondary" onclick="changePage(1)" ' + (state.page >= totalPages ? 'disabled' : '') + '>Next</button>';
    }

    function changePage(delta) {
      state.page += delta;
      renderHistory(state.items);
    }

    function renderAppealCell(item) {
      const status = String(item.status || '').toLowerCase();
      if (item.appeal_message) {
        return '<strong>' + escapeHtml(item.appeal_status || 'pending') + '</strong><br><span class="small">' + escapeHtml(item.appeal_message) + '</span>';
      }
      if (status === 'rejected' && item.record_type !== 'previous') {
        return '<textarea id="appeal-' + item.id + '" rows="2" placeholder="Reason for appeal"></textarea><button class="compact" style="margin-top:0.4rem;" onclick="sendAppeal(' + item.id + ')">Appeal</button>';
      }
      return '<span class="small">None</span>';
    }

    function renderHistory(items) {
      const container = document.getElementById('grade-history');
      if (!items.length) {
        container.innerHTML = '<p class="small">No grade submissions yet.</p>';
        renderPagination(1, 0);
        return;
      }

      const { totalPages, pageItems } = paginate(items);
      container.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Course</th><th>Semester</th><th>Grade</th><th>Status</th><th>Feedback</th><th>Appeal</th><th>Record</th><th>Last Updated</th></tr></thead><tbody>' +
        pageItems.map(item =>
          '<tr>' +
            '<td><strong>' + escapeHtml(item.course_code) + '</strong><br><span class="small">' + escapeHtml(item.course_title) + '</span></td>' +
            '<td>' + escapeHtml(item.semester) + '</td>' +
            '<td>' + escapeHtml(item.grade) + '</td>' +
            '<td>' + renderStatusBadge(item.status) + '</td>' +
            '<td>' + (item.teacher_feedback ? escapeHtml(item.teacher_feedback) : '<span class="small">None</span>') + '</td>' +
            '<td>' + renderAppealCell(item) + '</td>' +
            '<td>' + escapeHtml(item.record_type === 'previous' ? 'Previous' : 'Current') + '</td>' +
            '<td>' + renderDate(item.updated_at) + '</td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
      renderPagination(totalPages, items.length);
    }

    async function sendAppeal(id) {
      const textarea = document.getElementById('appeal-' + id);
      const appeal_message = textarea ? textarea.value.trim() : '';
      try {
        await api('appeal_submission', 'POST', { id, appeal_message });
        setMessage('Appeal sent for teacher review.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const response = await api('student_grade_history');
      state.items = response.items || [];
      renderHistory(state.items);
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
