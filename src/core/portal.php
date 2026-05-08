<?php
declare(strict_types=1);

function portalNavItems(string $role): array
{
    $items = [
        ['key' => 'home', 'label' => 'Home', 'href' => 'index.php?page=home'],
    ];

    if ($role === 'student') {
        $items[] = ['key' => 'student-calculator', 'label' => 'Calculator', 'href' => 'index.php?page=student_calculator'];
        $items[] = ['key' => 'student-submit', 'label' => 'Actual Grades', 'href' => 'index.php?page=student_submit'];
        $items[] = ['key' => 'student-prospectus', 'label' => 'Prospectus', 'href' => 'index.php?page=student_prospectus'];
        $items[] = ['key' => 'student-history', 'label' => 'Grade History', 'href' => 'index.php?page=student_history'];
    }

    if ($role === 'teacher' || $role === 'admin') {
        if ($role === 'teacher') {
            $items[] = ['key' => 'teacher', 'label' => 'Teacher', 'href' => 'index.php?page=teacher'];
        }
        $items[] = ['key' => 'teacher-dashboard', 'label' => 'Dashboard', 'href' => 'index.php?page=teacher_dashboard'];
        $items[] = ['key' => 'teacher-queue', 'label' => 'Queue', 'href' => 'index.php?page=teacher_queue'];
        $items[] = ['key' => 'teacher-placement', 'label' => 'Accounts', 'href' => 'index.php?page=teacher_placement'];
        $items[] = ['key' => 'teacher-history', 'label' => 'Grade History', 'href' => 'index.php?page=teacher_history'];
    }

    if ($role === 'admin') {
        $items[] = ['key' => 'admin-courses', 'label' => 'Programs', 'href' => 'index.php?page=admin_courses'];
        $items[] = ['key' => 'admin-students', 'label' => 'Students', 'href' => 'index.php?page=admin_students'];
        $items[] = ['key' => 'admin-staff', 'label' => 'Staff', 'href' => 'index.php?page=admin_staff'];
    }

    return $items;
}

function portalYearLevelLabel(int $yearLevel): string
{
    $labels = [
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
    ];

    return $labels[$yearLevel] ?? ($yearLevel . 'th Year');
}

