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
$csrfToken = getCsrfToken();

portalRenderStart(
    'Student Accounts',
    'admin-students',
    $email,
    'admin',
    'Student accounts',
    'Verify student accounts after students submit their own registration details.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Student Directory</h2>
          <p class="section-copy">Students enter their profile during registration; administrators verify access here.</p>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>Email</th><th>Status</th><th>Student Profile</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="students-table"></tbody>
        </table>
      </div>
      <div id="students-pagination" class="row" style="margin-top:0.85rem;"></div>
      <div id="admin-students-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { users: [], page: 1, perPage: 10 };

    async function api(action, method = 'GET', payload = null) {
      let url = 'index.php?page=api&action=' + encodeURIComponent(action);
      const options = { method, headers: {} };
      if (method === 'GET' && payload) {
        for (const key in payload) {
          url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
        }
      } else if (method !== 'GET' && payload) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = csrfToken;
        options.body = JSON.stringify({ action, _csrf: csrfToken, ...payload });
      }
      const response = await fetch(url, options);
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
      const el = document.getElementById('admin-students-message');
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
      const tbody = document.getElementById('students-table');
      if (!state.users.length) {
        tbody.innerHTML = '<tr><td colspan="5">No student accounts found.</td></tr>';
        document.getElementById('students-pagination').innerHTML = '';
        return;
      }

      const totalPages = Math.max(1, Math.ceil(state.users.length / state.perPage));
      state.page = Math.min(Math.max(1, state.page), totalPages);
      const pageUsers = state.users.slice((state.page - 1) * state.perPage, state.page * state.perPage);

      tbody.innerHTML = pageUsers.map(user => {
        const profileHtml = '<strong>' + escapeHtml(user.full_name || 'No name') + '</strong><br>' +
          '<span class="small">' + escapeHtml(user.program_name || 'No program') + ' | Year ' + escapeHtml(user.current_year_level || '') + ' | ' + escapeHtml(user.current_semester || '') + '</span>';

        const actionHtml =
          '<div class="table-actions">' +
            '<button class="compact" onclick="verifyAccount(' + user.id + ', \'verified\')">Verify</button>' +
            '<button class="compact danger" onclick="verifyAccount(' + user.id + ', \'rejected\')">Reject</button>' +
          '</div>';

        return '<tr><td>' + escapeHtml(user.email) + '</td><td>' + renderStatusBadge(user.account_status) + '</td><td>' + profileHtml + '</td><td>' + escapeHtml(user.created_at) + '</td><td>' + actionHtml + '</td></tr>';
      }).join('');

      const pager = document.getElementById('students-pagination');
      pager.innerHTML = state.users.length <= state.perPage ? '' :
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
      const users = await api('list_users', 'GET', { role: 'student' });
      state.users = users.users || [];
      renderStudents();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
