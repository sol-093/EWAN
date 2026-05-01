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
    'Staff Accounts',
    'admin-staff',
    $email,
    'admin',
    'Staff accounts',
    'Review teacher and administrator records separately so staff assignments are easier to identify at a glance.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Teacher and Admin Directory</h2>
          <p class="section-copy">Manage non-student accounts in a dedicated page without student profile fields getting in the way.</p>
        </div>
      </div>
      <div class="table-responsive">
        <table class="staff-directory-table">
          <thead><tr><th>Email</th><th>Current Role</th><th>Change Role</th><th>Faculty / Institute</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="staff-table"></tbody>
        </table>
      </div>
      <div id="admin-staff-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { users: [] };

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
      const el = document.getElementById('admin-staff-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    function renderRoleBadge(role) {
      const className = role === 'admin' ? 'status-badge status-approved' : 'status-badge status-pending';
      return '<span class="' + className + '">' + escapeHtml(role) + '</span>';
    }

    function renderStaff() {
      const tbody = document.getElementById('staff-table');
      if (!state.users.length) {
        tbody.innerHTML = '<tr><td colspan="6">No staff accounts found.</td></tr>';
        return;
      }

      const roleOptions = ['teacher', 'admin', 'student'];
      tbody.innerHTML = state.users.map(user => {
        const roleHtml = '<select id="role-user-' + user.id + '">' + roleOptions.map(role =>
          '<option value="' + role + '"' + (user.role === role ? ' selected' : '') + '>' + role.toUpperCase() + '</option>'
        ).join('') + '</select>';

        const affiliationHtml = '<input id="staff-affiliation-' + user.id + '" type="text" placeholder="e.g., College of Computing" value="' + escapeHtml(user.staff_affiliation || '') + '" />';
        const actionsHtml =
          '<div class="table-actions">' +
            '<button class="compact" onclick="updateUserRole(' + user.id + ')">Save Role</button>' +
            '<button class="compact" onclick="updateStaffAffiliation(' + user.id + ')">Save Detail</button>' +
          '</div>';
        return '<tr><td>' + escapeHtml(user.email) + '</td><td>' + renderRoleBadge(user.role) + '</td><td>' + roleHtml + '</td><td>' + affiliationHtml + '</td><td>' + escapeHtml(user.created_at) + '</td><td>' + actionsHtml + '</td></tr>';
      }).join('');
    }

    async function updateUserRole(userId) {
      try {
        await api('update_user_role', 'POST', { user_id: userId, role: document.getElementById('role-user-' + userId).value });
        setMessage('Staff role updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function updateStaffAffiliation(userId) {
      try {
        await api('update_staff_affiliation', 'POST', {
          user_id: userId,
          staff_affiliation: document.getElementById('staff-affiliation-' + userId).value.trim()
        });
        setMessage('Faculty or institute updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const users = await api('list_users', 'GET', { role: 'staff' });
      state.users = users.users || [];
      renderStaff();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
