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
    'Student Placement',
    'teacher-placement',
    $email,
    $role,
    'Student placement',
    'Assign or move students between programs, year levels, and semesters without mixing it into the review queue.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Student Profiles</h2>
          <p class="section-copy">Save placement changes here and let the student submission page detect them automatically.</p>
        </div>
      </div>
      <div id="teacher-students"></div>
      <div id="teacher-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { programs: [], students: [] };
    const yearLevels = [1, 2, 3, 4];
    const semesterOptions = ['1st Semester', '2nd Semester'];

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

    function renderProgramOptions(selectedProgramId) {
      return '<option value="">-- Select Program --</option>' + state.programs.map(program =>
        '<option value="' + program.id + '"' + (Number(program.id) === Number(selectedProgramId) ? ' selected' : '') + '>' + escapeHtml(program.name) + '</option>'
      ).join('');
    }

    function renderStudents() {
      const container = document.getElementById('teacher-students');
      if (!state.students.length) {
        container.innerHTML = '<p class="small">No student profiles found.</p>';
        return;
      }
      container.innerHTML = '<div class="table-responsive"><table><thead><tr><th>Email</th><th>Name</th><th>Program</th><th>Year</th><th>Semester</th><th>Action</th></tr></thead><tbody>' +
        state.students.map(student =>
          '<tr>' +
            '<td>' + escapeHtml(student.email) + '</td>' +
            '<td><input id="student-name-' + student.id + '" type="text" value="' + escapeHtml(student.full_name || '') + '" placeholder="Full name" /></td>' +
            '<td><select id="student-program-' + student.id + '">' + renderProgramOptions(student.program_id) + '</select></td>' +
            '<td><select id="student-year-' + student.id + '">' + yearLevels.map(level => '<option value="' + level + '"' + (Number(student.current_year_level) === level ? ' selected' : '') + '>' + level + '</option>').join('') + '</select></td>' +
            '<td><select id="student-semester-' + student.id + '">' + semesterOptions.map(semester => '<option value="' + semester + '"' + (student.current_semester === semester ? ' selected' : '') + '>' + escapeHtml(semester) + '</option>').join('') + '</select></td>' +
            '<td><button onclick="updateStudentProfile(' + student.id + ')">Save</button></td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
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
        setMessage('Student placement updated.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.programs = bootstrap.programs || [];
      const students = await api('list_student_profiles');
      state.students = students.students || [];
      renderStudents();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
