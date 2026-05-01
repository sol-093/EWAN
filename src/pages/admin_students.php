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
    'Review student records separately so profile setup, programs, and current terms are easier to manage.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Student Directory</h2>
          <p class="section-copy">Manage student role assignments and update academic profiles without mixing them with staff accounts.</p>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>Email</th><th>Role</th><th>Student Profile</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="students-table"></tbody>
        </table>
      </div>
      <div id="admin-students-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { users: [], programs: [] };
    const yearLevels = [1, 2, 3, 4];
    const semesterOptions = ['1st Semester', '2nd Semester'];

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

    function renderProgramOptions(selectedProgramId) {
      return '<option value="">-- Select Program --</option>' + state.programs.map(program =>
        '<option value="' + program.id + '"' + (Number(program.id) === Number(selectedProgramId) ? ' selected' : '') + '>' + escapeHtml(program.name) + '</option>'
      ).join('');
    }

    function renderStudents() {
      const tbody = document.getElementById('students-table');
      if (!state.users.length) {
        tbody.innerHTML = '<tr><td colspan="5">No student accounts found.</td></tr>';
        return;
      }

      tbody.innerHTML = state.users.map(user => {
        const roleHtml = '<select id="role-user-' + user.id + '">' +
          '<option value="student" selected>STUDENT</option>' +
          '<option value="teacher">TEACHER</option>' +
          '<option value="admin">ADMIN</option>' +
        '</select>';

        const profileHtml = '<div class="row">' +
          '<input id="student-name-' + user.id + '" type="text" placeholder="Full name" value="' + escapeHtml(user.full_name || '') + '" />' +
          '<select id="student-program-' + user.id + '">' + renderProgramOptions(user.program_id) + '</select>' +
          '<select id="student-year-' + user.id + '">' + yearLevels.map(level => '<option value="' + level + '"' + (Number(user.current_year_level) === level ? ' selected' : '') + '>' + level + '</option>').join('') + '</select>' +
          '<select id="student-semester-' + user.id + '">' + semesterOptions.map(semester => '<option value="' + semester + '"' + (user.current_semester === semester ? ' selected' : '') + '>' + escapeHtml(semester) + '</option>').join('') + '</select>' +
        '</div>';

        const actionHtml =
          '<div class="table-actions">' +
            '<button class="compact" onclick="updateUserRole(' + user.id + ')">Save Role</button>' +
            '<button class="compact" onclick="updateStudentProfile(' + user.id + ')">Save Profile</button>' +
          '</div>';

        return '<tr><td>' + escapeHtml(user.email) + '</td><td>' + roleHtml + '</td><td>' + profileHtml + '</td><td>' + escapeHtml(user.created_at) + '</td><td>' + actionHtml + '</td></tr>';
      }).join('');
    }

    async function updateUserRole(userId) {
      try {
        await api('update_user_role', 'POST', { user_id: userId, role: document.getElementById('role-user-' + userId).value });
        setMessage('Student role updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function updateStudentProfile(userId) {
      try {
        await api('update_student_profile', 'POST', {
          user_id: userId,
          full_name: document.getElementById('student-name-' + userId).value.trim(),
          program_id: Number(document.getElementById('student-program-' + userId).value),
          current_year_level: Number(document.getElementById('student-year-' + userId).value),
          current_semester: document.getElementById('student-semester-' + userId).value
        });
        setMessage('Student profile updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.programs = bootstrap.programs || [];
      const users = await api('list_users', 'GET', { role: 'student' });
      state.users = users.users || [];
      renderStudents();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
