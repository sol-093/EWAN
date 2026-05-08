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
    'Submission History',
    'teacher-history',
    $email,
    $role,
    'Submission history',
    'Review current and previous student grade submissions.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Grade Submission History <span class="module-tag">Staff View</span></h2>
          <p class="section-copy">Track pending, approved, rejected, and previous submissions across students.</p>
        </div>
      </div>
      <div class="history-filters">
        <div class="field">
          <label for="history-student-search">Search student name</label>
          <input id="history-student-search" type="search" placeholder="Type a student name" autocomplete="off" />
        </div>
        <div class="field">
          <label for="history-status-filter">Action made</label>
          <select id="history-status-filter">
            <option value="">All actions</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        <div class="field">
          <label for="history-date-order">Date order</label>
          <select id="history-date-order">
            <option value="desc">Newest first</option>
            <option value="asc">Oldest first</option>
          </select>
        </div>
      </div>
      <div id="grade-history"></div>
      <div id="history-pagination" class="row" style="margin-top:0.85rem;"></div>
      <div id="history-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { items: [], visibleItems: [], page: 1, perPage: 10 };

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
      renderHistory(state.visibleItems);
    }

    function renderHistory(items) {
      const container = document.getElementById('grade-history');
      if (!items.length) {
        const query = getStudentSearchQuery();
        container.innerHTML = '<p class="small">' + (query ? 'No submissions match that student name.' : 'No grade submissions yet.') + '</p>';
        renderPagination(1, 0);
        return;
      }

      const { totalPages, pageItems } = paginate(items);
      container.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Student</th><th>Program</th><th>Course</th><th>Semester</th><th>Grade</th><th>Status</th><th>Feedback</th><th>Appeal</th><th>Record</th><th>Last Updated</th></tr></thead><tbody>' +
        pageItems.map(item =>
          '<tr>' +
            '<td>' + escapeHtml(item.student_name) + '</td>' +
            '<td>' + escapeHtml(item.program_name) + '</td>' +
            '<td><strong>' + escapeHtml(item.course_code) + '</strong><br><span class="small">' + escapeHtml(item.course_title) + '</span></td>' +
            '<td>' + escapeHtml(item.semester) + '</td>' +
            '<td>' + escapeHtml(item.grade) + '</td>' +
            '<td>' + renderStatusBadge(item.status) + '</td>' +
            '<td>' + (item.teacher_feedback ? escapeHtml(item.teacher_feedback) : '<span class="small">None</span>') + '</td>' +
            '<td>' + (item.appeal_message ? '<strong>' + escapeHtml(item.appeal_status || 'pending') + '</strong><br><span class="small">' + escapeHtml(item.appeal_message) + '</span>' : '<span class="small">None</span>') + '</td>' +
            '<td>' + escapeHtml(item.record_type === 'previous' ? 'Previous' : 'Current') + '</td>' +
            '<td>' + renderDate(item.updated_at) + '</td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
      renderPagination(totalPages, items.length);
    }

    function getStudentSearchQuery() {
      const input = document.getElementById('history-student-search');
      return input ? input.value.trim().toLowerCase() : '';
    }

    function getStatusFilter() {
      const select = document.getElementById('history-status-filter');
      return select ? select.value : '';
    }

    function getDateOrder() {
      const select = document.getElementById('history-date-order');
      return select ? select.value : 'desc';
    }

    function getTimeValue(value) {
      if (!value) {
        return 0;
      }
      const date = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(date.getTime()) ? 0 : date.getTime();
    }

    function applyStudentSearch() {
      state.page = 1;
      const query = getStudentSearchQuery();
      const status = getStatusFilter();
      const order = getDateOrder();
      const filtered = state.items
        .filter(item => !query || String(item.student_name || '').toLowerCase().includes(query))
        .filter(item => !status || String(item.status || '').toLowerCase() === status)
        .slice()
        .sort((a, b) => {
          const diff = getTimeValue(a.updated_at) - getTimeValue(b.updated_at);
          return order === 'asc' ? diff : -diff;
        });
      state.visibleItems = filtered;
      renderHistory(state.visibleItems);
    }

    async function refresh() {
      const response = await api('teacher_grade_history');
      state.items = response.items || [];
      applyStudentSearch();
    }

    document.getElementById('history-student-search').addEventListener('input', applyStudentSearch);
    document.getElementById('history-status-filter').addEventListener('change', applyStudentSearch);
    document.getElementById('history-date-order').addEventListener('change', applyStudentSearch);

    refresh().catch(error => {
      const el = document.getElementById('history-message');
      el.textContent = error.message;
      el.className = 'message error';
    });
  </script>
<?php portalRenderEnd(); ?>
