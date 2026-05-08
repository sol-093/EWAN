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
    'Account Verification',
    'teacher-placement',
    $email,
    $role,
    'Account verification',
    'Review student registrations and verify access without changing student-entered profile data.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Student Accounts</h2>
          <p class="section-copy">Students enter their own profile during registration. Teachers and admins verify whether the account may sign in.</p>
        </div>
      </div>
      <div id="teacher-students"></div>
      <div id="teacher-students-pagination" class="row" style="margin-top:0.85rem;"></div>
      <div id="teacher-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { students: [], page: 1, perPage: 10 };

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
      const normalized = String(status || 'pending').toLowerCase();
      let className = 'status-badge status-pending';
      if (normalized === 'verified') {
        className = 'status-badge status-approved';
      } else if (normalized === 'rejected') {
        className = 'status-badge status-rejected';
      }
      return '<span class="' + className + '">' + escapeHtml(normalized) + '</span>';
    }

    function renderStudents() {
      const container = document.getElementById('teacher-students');
      if (!state.students.length) {
        container.innerHTML = '<p class="small">No student profiles found.</p>';
        document.getElementById('teacher-students-pagination').innerHTML = '';
        return;
      }
      const totalPages = Math.max(1, Math.ceil(state.students.length / state.perPage));
      state.page = Math.min(Math.max(1, state.page), totalPages);
      const pageStudents = state.students.slice((state.page - 1) * state.perPage, state.page * state.perPage);
      container.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Email</th><th>Status</th><th>Name</th><th>Program</th><th>Year / Semester</th><th>Action</th></tr></thead><tbody>' +
        pageStudents.map(student =>
          '<tr>' +
            '<td>' + escapeHtml(student.email) + '</td>' +
            '<td>' + renderStatusBadge(student.account_status) + '</td>' +
            '<td>' + escapeHtml(student.full_name || 'No name') + '</td>' +
            '<td>' + escapeHtml(student.program_name || 'No program') + '</td>' +
            '<td>Year ' + escapeHtml(student.current_year_level || '') + '<br><span class="small">' + escapeHtml(student.current_semester || '') + '</span></td>' +
            '<td><div class="table-actions"><button class="compact" onclick="verifyAccount(' + student.id + ', \'verified\')">Verify</button><button class="compact danger" onclick="verifyAccount(' + student.id + ', \'rejected\')">Reject</button></div></td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
      const pager = document.getElementById('teacher-students-pagination');
      pager.innerHTML = state.students.length <= state.perPage ? '' :
        '<button class="compact btn-secondary" onclick="changePage(-1)" ' + (state.page <= 1 ? 'disabled' : '') + '>Previous</button>' +
        '<span class="small">Page ' + state.page + ' of ' + totalPages + '</span>' +
        '<button class="compact btn-secondary" onclick="changePage(1)" ' + (state.page >= totalPages ? 'disabled' : '') + '>Next</button>';
    }

    function changePage(delta) {
      state.page += delta;
      renderStudents();
    }

    async function verifyAccount(userId, status) {
      try {
        await api('verify_account', 'POST', { user_id: userId, status });
        setMessage('Account verification updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const students = await api('list_student_profiles');
      state.students = students.students || [];
      renderStudents();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
