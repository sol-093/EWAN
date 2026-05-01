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
    'Programs and Courses',
    'admin-courses',
    $email,
    'admin',
    'Programs and courses',
    'Create programs, add courses, and review your academic inventory on its own admin page.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Programs and Courses</h2>
          <p class="section-copy">Add new programs and append subjects by semester.</p>
        </div>
      </div>
      <div class="row">
        <input id="program-name" type="text" placeholder="Program name" />
        <button onclick="addProgram()">Add Program</button>
      </div>
      <div class="row" style="margin-top:1rem;">
        <select id="course-program"></select>
        <input id="course-code" type="text" placeholder="Course code" />
        <input id="course-title" type="text" placeholder="Course title" />
        <input id="course-units" type="number" min="1" value="3" />
        <select id="course-semester">
          <option>1st Year - 1st Semester</option>
          <option>1st Year - 2nd Semester</option>
          <option>2nd Year - 1st Semester</option>
          <option>2nd Year - 2nd Semester</option>
          <option>3rd Year - 1st Semester</option>
          <option>3rd Year - 2nd Semester</option>
          <option>4th Year - 1st Semester</option>
          <option>4th Year - 2nd Semester</option>
        </select>
        <button onclick="addCourse()">Add Course</button>
      </div>
      <div id="admin-message" class="message"></div>
    </section>

    <section>
      <div class="section-head">
        <div>
          <h2>Course Inventory</h2>
          <p class="section-copy">Filter by program and semester for a focused inventory view.</p>
        </div>
      </div>
      <div class="row" style="margin-bottom:1rem;">
        <select id="view-filter-program" onchange="renderCoursesTable()"><option value="">All Programs</option></select>
        <select id="view-filter-semester" onchange="renderCoursesTable()">
          <option value="">All Semesters</option>
          <option>1st Year - 1st Semester</option>
          <option>1st Year - 2nd Semester</option>
          <option>2nd Year - 1st Semester</option>
          <option>2nd Year - 2nd Semester</option>
          <option>3rd Year - 1st Semester</option>
          <option>3rd Year - 2nd Semester</option>
          <option>4th Year - 1st Semester</option>
          <option>4th Year - 2nd Semester</option>
        </select>
      </div>
      <div class="table-responsive">
        <table>
          <thead><tr><th>Program</th><th>Code</th><th>Title</th><th>Units</th><th>Semester</th></tr></thead>
          <tbody id="courses-table-body"></tbody>
        </table>
      </div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { programs: [], courses: [] };

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
      const el = document.getElementById('admin-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    function renderPrograms() {
      const options = state.programs.map(program => '<option value="' + program.id + '">' + escapeHtml(program.name) + '</option>').join('');
      document.getElementById('course-program').innerHTML = '<option value="">-- Select Program --</option>' + options;
      document.getElementById('view-filter-program').innerHTML = '<option value="">All Programs</option>' + options;
    }

    function renderCoursesTable() {
      const filterProgram = document.getElementById('view-filter-program').value;
      const filterSemester = document.getElementById('view-filter-semester').value;
      const filtered = state.courses.filter(course =>
        (!filterProgram || Number(course.program_id) === Number(filterProgram)) &&
        (!filterSemester || course.semester === filterSemester)
      );
      document.getElementById('courses-table-body').innerHTML = filtered.length
        ? filtered.map(course =>
            '<tr><td>' + escapeHtml(course.program_name) + '</td><td>' + escapeHtml(course.code) + '</td><td>' + escapeHtml(course.title) + '</td><td>' + escapeHtml(course.units) + '</td><td>' + escapeHtml(course.semester) + '</td></tr>'
          ).join('')
        : '<tr><td colspan="5">No courses found.</td></tr>';
    }

    async function addProgram() {
      try {
        await api('add_program', 'POST', { name: document.getElementById('program-name').value.trim() });
        document.getElementById('program-name').value = '';
        setMessage('Program added.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function addCourse() {
      try {
        await api('add_course', 'POST', {
          program_id: Number(document.getElementById('course-program').value),
          code: document.getElementById('course-code').value.trim(),
          title: document.getElementById('course-title').value.trim(),
          units: Number(document.getElementById('course-units').value),
          semester: document.getElementById('course-semester').value
        });
        document.getElementById('course-code').value = '';
        document.getElementById('course-title').value = '';
        document.getElementById('course-units').value = '3';
        setMessage('Course added.', true);
        await refresh();
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.programs = bootstrap.programs || [];
      state.courses = bootstrap.courses || [];
      renderPrograms();
      renderCoursesTable();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
