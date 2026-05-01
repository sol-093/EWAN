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
    'Submit Actual Grades',
    'student-submit',
    $email,
    'student',
    'Submit actual grades',
    'Your assigned program and current term are detected automatically from the student profile.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Actual Grades <span class="module-tag">Send to Teacher</span></h2>
          <p class="section-copy">Load your assigned subjects and submit them for review.</p>
        </div>
      </div>
      <div class="row">
        <div id="student-assignment" style="flex:1 1 420px;"></div>
        <button onclick="loadStudentCourses()">Load Courses</button>
      </div>
      <div id="student-courses"></div>
      <div class="row" style="margin-top:1rem;">
        <button onclick="submitStudentGrades()">Submit for Teacher Review</button>
      </div>
      <div id="student-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const allowedGrades = ['1', '1.25', '1.5', '1.75', '2', '2.25', '2.5', '2.75', '3', '5'];
    const yearLevelLabels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };
    const state = { profile: null };

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
      const el = document.getElementById('student-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    function renderAssignment() {
      const el = document.getElementById('student-assignment');
      const profile = state.profile;
      if (!profile || !profile.full_name || !profile.program_name || !profile.current_year_level || !profile.current_semester) {
        el.innerHTML = '<p class="small error">Your student profile is incomplete. Ask an admin or teacher to update it.</p>';
        return;
      }
      el.innerHTML = '<div class="table-wrap"><table><tbody><tr>' +
        '<td><strong>' + escapeHtml(profile.full_name) + '</strong></td>' +
        '<td>' + escapeHtml(profile.program_name) + '</td>' +
        '<td>' + escapeHtml(yearLevelLabels[profile.current_year_level] || String(profile.current_year_level)) + '</td>' +
        '<td>' + escapeHtml(profile.current_semester) + '</td>' +
      '</tr></tbody></table></div>';
    }

    function renderCourseTable(courses) {
      return '<div class="table-responsive"><table><thead><tr><th>Code</th><th>Subject Title</th><th style="text-align:center;">Units</th><th>Grade</th></tr></thead><tbody>' +
        courses.map(course =>
          '<tr>' +
            '<td>' + escapeHtml(course.code) + '</td>' +
            '<td>' + escapeHtml(course.title) + '</td>' +
            '<td style="text-align:center;">' + escapeHtml(course.units) + '</td>' +
            '<td><select data-course-id="' + course.id + '" class="grade-input">' +
              allowedGrades.map(grade => '<option value="' + grade + '">' + grade + '</option>').join('') +
            '</select></td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
    }

    async function loadStudentCourses() {
      const profile = state.profile;
      if (!profile || !profile.program_id || !profile.current_year_level || !profile.current_semester || !profile.full_name) {
        setMessage('Your student profile is incomplete. Ask an admin or teacher to update it.');
        return;
      }
      const semester = (yearLevelLabels[profile.current_year_level] || String(profile.current_year_level)) + ' - ' + profile.current_semester;
      try {
        const response = await api('get_courses', 'GET', { program_id: profile.program_id, semester });
        const courses = response.courses || [];
        document.getElementById('student-courses').innerHTML = courses.length
          ? renderCourseTable(courses)
          : '<p class="small">No curriculum data for this term.</p>';
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function submitStudentGrades() {
      const inputs = Array.from(document.querySelectorAll('.grade-input'));
      if (!inputs.length) {
        setMessage('Load your assigned courses first.');
        return;
      }
      const grades = inputs.map(input => ({
        course_id: Number(input.getAttribute('data-course-id')),
        grade: Number(input.value)
      }));

      try {
        await api('submit_grades', 'POST', { grades });
        document.getElementById('student-courses').innerHTML = '';
        setMessage('Grades submitted for teacher review.', true);
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.profile = bootstrap.user && bootstrap.user.profile ? bootstrap.user.profile : null;
      renderAssignment();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