function portalProfileMetaItems(string $email, string $role): array
{
    $items = [
        ['label' => 'Signed In', 'value' => $email],
        ['label' => 'Role', 'value' => strtoupper($role)],
    ];

    try {
        require_once __DIR__ . '/db.php';
        if (!isset($pdo)) {
            return $items;
        }

        $stmt = $pdo->prepare(
            'SELECT u.full_name, u.current_year_level, u.current_semester, u.staff_affiliation, p.name AS program_name
             FROM users u
             LEFT JOIN programs p ON p.id = u.program_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $profile = $stmt->fetch();
        if (!is_array($profile)) {
            return $items;
        }

        $fullName = trim((string) ($profile['full_name'] ?? ''));
        if ($fullName !== '') {
            $items[] = ['label' => 'Name', 'value' => $fullName];
        }

        if ($role === 'student') {
            $programName = trim((string) ($profile['program_name'] ?? ''));
            $items[] = ['label' => 'Program', 'value' => $programName !== '' ? $programName : 'Not assigned'];

            $yearLevel = (int) ($profile['current_year_level'] ?? 0);
            $semester = trim((string) ($profile['current_semester'] ?? ''));
            $levelValue = $yearLevel > 0 ? portalYearLevelLabel($yearLevel) : 'Year not set';
            if ($semester !== '') {
                $levelValue .= ' - ' . $semester;
            }
            $items[] = ['label' => 'Year / Term', 'value' => $yearLevel > 0 || $semester !== '' ? $levelValue : 'Not set'];
        } else {
            $affiliation = trim((string) ($profile['staff_affiliation'] ?? ''));
            $items[] = [
                'label' => 'Faculty / Institute',
                'value' => $affiliation !== '' ? $affiliation : 'Not set yet',
            ];
        }
    } catch (Throwable $e) {
        // Keep portal rendering resilient even if profile lookup fails.
    }

    return $items;
}

function portalRenderHead(string $title, string $bodyClass): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand-950: #073b2d;
      --brand-900: #0b5f47;
      --brand-800: #0F7A5A;
      --brand-700: #128c67;
      --brand-600: #1FA97A;
      --brand-100: #dff5ed;
      --brand-50: #F5FBF8;
      --ink-900: #1A1A1A;
      --ink-700: #2f5b4d;
      --ink-500: #668277;
      --line-200: #d7e7df;
      --line-100: #e8f2ed;
      --surface-strong: #ffffff;
      --surface-soft: #F5FBF8;
      --surface-muted: #edf8f3;
      --danger-600: #b9382a;
      --danger-50: #fdf0ed;
      --success-700: #0F7A5A;
      --success-50: #edf9f0;
      --warning-700: #9a6700;
      --warning-50: #fff6df;
      --radius-xl: 16px;
      --radius-lg: 16px;
      --radius-md: 16px;
      --radius-sm: 12px;
      --shadow-sm: 0 8px 24px rgba(15, 122, 90, 0.07);
      --shadow-md: 0 16px 40px rgba(15, 122, 90, 0.10);
      --shadow-lg: 0 24px 56px rgba(15, 122, 90, 0.14);
      --transition: 180ms ease;
    }

    * { box-sizing: border-box; }

    html {
      color-scheme: light;
      overflow-x: hidden;
    }

    body {
      margin: 0;
      min-height: 100vh;
      overflow-x: hidden;
      color: var(--ink-900);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      line-height: 1.55;
      background: linear-gradient(180deg, #ffffff 0%, var(--brand-50) 100%);
    }

    body.auth-body,
    body.public-body {
      background: linear-gradient(180deg, #ffffff 0%, #f8fcfa 48%, var(--brand-50) 100%);
    }

    a {
      color: inherit;
      text-decoration-thickness: 0.08em;
      text-underline-offset: 0.16em;
    }

    img {
      max-width: 100%;
      display: block;
    }

    h1, h2, h3, h4 {
      margin: 0;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      color: var(--ink-900);
      line-height: 1.12;
      letter-spacing: 0;
      overflow-wrap: anywhere;
    }

    h1 { font-size: clamp(2rem, 4vw, 3.25rem); }
    h2 { font-size: clamp(1.25rem, 2vw, 1.75rem); }
    h3 { font-size: 1.08rem; }

    p { margin: 0; }

    main,
    section,
    div,
    article,
    aside,
    form,
    table,
    th,
    td {
      min-width: 0;
    }

    .eyebrow,
    .hero-kicker,
    .meta-label {
      color: var(--brand-700);
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0;
      text-transform: uppercase;
    }

    .shell,
    .public-shell,
    .auth-shell {
      width: min(1200px, calc(100% - 2rem));
      margin: 0 auto;
      max-width: 100%;
    }

    .public-shell,
    .auth-shell {
      padding: 1.5rem 0 2.5rem;
    }

    .shell {
      padding: 1.25rem 0 2.5rem;
    }

    .surface,
    section,
    .hero-card,
    .hero-meta,
    .feature-grid a,
    .auth-card,
    .auth-panel,
    .marketing-card,
    .stat-card,
    .public-nav {
      border: 1px solid rgba(15, 122, 90, 0.12);
      background: rgba(255, 255, 255, 0.88);
      box-shadow: var(--shadow-sm);
    }

    section,
    .hero-card,
    .hero-meta,
    .auth-card,
    .auth-panel,
    .marketing-card,
    .stat-card {
      border-radius: var(--radius-lg);
      padding: 1.5rem;
    }

    section { margin-bottom: 1rem; }

    .section-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.25rem;
      flex-wrap: wrap;
    }

    .section-copy,
    .muted,
    .hero-card p,
    .hero-meta p,
    .marketing-copy,
    .auth-copy,
    .small {
      color: var(--ink-700);
    }

    .page-lead {
      max-width: 58ch;
      font-size: 1.05rem;
      color: var(--ink-700);
    }

    .button,
    button,
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      min-height: 46px;
      padding: 0.75rem 1rem;
      border-radius: 12px;
      border: 1px solid transparent;
      font: inherit;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: transform var(--transition), border-color var(--transition), background-color var(--transition), color var(--transition), box-shadow var(--transition);
      background: linear-gradient(180deg, var(--brand-600) 0%, var(--brand-800) 100%);
      color: #fff;
      box-shadow: 0 8px 18px rgba(15, 122, 90, 0.18);
    }

    .button:hover,
    button:hover,
    .btn:hover {
      transform: translateY(-1px);
      background: linear-gradient(180deg, #1b9d73 0%, var(--brand-900) 100%);
    }

    .button-secondary,
    .btn-secondary {
      background: var(--surface-strong);
      color: var(--brand-800);
      border-color: rgba(15, 122, 90, 0.24);
      box-shadow: none;
    }

    .button-compact,
    button.compact,
    .btn.compact {
      min-height: 38px;
      padding: 0.5rem 0.85rem;
      font-size: 0.92rem;
    }

    .button-secondary:hover,
    .btn-secondary:hover {
      background: var(--brand-50);
    }

    .button-ghost {
      background: transparent;
      color: var(--brand-900);
      border-color: rgba(31, 94, 67, 0.16);
      box-shadow: none;
    }

    button.danger,
    .button-danger {
      background: var(--danger-600);
      color: #fff;
      box-shadow: 0 10px 22px rgba(185, 56, 42, 0.18);
    }

    button.danger:hover,
    .button-danger:hover {
      background: #992b1f;
    }

    select,
    input,
    textarea {
      width: 100%;
      min-height: 46px;
      padding: 0.76rem 0.9rem;
      border-radius: 12px;
      border: 1px solid var(--line-200);
      background: #fff;
      color: var(--ink-900);
      font: inherit;
      transition: border-color var(--transition), box-shadow var(--transition), background-color var(--transition);
    }

    select:focus,
    input:focus,
    textarea:focus,
    button:focus-visible,
    .btn:focus-visible,
    .button:focus-visible,
    a:focus-visible {
      outline: none;
      border-color: rgba(15, 122, 90, 0.52);
      box-shadow: 0 0 0 4px rgba(31, 169, 122, 0.14);
    }

    label {
      display: block;
      font-weight: 700;
      color: var(--brand-900);
      margin-bottom: 0.3rem;
    }

    .field {
      display: grid;
      gap: 0.38rem;
    }

    .field-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.95rem;
    }

    .row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.8rem;
    }

    .row > * {
      flex: 1 1 180px;
      min-width: 0;
    }

    .row > button,
    .row > .button,
    .row > .btn {
      flex: 0 0 auto;
    }

    .feature-grid,
    .stats-grid,
    .metric-grid,
    .metrics,
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
    }

    .feature-grid a {
      display: grid;
      gap: 0.5rem;
      border-radius: var(--radius-md);
      padding: 1.25rem;
      text-decoration: none;
      color: inherit;
      transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition), background-color var(--transition);
    }

    .feature-grid a:hover {
      transform: translateY(-2px);
      border-color: rgba(31, 94, 67, 0.24);
      box-shadow: var(--shadow-md);
      background: #fff;
    }

    .feature-grid p { color: var(--ink-700); }

    .metric-card,
    .metric,
    .stat-card {
      border-radius: var(--radius-md);
      background: linear-gradient(180deg, #ffffff 0%, var(--brand-50) 100%);
      border: 1px solid var(--line-100);
      padding: 1.25rem;
    }

    .metric-label,
    .metric span:first-child {
      display: block;
      color: var(--ink-500);
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0;
      text-transform: uppercase;
    }

    .metric-value,
    .metric span:last-child {
      display: block;
      margin-top: 0.3rem;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--brand-950);
    }

    .module-tag,
    .role-pill,
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      min-height: 32px;
      padding: 0.28rem 0.72rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0;
      text-transform: uppercase;
    }

    .module-tag,
    .role-pill,
    .status-pending {
      background: var(--brand-100);
      color: var(--brand-800);
    }

    .status-approved {
      background: var(--success-50);
      color: var(--success-700);
    }

    .status-rejected {
      background: var(--danger-50);
      color: var(--danger-600);
    }

    .message {
      min-height: 1.4rem;
      margin-top: 0.9rem;
      font-size: 0.98rem;
      font-weight: 700;
    }

    .toast-region {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      width: min(24rem, calc(100vw - 2rem));
      pointer-events: none;
    }

    .toast {
      pointer-events: auto;
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 0.95rem 1rem;
      border-radius: 16px;
      border: 1px solid rgba(15, 122, 90, 0.14);
      background: rgba(255, 255, 255, 0.96);
      box-shadow: var(--shadow-lg);
      color: var(--ink-900);
      transform: translateY(-10px);
      opacity: 0;
      animation: toast-in 180ms ease forwards;
    }

    .toast::before {
      content: '';
      width: 0.75rem;
      height: 0.75rem;
      margin-top: 0.3rem;
      border-radius: 999px;
      background: var(--brand-600);
      flex: 0 0 auto;
      box-shadow: 0 0 0 4px rgba(31, 169, 122, 0.12);
    }

    .toast-message {
      flex: 1 1 auto;
      font-size: 0.95rem;
      line-height: 1.45;
      color: var(--ink-900);
    }

    .toast-close {
      min-width: 2rem;
      min-height: 2rem;
      padding: 0;
      border-radius: 999px;
      background: transparent;
      color: var(--ink-500);
      box-shadow: none;
    }

    .toast-close:hover,
    .toast-close:focus-visible {
      color: var(--ink-900);
      background: var(--brand-50);
      box-shadow: none;
    }

    .toast-success::before { background: var(--success-700); box-shadow: 0 0 0 4px rgba(15, 122, 90, 0.12); }
    .toast-error::before { background: var(--danger-600); box-shadow: 0 0 0 4px rgba(185, 56, 42, 0.12); }
    .toast-warning::before { background: var(--warning-700); box-shadow: 0 0 0 4px rgba(154, 103, 0, 0.12); }
    .toast-info::before { background: var(--brand-600); box-shadow: 0 0 0 4px rgba(31, 169, 122, 0.12); }

    @keyframes toast-in {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .error,
    .err {
      color: var(--danger-600);
    }

    .success,
    .ok {
      color: var(--success-700);
    }

    .table-responsive,
    .table-wrap {
      overflow-x: auto;
      max-width: 100%;
      -webkit-overflow-scrolling: touch;
      border: 1px solid var(--line-100);
      border-radius: var(--radius-md);
      background: #fff;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      table-layout: auto;
    }

    .table-responsive table,
    .table-wrap table {
      width: max-content;
      min-width: 100%;
    }

    th,
    td {
      padding: 0.9rem 0.82rem;
      border-bottom: 1px solid var(--line-100);
      text-align: left;
      vertical-align: top;
      overflow-wrap: break-word;
      word-break: normal;
    }

    th {
      background: var(--surface-soft);
      color: var(--brand-900);
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0;
      text-transform: uppercase;
      white-space: nowrap;
      overflow-wrap: normal;
    }

    td input,
    td select {
      min-width: 9.5rem;
    }

    .table-actions {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0.55rem;
      min-width: 8rem;
    }

    .prospectus-table {
      border: 1px solid var(--ink-900);
      font-size: 0.84rem;
      background: #fff;
    }

    .prospectus-table th,
    .prospectus-table td {
      border: 1px solid #4b554f;
      padding: 0.24rem 0.36rem;
      vertical-align: middle;
    }

    .prospectus-table th {
      background: #283f24;
      color: #fff;
      font-size: 0.68rem;
      letter-spacing: 0;
      text-align: center;
    }

    .prospectus-term-row td {
      background: #e4e8e5;
      color: #10251d;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-weight: 800;
      text-transform: uppercase;
    }

    .prospectus-total-row td {
      background: #f7f9f8;
      font-weight: 800;
      text-align: center;
    }

    .prospectus-code {
      white-space: nowrap;
      font-weight: 700;
    }

    .prospectus-title {
      min-width: 28rem;
    }

    .prospectus-center {
      text-align: center;
      white-space: nowrap;
    }

    .prospectus-sheet {
      min-width: 980px;
      padding: 1.1rem 1.2rem 1.2rem;
      background: #fff;
      color: #17231d;
      border: 1px solid #c8d0ca;
    }

    .prospectus-heading {
      display: grid;
      grid-template-columns: 90px minmax(0, 1fr) 90px;
      align-items: center;
      gap: 1rem;
      text-align: center;
      margin-bottom: 0.9rem;
    }

    .prospectus-seal {
      width: 58px;
      height: 58px;
      margin: 0 auto;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: #2d6b3e;
      color: #f5d96f;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-weight: 800;
      border: 4px solid #d7e4d8;
    }

    .prospectus-brand h3 {
      margin: 0;
      font-size: 1.35rem;
      letter-spacing: 0;
      color: #263126;
      text-transform: uppercase;
    }

    .prospectus-brand p {
      color: #4f5d54;
      font-size: 0.82rem;
      line-height: 1.25;
    }

    .prospectus-program-title {
      margin-top: 0.7rem;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-weight: 800;
      font-size: 1rem;
      text-transform: uppercase;
      color: #263126;
    }

    .prospectus-student-line {
      display: flex;
      align-items: flex-end;
      gap: 0.6rem;
      margin: 0.35rem 0 0.5rem;
      font-weight: 800;
      text-transform: uppercase;
    }

    .prospectus-student-line span {
      flex: 1;
      min-height: 1.3rem;
      border-bottom: 3px solid #365732;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 1rem;
      letter-spacing: 0;
    }

    .prospectus-rule {
      height: 0.45rem;
      background: #365732;
      border-top: 1px solid #223c20;
      border-bottom: 1px solid #223c20;
      margin-bottom: 0.15rem;
    }

    .prospectus-filters {
      margin-bottom: 1.1rem;
    }

    .prospectus-output {
      margin-top: 1.15rem;
    }

    .print-only {
      display: none;
    }

    tr:last-child td { border-bottom: none; }

    .navbar,
    .public-nav {
      position: sticky;
      top: 0.8rem;
      z-index: 10;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.875rem 1rem;
      margin-bottom: 1.5rem;
      border-radius: var(--radius-lg);
      backdrop-filter: blur(8px);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      min-width: 220px;
      text-decoration: none;
    }

    .brand-mark {
      width: 46px;
      height: 46px;
      border-radius: 15px;
      display: grid;
      place-items: center;
      background: linear-gradient(180deg, var(--brand-600) 0%, var(--brand-800) 100%);
      color: #fff;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.95rem;
      font-weight: 800;
      letter-spacing: 0;
      box-shadow: 0 12px 24px rgba(31, 94, 67, 0.18);
    }

    .brand-text strong {
      display: block;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 1rem;
      color: var(--brand-950);
    }

    .brand-text span {
      display: block;
      color: var(--ink-700);
      font-size: 0.9rem;
    }

    .nav-links,
    .public-actions,
    .nav-side,
    .nav-menu {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      flex-wrap: wrap;
    }

    .nav-menu {
      flex: 1 1 auto;
      justify-content: space-between;
      min-width: 0;
    }

    .public-nav .nav-menu {
      flex: 0 0 auto;
      justify-content: flex-end;
    }

    .nav-links {
      flex: 1 1 420px;
      justify-content: center;
      flex-wrap: nowrap;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: none;
      -ms-overflow-style: none;
      padding-bottom: 0.15rem;
    }

    .nav-links::-webkit-scrollbar {
      display: none;
    }

    .nav-link {
      padding: 0.56rem 0.72rem;
      border-radius: 12px;
      text-decoration: none;
      color: var(--ink-700);
      font-weight: 700;
      white-space: nowrap;
      flex: 0 0 auto;
      transition: background-color var(--transition), color var(--transition), transform var(--transition);
    }

    .nav-side {
      flex: 0 0 auto;
      justify-content: flex-end;
    }

    .nav-toggle {
      position: absolute;
      inline-size: 1px;
      block-size: 1px;
      opacity: 0;
      pointer-events: none;
    }

    .nav-toggle-button {
      display: none;
      width: 46px;
      height: 46px;
      flex: 0 0 46px;
      align-items: center;
      justify-content: center;
      gap: 4px;
      border-radius: 999px;
      border: 1px solid rgba(31, 94, 67, 0.16);
      background: var(--surface-strong);
      color: var(--brand-900);
      cursor: pointer;
      margin-bottom: 0;
      box-shadow: none;
    }

    .nav-toggle-button span {
      display: block;
      width: 18px;
      height: 2px;
      border-radius: 999px;
      background: currentColor;
      transition: transform var(--transition), opacity var(--transition);
    }

    .nav-link:hover {
      color: var(--brand-900);
      background: rgba(15, 122, 90, 0.08);
    }

    .nav-link.active {
      background: var(--brand-800);
      color: #fff;
      box-shadow: 0 8px 18px rgba(15, 122, 90, 0.18);
    }

    .hero {
      display: grid;
      grid-template-columns: minmax(0, 1.8fr) minmax(260px, 0.9fr);
      gap: 1rem;
      margin-bottom: 1.25rem;
    }

    .hero-card,
    .hero-meta {
      min-height: 100%;
    }

    .hero-card {
      display: grid;
      gap: 0.75rem;
      align-content: start;
    }

    .hero-meta {
      display: grid;
      gap: 1rem;
      align-content: start;
    }

    .meta-value {
      margin-top: 0.18rem;
      font-weight: 700;
      color: var(--brand-950);
      word-break: break-word;
    }

    .marketing-hero {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.95fr);
      gap: 1rem;
      align-items: stretch;
      margin-bottom: 1rem;
    }

    .marketing-card {
      display: grid;
      gap: 1rem;
      padding: 1.5rem;
    }

    .marketing-copy {
      max-width: 58ch;
      font-size: 1.1rem;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .hero-points {
      display: grid;
      gap: 0.75rem;
    }

    .hero-point {
      padding: 1rem;
      border-radius: var(--radius-md);
      background: var(--surface-soft);
      border: 1px solid var(--line-100);
    }

    .hero-point strong {
      display: block;
      margin-bottom: 0.22rem;
      color: var(--brand-900);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .auth-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(320px, 0.92fr);
      gap: 1rem;
      align-items: stretch;
      min-height: calc(100vh - 5rem);
    }

    .auth-card,
    .auth-panel {
      display: grid;
      align-content: start;
      gap: 1rem;
    }

    .auth-card {
      padding: 1.75rem;
    }

    .auth-form {
      display: grid;
      gap: 0.95rem;
    }

    .auth-actions {
      display: grid;
      gap: 0.85rem;
      margin-top: 0.25rem;
    }

    .auth-footnote {
      color: var(--ink-700);
      font-size: 0.95rem;
    }

    .agreement-field {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      align-items: start;
      gap: 0.75rem;
      padding: 1rem;
      border: 1px solid var(--line-100);
      border-radius: var(--radius-sm);
      background: #ffffff;
      color: var(--ink-700);
      font-size: 0.95rem;
    }

    .agreement-field input {
      width: 1.05rem;
      min-height: 1.05rem;
      margin-top: 0.18rem;
      accent-color: var(--brand-800);
    }

    .agreement-field label {
      margin: 0;
      color: var(--ink-700);
      font-weight: 600;
      line-height: 1.4;
    }

    .inline-link {
      padding: 0;
      min-height: auto;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--brand-800);
      box-shadow: none;
      font: inherit;
      font-weight: 800;
      text-decoration: underline;
      text-underline-offset: 0.16em;
    }

    .inline-link:hover {
      background: transparent;
      color: var(--brand-900);
      transform: none;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 100;
      display: none;
      place-items: center;
      padding: 1rem;
      background: rgba(7, 59, 45, 0.42);
    }

    .modal-backdrop.open {
      display: grid;
    }

    .modal {
      width: min(680px, 100%);
      max-height: min(80vh, 720px);
      overflow-y: auto;
      border-radius: var(--radius-lg);
      border: 1px solid rgba(15, 122, 90, 0.16);
      background: #fff;
      box-shadow: var(--shadow-lg);
      padding: 1.5rem;
    }

    .modal-head {
      display: flex;
      align-items: start;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0.85rem;
    }

    .modal-content {
      display: grid;
      gap: 0.75rem;
      color: var(--ink-700);
    }

    .modal-content ul {
      margin: 0;
      padding-left: 1.15rem;
    }

    .auth-panel {
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(245, 251, 248, 0.94) 100%);
      backdrop-filter: blur(8px);
    }

    .auth-list {
      display: grid;
      gap: 0.75rem;
    }

    .auth-list-item {
      padding: 1rem;
      border-radius: var(--radius-md);
      background: #ffffff;
      border: 1px solid rgba(15, 122, 90, 0.10);
    }

    .auth-list-item strong {
      display: block;
      margin-bottom: 0.18rem;
      color: var(--brand-900);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .public-footer,
    .auth-footer {
      padding: 0.6rem 0 0;
      color: var(--ink-500);
      font-size: 0.95rem;
    }

    .auth-card,
    .auth-panel,
    .hero-card,
    .hero-meta,
    .marketing-card,
    .stat-card,
    section {
      background: rgba(255, 255, 255, 0.94);
    }

    .hero-card,
    .auth-card,
    .marketing-card {
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(245, 251, 248, 0.96) 100%);
    }

    @media (max-width: 960px) {
      .hero,
      .marketing-hero,
      .auth-grid {
        grid-template-columns: 1fr;
      }

      .navbar,
      .public-nav {
        flex-wrap: wrap;
      }

      .brand {
        flex: 1 1 auto;
      }

      .nav-toggle-button {
        display: inline-flex;
        flex-direction: column;
      }

      .nav-menu {
        display: none;
        width: 100%;
        flex: 1 1 100%;
        align-items: stretch;
        gap: 0.75rem;
        padding-top: 0.85rem;
        border-top: 1px solid var(--line-100);
      }

      .public-nav .nav-menu {
        flex: 1 1 100%;
        justify-content: flex-start;
      }

      .nav-toggle:checked + .nav-toggle-button + .nav-menu {
        display: flex;
      }

      .nav-toggle:checked + .nav-toggle-button span:nth-child(1) {
        transform: translateY(6px) rotate(45deg);
      }

      .nav-toggle:checked + .nav-toggle-button span:nth-child(2) {
        opacity: 0;
      }

      .nav-toggle:checked + .nav-toggle-button span:nth-child(3) {
        transform: translateY(-6px) rotate(-45deg);
      }

      .nav-links {
        justify-content: flex-start;
        flex: 1 1 100%;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        overflow: visible;
        padding-bottom: 0;
      }

      .nav-link {
        text-align: center;
        white-space: normal;
      }

      .nav-side {
        width: 100%;
        justify-content: space-between;
      }

      .public-actions {
        width: 100%;
      }

      .public-actions .btn {
        flex: 1 1 180px;
      }
    }

    @media (max-width: 720px) {
      .shell,
      .public-shell,
      .auth-shell {
        width: min(100% - 1rem, 1200px);
      }

      .field-grid {
        grid-template-columns: 1fr;
      }

      .row {
        align-items: stretch;
      }

      .row > * {
        flex-basis: 100%;
      }

      .navbar,
      .public-nav {
        top: 0.45rem;
        border-radius: 18px;
      }

      table {
        min-width: 520px;
      }
    }

    @media (max-width: 520px) {
      h1 { font-size: 1.75rem; }
      h2 { font-size: 1.2rem; }

      .shell,
      .public-shell,
      .auth-shell {
        width: min(100% - 0.7rem, 1200px);
      }

      .public-shell,
      .auth-shell,
      .shell {
        padding-top: 0.55rem;
      }

      section,
      .hero-card,
      .hero-meta,
      .auth-card,
      .auth-panel,
      .marketing-card,
      .stat-card {
        border-radius: var(--radius-md);
        padding: 0.9rem;
      }

      .navbar,
      .public-nav {
        gap: 0.65rem;
        padding: 0.7rem;
      }

      .brand {
        min-width: 0;
        gap: 0.65rem;
      }

      .brand-mark {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 13px;
        font-size: 0.82rem;
      }

      .brand-text strong {
        font-size: 0.92rem;
      }

      .brand-text span {
        display: none;
      }

      .nav-toggle-button {
        width: 40px;
        height: 40px;
        flex-basis: 40px;
      }

      .nav-links,
      .feature-grid,
      .stats-grid,
      .metric-grid,
      .metrics,
      .metrics-grid {
        grid-template-columns: 1fr;
      }

      .nav-side,
      .public-actions,
      .hero-actions,
      .auth-actions {
        display: grid;
        grid-template-columns: 1fr;
      }

      .btn,
      .button,
      button,
      .role-pill,
      .status-badge {
        width: 100%;
      }

      .table-responsive,
      .table-wrap {
        margin-inline: -0.25rem;
        border-radius: var(--radius-sm);
      }

      table {
        min-width: 460px;
      }

      th,
      td {
        padding: 0.68rem 0.55rem;
        font-size: 0.92rem;
      }

      th {
        font-size: 0.74rem;
        letter-spacing: 0;
      }
    }

    @media (max-width: 380px) {
      h1 { font-size: 1.55rem; }

      .shell,
      .public-shell,
      .auth-shell {
        width: min(100% - 0.5rem, 1200px);
      }

      section,
      .hero-card,
      .hero-meta,
      .auth-card,
      .auth-panel,
      .marketing-card,
      .stat-card {
        padding: 0.75rem;
      }

      .button,
      button,
      .btn,
      select,
      input,
      textarea {
        min-height: 42px;
        padding: 0.62rem 0.72rem;
      }

      table {
        min-width: 420px;
      }

      th,
      td {
        padding: 0.58rem 0.45rem;
        font-size: 0.86rem;
      }
    }

    @media print {
      @page {
        size: legal portrait;
        margin: 0.18in;
      }

      html,
      body {
        overflow: visible;
        background: #fff !important;
      }

      body * {
        visibility: hidden;
      }

      .prospectus-output,
      .prospectus-output * {
        visibility: visible;
      }

      .prospectus-output {
        position: absolute;
        inset: 0;
        margin: 0 !important;
        width: 100%;
        display: flex;
        justify-content: center;
      }

      .prospectus-output .table-responsive {
        overflow: visible;
        border: 0;
        border-radius: 0;
        width: 100%;
      }

      .prospectus-sheet {
        min-width: 0;
        width: 100%;
        max-width: 7.85in;
        padding: 0;
        border: 0;
      }

      .prospectus-output .prospectus-table {
        width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed;
        font-size: 6.15pt;
        line-height: 1;
      }

      .prospectus-table th,
      .prospectus-table td {
        padding: 1.25pt 2pt;
      }

      .prospectus-table th {
        color: #fff !important;
        background: #283f24 !important;
        font-size: 3.50pt;
        letter-spacing: 0;
        line-height: 1;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .prospectus-term-row td,
      .prospectus-total-row td,
      .prospectus-rule,
      .prospectus-seal {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .prospectus-term-row td,
      .prospectus-total-row td {
        font-size: 6pt;
        line-height: 1;
      }

      .prospectus-title {
        min-width: 0;
        width: 43%;
      }

      .prospectus-heading {
        grid-template-columns: 44px minmax(0, 1fr) 44px;
        gap: 0.22rem;
        margin-bottom: 0.08rem;
      }

      .prospectus-seal {
        width: 34px;
        height: 34px;
        border-width: 2px;
        font-size: 0.48rem;
      }

      .prospectus-brand h3 {
        font-size: 0.62rem;
        letter-spacing: 0;
      }

      .prospectus-brand p {
        font-size: 0.36rem;
      }

      .prospectus-program-title {
        margin-top: 0.06rem;
        font-size: 0.45rem;
      }

      .prospectus-student-line {
        margin: 0.04rem 0 0.06rem;
        font-size: 0.45rem;
      }

      .prospectus-student-line span {
        min-height: 0.44rem;
        border-bottom-width: 1px;
        font-size: 0.48rem;
      }

      .prospectus-rule {
        height: 0.08rem;
      }

      .prospectus-code,
      .prospectus-center {
        width: auto;
      }

      .prospectus-output .prospectus-table th:nth-child(1),
      .prospectus-output .prospectus-table td:nth-child(1) {
        width: 7%;
      }

      .prospectus-output .prospectus-table th:nth-child(2),
      .prospectus-output .prospectus-table td:nth-child(2) {
        width: 13%;
      }

      .prospectus-output .prospectus-table th:nth-child(3),
      .prospectus-output .prospectus-table td:nth-child(3) {
        width: 47%;
      }

      .prospectus-output .prospectus-table th:nth-child(4),
      .prospectus-output .prospectus-table th:nth-child(5),
      .prospectus-output .prospectus-table th:nth-child(6),
      .prospectus-output .prospectus-table th:nth-child(7),
      .prospectus-output .prospectus-table td:nth-child(4),
      .prospectus-output .prospectus-table td:nth-child(5),
      .prospectus-output .prospectus-table td:nth-child(6),
      .prospectus-output .prospectus-table td:nth-child(7) {
        width: 5.5%;
      }

      .prospectus-output .prospectus-table th:nth-child(8),
      .prospectus-output .prospectus-table td:nth-child(8) {
        width: 11%;
      }
    }

    /* School Records Database sharp redesign pass. */
    :root {
      --brand-950: #063b2c;
      --brand-900: #07543e;
      --brand-800: #0f7a5a;
      --brand-700: #128c67;
      --brand-600: #1fa97a;
      --brand-100: #dff5ed;
      --brand-50: #f5fbf8;
      --ink-950: #031f18;
      --ink-900: #063b2d;
      --ink-800: #07543e;
      --ink-700: #2f5b4d;
      --ink-500: #668277;
      --line-200: rgba(6, 59, 45, 0.16);
      --line-100: rgba(6, 59, 45, 0.09);
      --surface-strong: #ffffff;
      --surface-soft: #f5fbf8;
      --surface-muted: #eef6f2;
      --radius-xl: 4px;
      --radius-lg: 4px;
      --radius-md: 3px;
      --radius-sm: 2px;
      --shadow-sm: none;
      --shadow-md: none;
      --shadow-lg: 0 20px 48px rgba(3, 31, 24, 0.18);
      --transition: 120ms ease;
    }

    body {
      color: var(--ink-900);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      line-height: 1.48;
      background: #f5fbf8;
    }

    body.auth-body,
    body.public-body {
      background: var(--ink-950);
    }

    h1,
    h2,
    h3,
    h4,
    .metric-value,
    .metric span:last-child {
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-weight: 700;
      color: var(--ink-900);
      line-height: 1.14;
    }

    h1 { font-size: clamp(1.85rem, 3.1vw, 2.7rem); }
    h2 { font-size: clamp(1.25rem, 1.8vw, 1.65rem); }
    h3 { font-size: 1.05rem; }

    .eyebrow,
    .hero-kicker,
    .meta-label,
    .metric-label,
    .metric span:first-child,
    th {
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .eyebrow,
    .hero-kicker,
    .meta-label {
      color: var(--brand-700);
    }

    .shell,
    .public-shell {
      width: min(1580px, calc(100% - 2rem));
    }

    .auth-shell {
      width: min(1200px, calc(100% - 2rem));
    }

    .auth-grid {
      min-height: auto;
      align-items: stretch;
    }

    .auth-card,
    .auth-panel {
      min-height: 0;
      height: 100%;
    }

    .surface,
    section,
    .hero-card,
    .hero-meta,
    .feature-grid a,
    .auth-card,
    .auth-panel,
    .marketing-card,
    .stat-card,
    .metric-card,
    .metric,
    .public-nav,
    .navbar {
      border: 1px solid var(--line-200);
      background: #fff;
      box-shadow: none;
    }

    section,
    .hero-card,
    .hero-meta,
    .auth-card,
    .auth-panel,
    .marketing-card,
    .stat-card,
    .metric-card,
    .metric,
    .feature-grid a,
    .table-responsive,
    .table-wrap,
    .modal,
    .agreement-field,
    .auth-list-item,
    .hero-point {
      border-radius: 4px;
    }

    section,
    .hero-card,
    .hero-meta,
    .auth-card,
    .marketing-card,
    .stat-card {
      border-top: 3px solid var(--brand-800);
    }

    .hero-card,
    .auth-card,
    .marketing-card,
    .auth-card,
    .auth-panel,
    .hero-meta,
    .stat-card,
    section {
      background: #fff;
    }

    .navbar,
    .public-nav {
      top: 0;
      margin-bottom: 1.25rem;
      padding: 0.72rem 1rem;
      border-radius: 0;
      background: var(--ink-900);
      border-color: rgba(255, 255, 255, 0.08);
      backdrop-filter: none;
      transition: background-color var(--transition), border-color var(--transition), box-shadow var(--transition);
    }

    .navbar.is-scrolled,
    .public-nav.is-scrolled {
      background: #063d30;
      border-color: rgba(255, 255, 255, 0.16);
      box-shadow: 0 14px 34px rgba(7, 59, 45, 0.28);
    }

    .history-filters {
      display: grid;
      grid-template-columns: minmax(220px, 1.3fr) minmax(180px, 0.85fr) minmax(180px, 0.85fr);
      gap: 0.85rem;
      align-items: end;
      margin-bottom: 1rem;
      overflow-x: auto;
      padding-bottom: 0.15rem;
    }

    .history-filters .field {
      min-width: 0;
    }

    .brand-mark {
      width: 34px;
      height: 34px;
      border-radius: 2px;
      background: #fff;
      color: var(--ink-950);
      box-shadow: none;
      padding: 4px;
      overflow: hidden;
    }

    .brand-mark img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .brand-text strong {
      color: #fff;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.92rem;
      font-weight: 700;
    }

    .brand-text span {
      color: #a8d9c6;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.64rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .nav-link {
      border-radius: 2px;
      color: #c5eadb;
      font-size: 0.84rem;
      font-weight: 500;
      padding: 0.42rem 0.75rem;
    }

    .nav-link:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.07);
      transform: none;
    }

    .nav-link.active {
      background: rgba(31, 169, 122, 0.12);
      color: #8af0c7;
      box-shadow: none;
    }

    .role-pill,
    .module-tag,
    .status-badge {
      min-height: 0;
      border-radius: 2px;
      padding: 0.26rem 0.62rem;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.04em;
    }

    .role-pill {
      background: var(--ink-800);
      color: #c5eadb;
    }

    .module-tag,
    .status-pending {
      background: var(--ink-900);
      color: #e4f7ef;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .status-approved {
      background: #f0fdf4;
      color: #15803d;
      border: 1px solid rgba(21, 128, 61, 0.2);
    }

    .status-rejected {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid rgba(185, 28, 28, 0.2);
    }

    .button,
    button,
    .btn {
      min-height: 38px;
      border-radius: 3px;
      background: var(--ink-800);
      color: #fff;
      box-shadow: none;
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.88rem;
      font-weight: 600;
      letter-spacing: 0.02em;
      padding: 0.58rem 0.95rem;
      transform: none;
    }

    .button:hover,
    button:hover,
    .btn:hover {
      background: var(--brand-900);
      transform: none;
    }

    .button-secondary,
    .btn-secondary,
    .button-ghost {
      background: transparent;
      color: #c5eadb;
      border-color: rgba(255, 255, 255, 0.16);
    }

    .public-shell .btn-secondary,
    .auth-shell .btn-secondary,
    .navbar .btn-secondary {
      color: #e4f7ef;
    }

    .shell .button-secondary,
    .shell .btn-secondary,
    .shell .button-ghost,
    section .btn-secondary {
      color: var(--brand-900);
      border-color: rgba(13, 24, 38, 0.16);
    }

    .button-secondary:hover,
    .btn-secondary:hover,
    .button-ghost:hover {
      background: rgba(31, 169, 122, 0.09);
      color: var(--brand-900);
    }

    .navbar .btn-secondary:hover,
    .public-shell .btn-secondary:hover,
    .auth-shell .btn-secondary:hover {
      color: #fff;
    }

    input,
    select,
    textarea {
      min-height: 40px;
      border-radius: 3px;
      border-color: rgba(13, 24, 38, 0.16);
      padding: 0.62rem 0.76rem;
      background: #fff;
    }

    .password-field {
      position: relative;
    }

    .password-field input {
      padding-right: 3rem;
    }

    .password-toggle {
      position: absolute;
      top: 50%;
      right: 0.45rem;
      display: inline-grid;
      place-items: center;
      width: 2.1rem;
      min-width: 0;
      min-height: 2.1rem;
      padding: 0;
      border: 0;
      border-radius: 3px;
      background: transparent;
      color: var(--brand-800);
      box-shadow: none;
      transform: translateY(-50%);
    }

    .password-toggle:hover {
      background: rgba(31, 169, 122, 0.1);
      color: var(--brand-900);
      transform: translateY(-50%);
    }

    .password-toggle svg {
      width: 1.15rem;
      height: 1.15rem;
      pointer-events: none;
    }

    label {
      color: var(--ink-700);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    input:focus,
    select:focus,
    textarea:focus,
    button:focus-visible,
    .btn:focus-visible,
    .button:focus-visible,
    a:focus-visible {
      border-color: rgba(31, 169, 122, 0.62);
      box-shadow: 0 0 0 3px rgba(31, 169, 122, 0.16);
    }

    .hero {
      grid-template-columns: minmax(0, 1fr) minmax(240px, 0.38fr);
      gap: 0.8rem;
    }

    .hero-card,
    .hero-meta {
      padding: 1.2rem;
    }

    .hero-card {
      gap: 0.55rem;
    }

    .page-lead,
    .section-copy,
    .muted,
    .hero-card p,
    .hero-meta p,
    .marketing-copy,
    .auth-copy,
    .small,
    .feature-grid p,
    .auth-footnote,
    .public-footer,
    .auth-footer {
      color: var(--ink-500);
    }

    .meta-value {
      color: var(--ink-900);
      font-weight: 600;
      font-size: 0.92rem;
    }

    .metric-card,
    .metric,
    .stat-card {
      border-left: 3px solid var(--brand-800);
      border-top: 1px solid var(--line-200);
      background: #fff;
      padding: 1rem;
    }

    .metric-value,
    .metric span:last-child {
      color: var(--ink-900);
      font-size: 1.8rem;
      font-weight: 400;
    }

    .section-head {
      border-bottom: 1px solid var(--line-100);
      padding-bottom: 0.85rem;
      margin-bottom: 1rem;
    }

    .feature-grid a {
      border-top: 3px solid var(--brand-800);
      padding: 1rem;
    }

    .feature-grid a:hover {
      border-color: var(--line-200);
      border-top-color: var(--brand-600);
      background: #fff;
      box-shadow: none;
      transform: none;
    }

    .hero-point,
    .auth-list-item,
    .agreement-field {
      background: #f5fbf8;
      border: 1px solid var(--line-100);
      border-left: 3px solid var(--brand-700);
    }

    .hero-point strong,
    .auth-list-item strong {
      color: var(--ink-900);
      font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      font-weight: 600;
    }

    .table-responsive,
    .table-wrap {
      border: 1px solid var(--line-100);
      background: #fff;
      box-shadow: none;
    }

    th {
      background: var(--ink-900);
      color: #e4f7ef;
      padding: 0.62rem 0.75rem;
    }

    td {
      color: var(--ink-700);
      padding: 0.7rem 0.75rem;
      border-bottom-color: var(--line-100);
    }

    tr:hover td {
      background: #f5fbf8;
    }

    .auth-grid {
      min-height: calc(100vh - 5rem);
      padding: 1.75rem;
      border: 1px solid rgba(255, 255, 255, 0.06);
      background: var(--ink-950);
      border-radius: 0;
    }

    .auth-card,
    .auth-panel {
      border-color: rgba(255, 255, 255, 0.08);
      background: var(--ink-900);
    }

    .auth-card {
      border-top-color: var(--brand-600);
    }

    .auth-panel {
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(255, 255, 255, 0.03);
    }

    .auth-shell h1,
    .auth-shell h2,
    .auth-shell h3 {
      color: #fff;
    }

    .auth-shell .eyebrow,
    .auth-shell label {
      color: #8af0c7;
    }

    .auth-shell input,
    .auth-shell select,
    .auth-shell textarea {
      background: rgba(255, 255, 255, 0.07);
      border-color: rgba(255, 255, 255, 0.12);
      color: #fff;
    }

    .auth-shell input::placeholder,
    .auth-shell textarea::placeholder {
      color: #5d8576;
    }

    .auth-shell .auth-list-item,
    .auth-shell .agreement-field {
      background: rgba(255, 255, 255, 0.04);
      border-color: rgba(255, 255, 255, 0.06);
      border-left-color: var(--brand-700);
    }

    .auth-shell .auth-list-item strong {
      color: #fff;
    }

    .public-body .marketing-card,
    .public-body section {
      border-color: rgba(255, 255, 255, 0.08);
      background: var(--ink-900);
      color: #e4f7ef;
    }

    .public-body h1,
    .public-body h2,
    .public-body h3,
    .public-body .hero-point strong {
      color: #fff;
    }

    .public-body .hero-point,
    .public-body .feature-grid a {
      background: rgba(255, 255, 255, 0.04);
      border-color: rgba(255, 255, 255, 0.06);
      border-left: 3px solid var(--brand-700);
      color: #e4f7ef;
    }

    .nav-toggle-button {
      border-radius: 3px;
      background: transparent;
      color: #e4f7ef;
      border-color: rgba(255, 255, 255, 0.16);
    }

    .nav-toggle-button span {
      border-radius: 0;
    }

    .modal {
      border-top: 3px solid var(--brand-800);
    }

    @media (max-width: 720px) {
      .navbar,
      .public-nav {
        top: 0;
        border-radius: 0;
      }
    }

    @media (max-width: 520px) {
      section,
      .hero-card,
      .hero-meta,
      .auth-card,
      .auth-panel,
      .marketing-card,
      .stat-card {
        border-radius: 4px;
      }

      .brand-mark {
        border-radius: 2px;
      }

      .btn,
      .button,
      button,
      .role-pill,
      .status-badge {
        width: 100%;
      }
    }

    .navbar .nav-side .btn-secondary,
    .public-nav .public-actions .btn-secondary {
      background: rgba(255, 255, 255, 0.08);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.28);
    }

    .navbar .nav-side .btn-secondary:hover,
    .public-nav .public-actions .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.38);
    }

    .navbar .role-pill {
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.18);
    }

    @media (max-width: 960px) {
      .hero,
      .marketing-hero,
      .auth-grid {
        grid-template-columns: 1fr;
      }

      .hero-card,
      .hero-meta {
        min-height: auto;
      }

      .hero-card h1 {
        overflow-wrap: normal;
        word-break: normal;
        hyphens: none;
      }

      .page-lead {
        max-width: 100%;
      }

      .navbar,
      .public-nav {
        align-items: center;
        padding: 0.65rem 0.75rem;
      }

      .brand {
        min-width: 0;
        flex: 1 1 calc(100% - 52px);
      }

      .brand-text {
        min-width: 0;
      }

      .brand-text strong,
      .brand-text span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .nav-menu {
        background: rgba(255, 255, 255, 0.04);
        border-top-color: rgba(255, 255, 255, 0.12);
        padding: 0.75rem;
      }

      .nav-links {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
        width: 100%;
      }

      .nav-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0.55rem 0.6rem;
        text-align: center;
      }

      .nav-side,
      .public-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        width: 100%;
      }

      .navbar .role-pill,
      .navbar .nav-side .btn,
      .public-nav .public-actions .btn {
        width: 100%;
        min-height: 38px;
      }
    }

    @media (max-width: 520px) {
      h1 {
        font-size: 1.55rem;
        line-height: 1.15;
      }

      .hero {
        gap: 0.65rem;
      }

      .hero-card,
      .hero-meta {
        padding: 0.85rem;
      }

      .brand-mark {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
      }

      .brand-text strong {
        font-size: 0.84rem;
      }

      .nav-links,
      .nav-side,
      .public-actions {
        grid-template-columns: 1fr;
      }

      .navbar .nav-side .btn,
      .public-nav .public-actions .btn {
        width: 100%;
      }
    }

    body,
    body.public-body,
    body.auth-body,
    body.app-shell-body {
      background: #ffffff;
    }

    .public-body .marketing-card,
    .public-body section,
    .auth-card,
    .auth-panel,
    .auth-grid {
      background: #ffffff;
      color: var(--ink-900);
      border-color: var(--line-200);
    }

    .auth-grid {
      border-color: transparent;
      padding: 0;
    }

    .public-body h1,
    .public-body h2,
    .public-body h3,
    .public-body .hero-point strong,
    .auth-shell h1,
    .auth-shell h2,
    .auth-shell h3,
    .auth-shell .auth-list-item strong {
      color: var(--ink-900);
    }

    .public-body .hero-point,
    .public-body .feature-grid a,
    .auth-shell .auth-list-item,
    .auth-shell .agreement-field {
      background: var(--surface-soft);
      border-color: var(--line-100);
      border-left: 3px solid var(--brand-700);
      color: var(--ink-700);
    }

    .auth-shell .eyebrow,
    .auth-shell label {
      color: var(--brand-800);
    }

    .auth-shell input,
    .auth-shell select,
    .auth-shell textarea {
      background: #ffffff;
      border-color: var(--line-200);
      color: var(--ink-900);
    }

    .auth-shell input::placeholder,
    .auth-shell textarea::placeholder {
      color: var(--ink-500);
    }

    .public-shell,
    .auth-shell,
    .public-shell .marketing-copy,
    .auth-shell .auth-copy,
    .auth-shell .auth-footnote,
    .public-footer,
    .auth-footer {
      color: var(--ink-700);
    }

    .public-shell .btn-secondary,
    .auth-shell .btn-secondary,
    .public-shell .button-secondary,
    .auth-shell .button-secondary,
    .public-shell .button-ghost,
    .auth-shell .button-ghost {
      background: #ffffff;
      color: var(--brand-900);
      border-color: var(--line-200);
    }

    .public-shell .btn-secondary:hover,
    .auth-shell .btn-secondary:hover,
    .public-shell .button-secondary:hover,
    .auth-shell .button-secondary:hover,
    .public-shell .button-ghost:hover,
    .auth-shell .button-ghost:hover {
      background: var(--surface-soft);
      color: var(--brand-950);
      border-color: rgba(6, 59, 45, 0.24);
    }

    .public-nav .public-actions .btn,
    .navbar .nav-side .btn,
    .navbar .role-pill,
    .navbar .nav-link {
      color: #ffffff;
    }

    .public-nav .brand-text strong,
    .navbar .brand-text strong {
      color: #ffffff;
    }

    .public-nav .brand-text span,
    .navbar .brand-text span {
      color: #dff5ed;
    }

    .public-nav .public-actions .btn-secondary,
    .navbar .nav-side .btn-secondary {
      background: rgba(255, 255, 255, 0.08);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.28);
    }

    .public-nav .public-actions .btn-secondary:hover,
    .navbar .nav-side .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.14);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.38);
    }

    html,
    body {
      overflow-x: clip;
    }

    body.app-shell-body,
    body.public-body {
      transition: background-color var(--transition);
    }

    body.app-shell-body.nav-scrolled,
    body.public-body.nav-scrolled {
      background: #f2faf6;
    }

    .navbar,
    .public-nav {
      position: sticky;
      top: 0;
      z-index: 100;
      width: 100vw;
      max-width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
      padding-left: max(1rem, calc((100vw - 1580px) / 2));
      padding-right: max(1rem, calc((100vw - 1580px) / 2));
      border-left: 0;
      border-right: 0;
    }

    .shell,
    .public-shell {
      padding-top: 0;
    }

    .auth-shell {
      padding-top: 0;
    }

    .auth-grid {
      min-height: auto;
      align-items: stretch;
    }

    .hero-actions {
      align-items: center;
    }

    .hero-actions .btn {
      width: auto;
      min-width: 7rem;
      min-height: 2.75rem;
      padding: 0.68rem 1rem;
      align-self: center;
    }

    .auth-masthead {
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1rem;
      padding: 0.72rem 1rem;
      width: 100vw;
      max-width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
      padding-left: max(1rem, calc((100vw - 1200px) / 2));
      padding-right: max(1rem, calc((100vw - 1200px) / 2));
      background: var(--ink-900);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-top: 0;
      border-left: 0;
      border-right: 0;
    }

    .auth-masthead .brand {
      min-width: 0;
    }

    .auth-masthead .brand-text strong {
      color: #ffffff;
    }

    .auth-masthead .brand-text span {
      color: #dff5ed;
    }

    .auth-masthead .role-pill {
      flex: 0 0 auto;
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(31, 169, 122, 0.12);
    }

    .auth-card,
    .auth-panel {
      min-height: 0;
      height: 100%;
      border-top: 4px solid var(--brand-800);
      box-shadow: 0 16px 34px rgba(7, 59, 45, 0.08);
      padding: 1.5rem;
    }

    .auth-card {
      position: relative;
      overflow: hidden;
    }

    .auth-card::before,
    .auth-panel::before {
      content: "";
      display: block;
      height: 0.34rem;
      margin: -1.5rem -1.5rem 1.15rem;
      background: var(--brand-800);
    }

    .auth-shell .auth-card h1 {
      max-width: 12ch;
    }

    .auth-panel {
      align-content: start;
    }

    .auth-shell .auth-list-item {
      background: #f5fbf8;
      border-left: 3px solid var(--brand-800);
    }

    @media (max-width: 720px) {
      .auth-masthead {
        align-items: stretch;
        flex-direction: column;
        padding-left: 1rem;
        padding-right: 1rem;
      }

      .auth-masthead .role-pill {
        width: 100%;
        justify-content: center;
      }
    }

    .navbar.is-scrolled,
    .public-nav.is-scrolled {
      background: #084936;
      box-shadow: 0 14px 34px rgba(7, 59, 45, 0.26);
    }

    .navbar .nav-link,
    .public-nav .btn,
    .navbar .nav-side .btn,
    .navbar .role-pill,
    .auth-masthead .role-pill {
      position: relative;
      overflow: hidden;
      transition: color var(--transition), background-color var(--transition), border-color var(--transition), box-shadow var(--transition), transform var(--transition);
    }

    .navbar .nav-link::after {
      content: "";
      position: absolute;
      left: 0.7rem;
      right: 0.7rem;
      bottom: 0.24rem;
      height: 2px;
      background: #8af0c7;
      transform: scaleX(0);
      transform-origin: center;
      transition: transform var(--transition);
    }

    .navbar .nav-link:hover,
    .navbar .nav-link:focus-visible {
      color: #ffffff;
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-0.5px);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
    }

    .navbar .nav-link:hover::after,
    .navbar .nav-link:focus-visible::after,
    .navbar .nav-link.active::after {
      transform: scaleX(1);
    }

    .navbar .nav-link.active {
      background: rgba(31, 169, 122, 0.18);
      color: #ffffff;
    }

    .public-nav .btn:hover,
    .public-nav .btn:focus-visible,
    .navbar .nav-side .btn:hover,
    .navbar .nav-side .btn:focus-visible,
    .navbar .role-pill:hover,
    .auth-masthead .role-pill:hover {
      transform: translateY(-0.5px);
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
      border-color: rgba(255, 255, 255, 0.38);
    }

    button,
    .btn,
    .button,
    .inline-link,
    .password-toggle {
      transition: background-color var(--transition), border-color var(--transition), color var(--transition), box-shadow var(--transition), transform var(--transition);
    }

    button:hover,
    button:focus-visible,
    .btn:hover,
    .btn:focus-visible,
    .button:hover,
    .button:focus-visible {
      transform: translateY(-0.5px);
      box-shadow: 0 5px 12px rgba(7, 59, 45, 0.10);
    }

    .inline-link:hover,
    .inline-link:focus-visible,
    .password-toggle:hover,
    .password-toggle:focus-visible {
      transform: translateY(-0.5px);
    }

    body {
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1 0 auto;
    }

    .site-footer {
      flex: 0 0 auto;
      width: 100%;
      margin-top: auto;
      background: #f4faf7;
      border-top: 1px solid var(--line-200);
      color: var(--ink-700);
    }

    .site-footer-inner {
      width: min(1580px, calc(100% - 2rem));
      margin: 0 auto;
      padding: 1rem 0;
      font-size: 0.86rem;
    }

    body.auth-body .site-footer-inner {
      width: min(1200px, calc(100% - 2rem));
    }

    @media (max-width: 720px) {
      .site-footer-inner {
        line-height: 1.45;
      }
    }
  </style>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php
}

