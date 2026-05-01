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
    'Grade Calculator',
    'student-calculator',
    $email,
    'student',
    'Grade calculator',
    'Simulate grades on a dedicated page with a clean table and live GWA summary.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Calculator <span class="module-tag">No Submission</span></h2>
          <p class="section-copy">Choose a program and term, then test possible grades.</p>
        </div>
      </div>
      <div class="row">
        <select id="calc-program"></select>
        <select id="calc-semester">
          <option>1st Year - 1st Semester</option>
          <option>1st Year - 2nd Semester</option>
          <option>2nd Year - 1st Semester</option>
          <option>2nd Year - 2nd Semester</option>
          <option>3rd Year - 1st Semester</option>
          <option>3rd Year - 2nd Semester</option>
          <option>4th Year - 1st Semester</option>
          <option>4th Year - 2nd Semester</option>
        </select>
        <button onclick="loadCalculatorCourses()">Load Courses</button>
      </div>
      <div id="calculator-courses"></div>
      <div id="calc-message" class="message"></div>
      <div class="metric-grid" id="calc-metrics" style="display:none; margin-top:1rem;">
        <div class="metric-card"><span class="metric-label">Simulated GWA</span><span class="metric-value" id="calc-gwa">0.00</span></div>
        <div class="metric-card"><span class="metric-label">Total Units</span><span class="metric-value" id="calc-units">0</span></div>
        <div class="metric-card"><span class="metric-label">Courses</span><span class="metric-value" id="calc-count">0</span></div>
      </div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const allowedGrades = ['1', '1.25', '1.5', '1.75', '2', '2.25', '2.5', '2.75', '3', '5'];
    const state = { programs: [], courses: [] };

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
      const el = document.getElementById('calc-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    function renderPrograms() {
      const calc = document.getElementById('calc-program');
      if (!state.programs.length) {
        calc.innerHTML = '<option value="">No programs available</option>';
        return;
      }
      calc.innerHTML = '<option value="" disabled selected>-- Select Program --</option>' +
        state.programs.map(program => '<option value="' + program.id + '">' + escapeHtml(program.name) + '</option>').join('');
    }

    function renderGradeEntryTable(courses) {
      return '<div class="table-responsive"><table><thead><tr><th>Code</th><th>Subject Title</th><th>Units</th><th>Grade</th></tr></thead><tbody>' +
        courses.map(course =>
          '<tr>' +
            '<td>' + escapeHtml(course.code) + '</td>' +
            '<td>' + escapeHtml(course.title) + '</td>' +
            '<td>' + escapeHtml(course.units) + ' units</td>' +
            '<td><select data-calc-course-id="' + course.id + '" class="calc-grade-input" onchange="updateCalculator()">' +
              allowedGrades.map(grade => '<option value="' + grade + '">' + grade + '</option>').join('') +
            '</select></td>' +
          '</tr>'
        ).join('') +
      '</tbody></table></div>';
    }

    async function loadCalculatorCourses() {
      const programId = Number(document.getElementById('calc-program').value);
      const semester = document.getElementById('calc-semester').value;
      const container = document.getElementById('calculator-courses');
      setMessage('');
      if (!programId) {
        setMessage('Please select a program first.');
        return;
      }

      try {
        const response = await api('get_courses', 'GET', { program_id: programId, semester });
        const courses = response.courses || [];
        if (!courses.length) {
          container.innerHTML = '<p class="small">No courses found for this term.</p>';
          document.getElementById('calc-metrics').style.display = 'none';
          return;
        }
        container.innerHTML = renderGradeEntryTable(courses);
        document.getElementById('calc-metrics').style.display = 'grid';
        updateCalculator();
      } catch (error) {
        setMessage(error.message);
      }
    }

    function updateCalculator() {
      const inputs = document.querySelectorAll('.calc-grade-input');
      let totalWeightedGrade = 0;
      let totalUnits = 0;
      let courseCount = 0;

      inputs.forEach(input => {
        const courseId = Number(input.getAttribute('data-calc-course-id'));
        const course = state.courses.find(item => Number(item.id) === courseId);
        if (!course) {
          return;
        }
        const units = Number(course.units);
        totalWeightedGrade += Number(input.value) * units;
        totalUnits += units;
        courseCount++;
      });

      document.getElementById('calc-gwa').textContent = totalUnits > 0 ? (totalWeightedGrade / totalUnits).toFixed(2) : '0.00';
      document.getElementById('calc-units').textContent = String(totalUnits);
      document.getElementById('calc-count').textContent = String(courseCount);
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.programs = bootstrap.programs || [];
      state.courses = bootstrap.courses || [];
      renderPrograms();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
