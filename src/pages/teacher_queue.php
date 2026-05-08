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
    'Review Queue',
    'teacher-queue',
    $email,
    $role,
    'Review queue',
    'Approve or reject student submissions from one clear queue page.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Pending and Reviewed Submissions</h2>
          <p class="section-copy">Use the queue below to validate incoming grades.</p>
        </div>
      </div>
      <div id="teacher-queue"></div>
      <div id="teacher-queue-pagination" class="row" style="margin-top:0.85rem;"></div>
      <div id="teacher-message" class="message"></div>
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

    function renderStatusBadge(status) {
      const normalized = String(status || '').toLowerCase();
      let className = 'status-badge status-pending';
      if (normalized === 'approved') {
        className = 'status-badge status-approved';
      } else if (normalized === 'rejected') {
        className = 'status-badge status-rejected';
      }
      return '<span class="' + className + '">' + escapeHtml(status) + '</span>';
    }

    function renderActionCell(item) {
      const normalized = String(item.status || '').toLowerCase();
      const hasPendingAppeal = String(item.appeal_status || '').toLowerCase() === 'pending';
      if (normalized !== 'pending' && !hasPendingAppeal) {
        return '<span class="status-badge status-approved">Grade Submitted</span>';
      }

      return '<div class="table-actions">' +
        '<textarea id="feedback-' + item.id + '" rows="2" placeholder="Feedback for rejection"></textarea>' +
        '<button class="compact" onclick="reviewSubmission(' + item.id + ', \'approved\')">' + (hasPendingAppeal ? 'Approve Appeal' : 'Approve') + '</button>' +
        '<button class="compact danger" onclick="reviewSubmission(' + item.id + ', \'rejected\')">' + (hasPendingAppeal ? 'Reject Appeal' : 'Reject') + '</button>' +
      '</div>';
    }

    function renderAppeal(item) {
      if (!item.appeal_message) {
        return '<span class="small">None</span>';
      }
      return '<strong>' + escapeHtml(item.appeal_status || 'pending') + '</strong><br><span class="small">' + escapeHtml(item.appeal_message) + '</span>';
    }

    function renderFeedback(item) {
      return item.teacher_feedback ? escapeHtml(item.teacher_feedback) : '<span class="small">None</span>';
    }

    function paginate(items) {
      const totalPages = Math.max(1, Math.ceil(items.length / state.perPage));
      state.page = Math.min(Math.max(1, state.page), totalPages);
      const start = (state.page - 1) * state.perPage;
      return { totalPages, pageItems: items.slice(start, start + state.perPage) };
    }

    function renderPagination(totalPages, totalItems) {
      const el = document.getElementById('teacher-queue-pagination');
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
      renderQueue(state.items);
    }

    function renderQueue(items) {
      const queue = document.getElementById('teacher-queue');
      if (!items.length) {
        queue.innerHTML = '<p class="small">No submissions yet.</p>';
        renderPagination(1, 0);
        return;
      }
      const { totalPages, pageItems } = paginate(items);
      queue.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Student</th><th>Program</th><th>Course</th><th>Semester</th><th>Grade</th><th>Status</th><th>Feedback</th><th>Appeal</th><th>Action</th></tr></thead><tbody>' +
        pageItems.map(item =>
          '<tr>' +
            '<td>' + escapeHtml(item.student_name) + '</td>' +
            '<td>' + escapeHtml(item.program_name) + '</td>' +
            '<td>' + escapeHtml(item.course_code) + ' - ' + escapeHtml(item.course_title) + '</td>' +
            '<td>' + escapeHtml(item.semester) + '</td>' +
            '<td>' + escapeHtml(item.grade) + '</td>' +
            '<td>' + renderStatusBadge(item.status) + '</td>' +
            '<td>' + renderFeedback(item) + '</td>' +
            '<td>' + renderAppeal(item) + '</td>' +
            '<td>' + renderActionCell(item) + '</td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
      renderPagination(totalPages, items.length);
    }

    async function reviewSubmission(id, status) {
      try {
        const feedbackInput = document.getElementById('feedback-' + id);
        const teacher_feedback = feedbackInput ? feedbackInput.value.trim() : '';
        await api('review_submission', 'POST', { id, status, teacher_feedback });
        setMessage('Submission updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const queue = await api('teacher_queue');
      state.items = queue.items || [];
      renderQueue(state.items);
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
