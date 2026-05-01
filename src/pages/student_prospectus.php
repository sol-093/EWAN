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
    'Prospectus Viewer',
    'student-prospectus',
    $email,
    'student',
    'Prospectus viewer',
    'Browse a program roadmap and compare it against your approved grades.'
);
?>
    <section>
      <div class="section-head">
        <div>
          <h2>Curriculum Roadmap <span class="module-tag">Filtered View</span></h2>
          <p class="section-copy">Choose a degree program and term to inspect the prospectus in a curriculum sheet format.</p>
        </div>
      </div>
      <div class="row prospectus-filters">
        <select id="filter-program" onchange="generateProspectus()"></select>
        <select id="filter-semester" onchange="generateProspectus()">
          <option value="All">All Terms</option>
          <option>1st Year - 1st Semester</option>
          <option>1st Year - 2nd Semester</option>
          <option>2nd Year - 1st Semester</option>
          <option>2nd Year - 2nd Semester</option>
          <option>3rd Year - 1st Semester</option>
          <option>3rd Year - 2nd Semester</option>
          <option>4th Year - 1st Semester</option>
          <option>4th Year - 2nd Semester</option>
        </select>
        <button type="button" onclick="downloadProspectusPdf()">Download PDF</button>
      </div>
      <div id="prospectus-ui-container" class="prospectus-output"></div>
      <div id="prospectus-message" class="message"></div>
    </section>
  <script>
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const state = { programs: [], approved: [], profile: null };
    const allSemesters = [
      '1st Year - 1st Semester', '1st Year - 2nd Semester',
      '2nd Year - 1st Semester', '2nd Year - 2nd Semester',
      '3rd Year - 1st Semester', '3rd Year - 2nd Semester',
      '4th Year - 1st Semester', '4th Year - 2nd Semester'
    ];

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
      const el = document.getElementById('prospectus-message');
      el.textContent = text;
      el.className = 'message ' + (ok ? 'success' : 'error');
    }

    function renderGradeState(grade) {
      if (grade) {
        return escapeHtml(grade);
      }
      return '';
    }

    function selectedProgramName() {
      const select = document.getElementById('filter-program');
      return select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';
    }

    function termLabel(term) {
      return String(term || 'Unassigned Term').toUpperCase();
    }

    function courseHours(course) {
      const units = Number(course.units || 0);
      return {
        creditLec: Number(course.credit_lec ?? units),
        creditLab: Number(course.credit_lab ?? 0),
        contactLec: Number(course.contact_lec ?? units),
        contactLab: Number(course.contact_lab ?? 0)
      };
    }

    function renderProspectusTable(courses, approvedMap) {
      const grouped = {};
      allSemesters.forEach(term => {
        grouped[term] = [];
      });
      courses.forEach(course => {
        const term = course.semester || 'Unassigned Term';
        if (!grouped[term]) {
          grouped[term] = [];
        }
        grouped[term].push(course);
      });

      const orderedTerms = Object.keys(grouped).filter(term => grouped[term].length > 0);
      const rows = orderedTerms.map(term => {
        const termCourses = grouped[term];
        const totals = termCourses.reduce((sum, course) => {
          const hours = courseHours(course);
          sum.creditLec += hours.creditLec;
          sum.creditLab += hours.creditLab;
          sum.contactLec += hours.contactLec;
          sum.contactLab += hours.contactLab;
          return sum;
        }, { creditLec: 0, creditLab: 0, contactLec: 0, contactLab: 0 });
        const courseRows = termCourses.map(course => {
          const grade = approvedMap[course.code] || '';
          const hours = courseHours(course);
          return '<tr>' +
            '<td class="prospectus-center">' + renderGradeState(grade) + '</td>' +
            '<td class="prospectus-code">' + escapeHtml(course.code) + '</td>' +
            '<td class="prospectus-title">' + escapeHtml(course.title) + '</td>' +
            '<td class="prospectus-center">' + escapeHtml(hours.creditLec) + '</td>' +
            '<td class="prospectus-center">' + escapeHtml(hours.creditLab) + '</td>' +
            '<td class="prospectus-center">' + escapeHtml(hours.contactLec) + '</td>' +
            '<td class="prospectus-center">' + escapeHtml(hours.contactLab) + '</td>' +
            '<td>' + escapeHtml(course.prerequisite || '') + '</td>' +
          '</tr>';
        }).join('');

        return '<tr class="prospectus-term-row"><td colspan="8">' + escapeHtml(termLabel(term)) + '</td></tr>' +
          courseRows +
          '<tr class="prospectus-total-row">' +
            '<td colspan="3">TOTAL</td>' +
            '<td>' + escapeHtml(totals.creditLec) + '</td>' +
            '<td>' + escapeHtml(totals.creditLab) + '</td>' +
            '<td>' + escapeHtml(totals.contactLec) + '</td>' +
            '<td>' + escapeHtml(totals.contactLab) + '</td>' +
            '<td></td>' +
          '</tr>';
      }).join('');

      const studentName = state.profile && state.profile.full_name ? state.profile.full_name : '';
      const programName = selectedProgramName();

      return '<div class="table-responsive">' +
        '<div class="prospectus-sheet">' +
          '<div class="prospectus-heading">' +
            '<div class="prospectus-seal">KLD</div>' +
            '<div class="prospectus-brand">' +
              '<h3>Kolehiyo ng Lungsod ng Dasmariñas</h3>' +
              '<p>Brgy. Burol Main, City of Dasmariñas, Cavite, Philippines 4114</p>' +
              '<p>www.kld.edu.ph</p>' +
              '<div class="prospectus-program-title">' + escapeHtml(programName) + '</div>' +
              '<p>Effective Academic Year 2025-2026</p>' +
            '</div>' +
            '<div class="prospectus-seal">CAV</div>' +
          '</div>' +
          '<div class="prospectus-student-line">Name: <span>' + escapeHtml(studentName) + '</span></div>' +
          '<div class="prospectus-rule"></div>' +
        '<table class="prospectus-table">' +
          '<thead>' +
            '<tr>' +
              '<th rowspan="2">Grade</th>' +
              '<th rowspan="2">Course Code</th>' +
              '<th rowspan="2">Course Title</th>' +
              '<th colspan="2">Credit<br>Unit</th>' +
              '<th colspan="2">Contact<br>Hrs</th>' +
              '<th rowspan="2">Pre-Requisite<br>(Co-Requisite)</th>' +
            '</tr>' +
            '<tr>' +
              '<th>Lec</th>' +
              '<th>Lab</th>' +
              '<th>Lec</th>' +
              '<th>Lab</th>' +
            '</tr>' +
          '</thead>' +
          '<tbody>' + rows + '</tbody>' +
        '</table>' +
        '</div>' +
      '</div>';
    }

    function renderPrograms() {
      const select = document.getElementById('filter-program');
      select.innerHTML = '<option value="" disabled>-- Select Program --</option>' +
        state.programs.map(program => '<option value="' + program.id + '">' + escapeHtml(program.name) + '</option>').join('');
      if (state.profile && state.profile.program_id) {
        select.value = String(state.profile.program_id);
      } else if (state.programs.length) {
        select.value = String(state.programs[0].id);
      }
    }

    function downloadProspectusPdf() {
      const sheet = document.querySelector('.prospectus-sheet');
      if (!sheet) {
        setMessage('Generate a prospectus before downloading.');
        return;
      }

      const programName = selectedProgramName() || 'Prospectus';
      const previousTitle = document.title;
      document.title = programName + ' Prospectus';
      document.body.classList.add('single-page-print');
      window.addEventListener('afterprint', () => {
        document.body.classList.remove('single-page-print');
        document.title = previousTitle;
      }, { once: true });
      window.print();
    }

    async function generateProspectus() {
      const programId = document.getElementById('filter-program').value;
      const semester = document.getElementById('filter-semester').value;
      const container = document.getElementById('prospectus-ui-container');
      setMessage('');
      if (!programId) {
        container.innerHTML = '<p class="small">Please select a program.</p>';
        return;
      }

      try {
        let curriculum = [];
        if (semester === 'All') {
          for (const term of allSemesters) {
            const response = await api('get_courses', 'GET', { program_id: programId, semester: term });
            curriculum = curriculum.concat(response.courses || []);
          }
        } else {
          const response = await api('get_courses', 'GET', { program_id: programId, semester });
          curriculum = response.courses || [];
        }

        if (!curriculum.length) {
          container.innerHTML = '<p class="small">No curriculum data found.</p>';
          return;
        }

        const approvedMap = {};
        state.approved.forEach(row => {
          if (row.code) {
            approvedMap[row.code] = row.grade;
          }
        });

        container.innerHTML = renderProspectusTable(curriculum, approvedMap);
      } catch (error) {
        setMessage(error.message);
      }
    }

    async function refresh() {
      const bootstrap = await api('bootstrap');
      state.programs = bootstrap.programs || [];
      state.profile = bootstrap.user && bootstrap.user.profile ? bootstrap.user.profile : null;
      const approved = await api('student_gwa');
      state.approved = approved.rows || [];
      renderPrograms();
      generateProspectus();
    }

    refresh().catch(error => setMessage(error.message));
  </script>
<?php portalRenderEnd(); ?>