function portalRenderFooter(): void
{
    ?>
  <footer class="site-footer">
    <div class="site-footer-inner">
      &copy; 2026 School Records Database. Built for students, teachers, and administrators.
    </div>
  </footer>
  <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true"></div>
  <script>
    (() => {
      const nav = document.querySelector('.navbar, .public-nav');
      if (!nav) {
        // Continue so the toast system still initializes on auth/public pages.
      }
      const syncNavScroll = () => {
        if (!nav) {
          return;
        }
        const isScrolled = window.scrollY > 6;
        nav.classList.toggle('is-scrolled', isScrolled);
        document.body.classList.toggle('nav-scrolled', isScrolled);
      };
      syncNavScroll();

      const toastRegion = document.getElementById('toast-region');
      const toastTimers = new WeakMap();
      const observedMessages = new WeakMap();
      const pendingMessageSync = new WeakMap();

      function normalizeToastType(value) {
        const normalized = String(value || '').toLowerCase();
        if (normalized === 'success' || normalized === 'error' || normalized === 'warning' || normalized === 'info') {
          return normalized;
        }
        return 'info';
      }

      function showToast(message, type = 'info', duration = 3500) {
        if (!toastRegion) {
          return;
        }

        const text = String(message || '').trim();
        if (!text) {
          return;
        }

        const toast = document.createElement('div');
        const toastType = normalizeToastType(type);
        toast.className = 'toast toast-' + toastType;
        toast.setAttribute('role', toastType === 'error' ? 'alert' : 'status');

        const messageNode = document.createElement('div');
        messageNode.className = 'toast-message';
        messageNode.textContent = text;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'toast-close';
        closeButton.setAttribute('aria-label', 'Dismiss notification');
        closeButton.textContent = '×';
        closeButton.addEventListener('click', () => dismissToast(toast));

        toast.appendChild(messageNode);
        toast.appendChild(closeButton);
        toastRegion.appendChild(toast);

        const timerId = window.setTimeout(() => dismissToast(toast), duration);
        toastTimers.set(toast, timerId);
      }

      function dismissToast(toast) {
        const timerId = toastTimers.get(toast);
        if (timerId) {
          window.clearTimeout(timerId);
          toastTimers.delete(toast);
        }
        toast.remove();
      }

      function readMessageState(element) {
        const text = String(element.textContent || '').trim();
        const className = String(element.className || '');
        const type = className.includes('success') ? 'success' : className.includes('error') ? 'error' : className.includes('warning') ? 'warning' : 'info';
        return { text, type, signature: text + '|' + type };
      }

      function observeMessageElement(element) {
        if (!(element instanceof HTMLElement) || observedMessages.has(element)) {
          return;
        }

        let lastSignature = '';
        const sync = () => {
          pendingMessageSync.delete(element);
          const state = readMessageState(element);
          if (!state.text || state.signature === lastSignature) {
            return;
          }
          lastSignature = state.signature;
          showToast(state.text, state.type);
        };

        const scheduleSync = () => {
          if (pendingMessageSync.get(element)) {
            return;
          }
          pendingMessageSync.set(element, true);
          window.requestAnimationFrame(sync);
        };

        observedMessages.set(element, true);
        new MutationObserver(scheduleSync).observe(element, {
          childList: true,
          characterData: true,
          subtree: true,
          attributes: true,
          attributeFilter: ['class'],
        });

        scheduleSync();
      }

      document.querySelectorAll('.message').forEach(observeMessageElement);

      new MutationObserver(mutations => {
        for (const mutation of mutations) {
          mutation.addedNodes.forEach(node => {
            if (!(node instanceof HTMLElement)) {
              return;
            }
            if (node.matches('.message')) {
              observeMessageElement(node);
            }
            node.querySelectorAll?.('.message').forEach(observeMessageElement);
          });
        }
      }).observe(document.body, { childList: true, subtree: true });

      const flashToasts = <?php echo json_encode(function_exists('consumeFlashToasts') ? consumeFlashToasts() : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
      flashToasts.forEach(toast => showToast(toast.message, toast.type));

      window.portalToast = { show: showToast, dismiss: dismissToast };
      window.showToast = showToast;

      window.addEventListener('scroll', syncNavScroll, { passive: true });
    })();
  </script>
</body>
</html><?php
}

function portalRenderPublicStart(string $title): void
{
    portalRenderHead($title, 'public-body');
    ?>
  <main class="public-shell">
<?php
}

function portalRenderPublicEnd(): void
{
    echo "  </main>\n";
    portalRenderFooter();
}

function portalRenderAuthStart(
    string $title,
    string $eyebrow,
    string $heading,
    string $copy
): void {
    portalRenderHead($title, 'auth-body');
    ?>
  <main class="auth-shell">
    <div class="auth-masthead">
      <a class="brand" href="index.php">
        <div class="brand-mark"><img src="public/uploads/44f71a208c5b1c5ff972166d1ea9d26b.jpg" alt="" /></div>
        <div class="brand-text">
          <strong>School Records Database</strong>
          <span>Academic Data Management</span>
        </div>
      </a>
      <span class="role-pill">Academic Access</span>
    </div>
    <section class="auth-grid" style="margin-bottom:0;">
      <div class="auth-card">
        <div>
          <p class="eyebrow"><?php echo htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
          <h1 style="margin-top:0.45rem;"><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="auth-copy" style="margin-top:0.75rem;"><?php echo htmlspecialchars($copy, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
<?php
}

function portalRenderAuthAside(string $title, string $copy, array $items): void
{
    ?>
      </div>
      <aside class="auth-panel">
        <div>
          <p class="eyebrow">Unified Experience</p>
          <h2 style="margin-top:0.45rem;"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
          <p class="auth-copy" style="margin-top:0.6rem;"><?php echo htmlspecialchars($copy, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="auth-list">
          <?php foreach ($items as $item): ?>
            <div class="auth-list-item">
              <strong><?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
              <span><?php echo htmlspecialchars((string) ($item['copy'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </aside>
    </section>
<?php
}

function portalRenderAuthEnd(): void
{
    echo "  </main>\n";
    portalRenderFooter();
}

function portalRenderStart(string $title, string $currentKey, string $email, string $role, string $heading, string $subheading = ''): void
{
    $navItems = portalNavItems($role);
    $metaItems = portalProfileMetaItems($email, $role);
    portalRenderHead($title, 'app-shell-body');
    ?>
  <main class="shell">
    <nav class="navbar">
      <a class="brand" href="index.php?page=home">
        <div class="brand-mark"><img src="public/uploads/44f71a208c5b1c5ff972166d1ea9d26b.jpg" alt="" /></div>
        <div class="brand-text">
          <strong>School Records Database</strong>
          <span>Academic Data Management</span>
        </div>
      </a>
      <input class="nav-toggle" type="checkbox" id="app-nav-toggle" aria-label="Toggle navigation" />
      <label class="nav-toggle-button" for="app-nav-toggle" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
      </label>
      <div class="nav-menu">
        <div class="nav-links">
          <?php foreach ($navItems as $item): ?>
            <a class="nav-link<?php echo $item['key'] === $currentKey ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="nav-side">
          <span class="role-pill"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></span>
          <a class="btn btn-secondary" href="index.php?page=logout">Logout</a>
        </div>
      </div>
    </nav>

    <section class="hero" style="padding:0; background:transparent; border:none; box-shadow:none; margin-bottom:1.1rem;">
      <div class="hero-card">
        <p class="hero-kicker">Academic Workspace</p>
        <h1><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-lead"><?php echo htmlspecialchars($subheading, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div class="hero-meta">
        <?php foreach ($metaItems as $meta): ?>
          <div>
            <span class="meta-label"><?php echo htmlspecialchars((string) ($meta['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="meta-value"><?php echo htmlspecialchars((string) ($meta['value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
<?php
}

function portalRenderEnd(): void
{
    echo "  </main>\n";
    portalRenderFooter();
}
