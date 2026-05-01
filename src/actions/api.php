<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/security.php';
ensureSessionStarted();

// Start buffering to catch any accidental output (notices/warnings)
ob_start();

header('Content-Type: application/json; charset=utf-8');

function flushJsonError(string $message, int $statusCode = 500): void
{
    if (ob_get_level() > 0) {
        ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
    }
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

set_exception_handler(static function (Throwable $throwable): void {
    error_log((string) $throwable);
    flushJsonError('Server error: ' . $throwable->getMessage(), 500);
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!is_array($error)) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    error_log('API fatal error: ' . ($error['message'] ?? 'Unknown fatal error'));
    flushJsonError('Server error: ' . (string) ($error['message'] ?? 'Unknown fatal error'), 500);
});

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../core/db.php';

$userId = (int) $_SESSION['user_id'];
$userRole = (string) ($_SESSION['role'] ?? 'student');
$allowedGrades = ['1.00', '1.25', '1.50', '1.75', '2.00', '2.25', '2.50', '2.75', '3.00', '5.00'];
$allowedYearLevels = [1, 2, 3, 4];
$allowedSemesters = ['1st Semester', '2nd Semester'];

function jsonResponse(array $payload, int $statusCode = 200): void
{
    // Clear any buffered output (notices) before sending the JSON
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function requireRole(array $roles, string $currentRole): void
{
    if (!in_array($currentRole, $roles, true)) {
        jsonResponse(['ok' => false, 'error' => 'Forbidden'], 403);
    }
}

function requireMethod(string $expected): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== strtoupper($expected)) {
        jsonResponse(['ok' => false, 'error' => 'Method Not Allowed'], 405);
    }
}

function requireCsrfForMutation(): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $token = requestCsrfToken();
    if (!validateCsrfToken($token)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid CSRF token'], 419);
    }
}

function getInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '{}', true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

function yearLevelLabel(int $yearLevel): string
{
    $labels = [
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
    ];

    return $labels[$yearLevel] ?? ($yearLevel . 'th Year');
}

function buildAcademicTerm(int $yearLevel, string $semester): string
{
    return yearLevelLabel($yearLevel) . ' - ' . $semester;
}

function fetchUserProfile(PDO $pdo, int $targetUserId): array
{
    $stmt = $pdo->prepare(
        'SELECT u.id, u.email, u.role, u.full_name, u.program_id, u.current_year_level, u.current_semester, u.staff_affiliation,
                p.name AS program_name
         FROM users u
         LEFT JOIN programs p ON p.id = u.program_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $targetUserId]);
    $row = $stmt->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('User profile not found.');
    }

    $yearLevel = isset($row['current_year_level']) ? (int) $row['current_year_level'] : 0;
    $semester = is_string($row['current_semester'] ?? null) ? trim((string) $row['current_semester']) : '';

    return [
        'id' => (int) $row['id'],
        'email' => (string) ($row['email'] ?? ''),
        'role' => (string) ($row['role'] ?? ''),
        'full_name' => trim((string) ($row['full_name'] ?? '')),
        'program_id' => isset($row['program_id']) ? (int) $row['program_id'] : 0,
        'program_name' => (string) ($row['program_name'] ?? ''),
        'current_year_level' => $yearLevel,
        'current_semester' => $semester,
        'staff_affiliation' => trim((string) ($row['staff_affiliation'] ?? '')),
        'current_term' => ($yearLevel > 0 && $semester !== '') ? buildAcademicTerm($yearLevel, $semester) : '',
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'GET' ? ($_GET['action'] ?? '') : ((getInput()['action'] ?? ''));
$input = $method === 'GET' ? $_GET : getInput();

if ($action === 'bootstrap') {
    $programs = $pdo->query('SELECT id, name FROM programs ORDER BY name ASC')->fetchAll();
    $courseSelect = 'c.id, c.code, c.title, c.units, c.credit_lec, c.credit_lab, c.contact_lec, c.contact_lab, c.prerequisite, c.semester, c.program_id, p.name AS program_name';
    $courses = $pdo->query('SELECT ' . $courseSelect . ' FROM courses c JOIN programs p ON p.id = c.program_id ORDER BY p.name ASC, c.semester ASC, c.code ASC')->fetchAll();
    $profile = fetchUserProfile($pdo, $userId);

    jsonResponse([
        'ok' => true,
        'user' => [
            'id' => $userId,
            'role' => $userRole,
            'email' => (string) ($_SESSION['email'] ?? ''),
            'profile' => $profile,
        ],
        'programs' => $programs,
        'courses' => $courses,
    ]);
}

if ($action === 'get_courses') {
    $programId = (int) ($input['program_id'] ?? 0);
    $semester = trim((string) ($input['semester'] ?? ''));
    $courseSelect = 'id, code, title, units, credit_lec, credit_lab, contact_lec, contact_lab, prerequisite, semester, program_id';
    
    if (!$programId) {
        jsonResponse(['ok' => false, 'error' => 'Program ID is required'], 400);
    }
    
    try {
        if ($semester) {
            $stmt = $pdo->prepare('SELECT ' . $courseSelect . ' FROM courses WHERE program_id = :program_id AND semester = :semester ORDER BY code ASC');
            $stmt->execute(['program_id' => $programId, 'semester' => $semester]);
        } else {
            // If no semester specified, get all courses for the program
            $stmt = $pdo->prepare('SELECT ' . $courseSelect . ' FROM courses WHERE program_id = :program_id ORDER BY semester ASC, code ASC');
            $stmt->execute(['program_id' => $programId]);
        }
        $courses = $stmt->fetchAll();
    } catch (PDOException $e) {
        jsonResponse(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
    
    jsonResponse([
        'ok' => true,
        'courses' => $courses,
    ]);
}

if ($action === 'add_program') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['admin'], $userRole);
    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '') {
        jsonResponse(['ok' => false, 'error' => 'Program name is required'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO programs (name) VALUES (:name)');
    try {
        $stmt->execute(['name' => $name]);
    } catch (PDOException $e) {
        jsonResponse(['ok' => false, 'error' => 'Program already exists or invalid'], 400);
    }

    jsonResponse(['ok' => true]);
}

if ($action === 'add_course') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['admin'], $userRole);

    $programId = (int) ($input['program_id'] ?? 0);
    $code = strtoupper(trim((string) ($input['code'] ?? '')));
    $title = trim((string) ($input['title'] ?? ''));
    $units = (int) ($input['units'] ?? 3);
    $semester = trim((string) ($input['semester'] ?? '1st Year - 1st Semester'));

    if ($programId <= 0 || $code === '' || $title === '' || $units <= 0) {
        jsonResponse(['ok' => false, 'error' => 'All course fields are required'], 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO courses (program_id, code, title, units, credit_lec, credit_lab, contact_lec, contact_lab, semester)
         VALUES (:program_id, :code, :title, :units, :credit_lec, 0, :contact_lec, 0, :semester)'
    );
    try {
        $stmt->execute([
            'program_id' => $programId,
            'code' => $code,
            'title' => $title,
            'units' => $units,
            'credit_lec' => $units,
            'contact_lec' => $units,
            'semester' => $semester,
        ]);
    } catch (PDOException $e) {
        jsonResponse(['ok' => false, 'error' => 'Course code already exists or invalid'], 400);
    }

    jsonResponse(['ok' => true]);
}

if ($action === 'list_users') {
    requireRole(['admin'], $userRole);

    $roleFilter = trim((string) ($input['role'] ?? ''));
    $allowedRoleFilters = ['student', 'teacher', 'admin', 'staff'];
    if ($roleFilter !== '' && !in_array($roleFilter, $allowedRoleFilters, true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid role filter'], 400);
    }

    $sql = 'SELECT u.id, u.email, u.role, u.full_name, u.program_id, u.current_year_level, u.current_semester, u.staff_affiliation,
                   p.name AS program_name, u.created_at
            FROM users u
            LEFT JOIN programs p ON p.id = u.program_id';
    $params = [];

    if ($roleFilter === 'student') {
        $sql .= ' WHERE u.role = :student_role';
        $params['student_role'] = 'student';
    } elseif ($roleFilter === 'staff') {
        $sql .= ' WHERE u.role IN ("teacher", "admin")';
    } elseif ($roleFilter !== '') {
        $sql .= ' WHERE u.role = :role';
        $params['role'] = $roleFilter;
    }

    $sql .= ' ORDER BY u.created_at DESC, u.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $users = $stmt->fetchAll();

    jsonResponse(['ok' => true, 'users' => $users]);
}

if ($action === 'list_student_profiles') {
    requireRole(['teacher', 'admin'], $userRole);

    $students = $pdo->query(
        'SELECT u.id, u.email, u.full_name, u.program_id, u.current_year_level, u.current_semester,
                p.name AS program_name, u.created_at
         FROM users u
         LEFT JOIN programs p ON p.id = u.program_id
         WHERE u.role = "student"
         ORDER BY COALESCE(u.full_name, u.email) ASC, u.id ASC'
    )->fetchAll();

    jsonResponse(['ok' => true, 'students' => $students]);
}

if ($action === 'update_user_role') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['admin'], $userRole);

    $targetUserId = (int) ($input['user_id'] ?? 0);
    $newRole = (string) ($input['role'] ?? '');
    $allowedRoles = ['student', 'teacher', 'admin'];

    if ($targetUserId <= 0 || !in_array($newRole, $allowedRoles, true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid user role payload'], 400);
    }

    if ($targetUserId === $userId && $newRole !== 'admin') {
        jsonResponse(['ok' => false, 'error' => 'You cannot demote your own admin account'], 400);
    }

    $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
    $stmt->execute([
        'role' => $newRole,
        'id' => $targetUserId,
    ]);

    jsonResponse(['ok' => true]);
}

if ($action === 'update_staff_affiliation') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['admin'], $userRole);

    $targetUserId = (int) ($input['user_id'] ?? 0);
    $staffAffiliation = trim((string) ($input['staff_affiliation'] ?? ''));
    if ($targetUserId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Invalid staff payload'], 400);
    }

    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $roleStmt->execute(['id' => $targetUserId]);
    $targetRole = $roleStmt->fetchColumn();
    if (!in_array($targetRole, ['teacher', 'admin'], true)) {
        jsonResponse(['ok' => false, 'error' => 'Only teacher or admin accounts can be updated here.'], 400);
    }

    $stmt = $pdo->prepare('UPDATE users SET staff_affiliation = :staff_affiliation WHERE id = :id');
    $stmt->execute([
        'staff_affiliation' => $staffAffiliation,
        'id' => $targetUserId,
    ]);

    jsonResponse([
        'ok' => true,
        'profile' => fetchUserProfile($pdo, $targetUserId),
    ]);
}

if ($action === 'update_student_profile') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['teacher', 'admin'], $userRole);

    $targetUserId = (int) ($input['user_id'] ?? 0);
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $programId = (int) ($input['program_id'] ?? 0);
    $yearLevel = (int) ($input['current_year_level'] ?? 0);
    $semester = trim((string) ($input['current_semester'] ?? ''));

    if ($targetUserId <= 0 || $fullName === '' || $programId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Student name and program are required.'], 400);
    }

    if (!in_array($yearLevel, $allowedYearLevels, true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid year level.'], 400);
    }

    if (!in_array($semester, $allowedSemesters, true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid semester.'], 400);
    }

    $roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $roleStmt->execute(['id' => $targetUserId]);
    $targetRole = $roleStmt->fetchColumn();
    if ($targetRole !== 'student') {
        jsonResponse(['ok' => false, 'error' => 'Only student records can be updated here.'], 400);
    }

    $programStmt = $pdo->prepare('SELECT id FROM programs WHERE id = :id LIMIT 1');
    $programStmt->execute(['id' => $programId]);
    if (!$programStmt->fetchColumn()) {
        jsonResponse(['ok' => false, 'error' => 'Selected program was not found.'], 400);
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET full_name = :full_name,
             program_id = :program_id,
             current_year_level = :current_year_level,
             current_semester = :current_semester
         WHERE id = :id'
    );
    $stmt->execute([
        'full_name' => $fullName,
        'program_id' => $programId,
        'current_year_level' => $yearLevel,
        'current_semester' => $semester,
        'id' => $targetUserId,
    ]);

    jsonResponse([
        'ok' => true,
        'profile' => fetchUserProfile($pdo, $targetUserId),
    ]);
}

if ($action === 'submit_grades') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['student'], $userRole);

    $grades = $input['grades'] ?? [];
    $profile = fetchUserProfile($pdo, $userId);
    $studentName = $profile['full_name'];
    $programId = (int) $profile['program_id'];
    $semester = $profile['current_term'];

    if ($studentName === '' || $programId <= 0 || $semester === '') {
        jsonResponse(['ok' => false, 'error' => 'Your student profile is incomplete. Ask an admin or teacher to assign your program and term.'], 400);
    }

    if (!is_array($grades) || count($grades) === 0) {
        jsonResponse(['ok' => false, 'error' => 'Load your assigned courses before submitting grades.'], 400);
    }

    $archiveExisting = $pdo->prepare(
        'INSERT INTO grade_submission_history (
            original_submission_id, student_name, program_id, course_id, grade, semester, status,
            submitted_by, reviewed_by, original_created_at, original_updated_at
         )
         SELECT id, student_name, program_id, course_id, grade, semester, status,
                submitted_by, reviewed_by, created_at, updated_at
         FROM grade_submissions
         WHERE submitted_by = :submitted_by
           AND course_id = :course_id
           AND semester = :semester
           AND status IN ("approved", "rejected")
         LIMIT 1'
    );

    $upsert = $pdo->prepare(
        'INSERT INTO grade_submissions (student_name, program_id, course_id, grade, semester, status, submitted_by, reviewed_by)
         VALUES (:student_name, :program_id, :course_id, :grade, :semester, "pending", :submitted_by, NULL)
         ON DUPLICATE KEY UPDATE
            grade = VALUES(grade),
            status = "pending",
            submitted_by = VALUES(submitted_by),
            reviewed_by = NULL,
            updated_at = CURRENT_TIMESTAMP'
    );

    try {
        $pdo->beginTransaction();

        foreach ($grades as $item) {
            $courseId = (int) ($item['course_id'] ?? 0);
            $grade = (float) ($item['grade'] ?? 0);
            $gradeNormalized = number_format($grade, 2, '.', '');
            if ($courseId <= 0 || !in_array($gradeNormalized, $allowedGrades, true)) {
                $pdo->rollBack();
                jsonResponse(['ok' => false, 'error' => 'Invalid grade payload'], 400);
            }

            $archiveExisting->execute([
                'submitted_by' => $userId,
                'course_id' => $courseId,
                'semester' => $semester,
            ]);

            $upsert->execute([
                'student_name' => $studentName,
                'program_id' => $programId,
                'course_id' => $courseId,
                'grade' => $gradeNormalized,
                'semester' => $semester,
                'submitted_by' => $userId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['ok' => false, 'error' => 'Failed to submit grades'], 500);
    }

    jsonResponse(['ok' => true]);
}

if ($action === 'student_grade_notice') {
    requireRole(['student'], $userRole);

    $stmt = $pdo->prepare(
        'SELECT gs.status, gs.grade, gs.semester, gs.updated_at, c.code, c.title
         FROM grade_submissions gs
         JOIN courses c ON c.id = gs.course_id
         WHERE gs.submitted_by = :submitted_by
           AND gs.status IN ("approved", "rejected")
         ORDER BY gs.updated_at DESC, gs.id DESC
         LIMIT 1'
    );
    $stmt->execute(['submitted_by' => $userId]);
    $notice = $stmt->fetch();

    jsonResponse([
        'ok' => true,
        'notice' => is_array($notice) ? $notice : null,
    ]);
}

if ($action === 'student_grade_history') {
    requireRole(['student'], $userRole);

    $stmt = $pdo->prepare(
        'SELECT status, grade, semester, created_at, updated_at, course_code, course_title, program_name, record_type
         FROM (
            SELECT gs.status, gs.grade, gs.semester, gs.created_at, gs.updated_at,
                   c.code AS course_code, c.title AS course_title, p.name AS program_name,
                   "current" AS record_type, gs.updated_at AS sort_date, gs.id AS sort_id
            FROM grade_submissions gs
            JOIN courses c ON c.id = gs.course_id
            JOIN programs p ON p.id = gs.program_id
            WHERE gs.submitted_by = :current_submitted_by

            UNION ALL

            SELECT gsh.status, gsh.grade, gsh.semester,
                   gsh.original_created_at AS created_at, gsh.original_updated_at AS updated_at,
                   c.code AS course_code, c.title AS course_title, p.name AS program_name,
                   "previous" AS record_type, gsh.archived_at AS sort_date, gsh.id AS sort_id
            FROM grade_submission_history gsh
            JOIN courses c ON c.id = gsh.course_id
            JOIN programs p ON p.id = gsh.program_id
            WHERE gsh.submitted_by = :history_submitted_by
         ) history_rows
         ORDER BY sort_date DESC, sort_id DESC'
    );
    $stmt->execute([
        'current_submitted_by' => $userId,
        'history_submitted_by' => $userId,
    ]);

    jsonResponse([
        'ok' => true,
        'items' => $stmt->fetchAll(),
    ]);
}

if ($action === 'teacher_grade_history') {
    requireRole(['teacher', 'admin'], $userRole);

    $stmt = $pdo->query(
        'SELECT status, grade, semester, created_at, updated_at, student_name, course_code, course_title, program_name, record_type
         FROM (
            SELECT gs.status, gs.grade, gs.semester, gs.created_at, gs.updated_at,
                   gs.student_name, c.code AS course_code, c.title AS course_title, p.name AS program_name,
                   "current" AS record_type, gs.updated_at AS sort_date, gs.id AS sort_id
            FROM grade_submissions gs
            JOIN courses c ON c.id = gs.course_id
            JOIN programs p ON p.id = gs.program_id

            UNION ALL

            SELECT gsh.status, gsh.grade, gsh.semester,
                   gsh.original_created_at AS created_at, gsh.original_updated_at AS updated_at,
                   gsh.student_name, c.code AS course_code, c.title AS course_title, p.name AS program_name,
                   "previous" AS record_type, gsh.archived_at AS sort_date, gsh.id AS sort_id
            FROM grade_submission_history gsh
            JOIN courses c ON c.id = gsh.course_id
            JOIN programs p ON p.id = gsh.program_id
         ) history_rows
         ORDER BY sort_date DESC, sort_id DESC'
    );

    jsonResponse([
        'ok' => true,
        'items' => $stmt->fetchAll(),
    ]);
}

if ($action === 'teacher_queue') {
    requireRole(['teacher', 'admin'], $userRole);

    $rows = $pdo->query(
        'SELECT gs.id, gs.student_name, p.name AS program_name, c.code AS course_code, c.title AS course_title, c.units, gs.grade, gs.semester, gs.status, gs.created_at
         FROM grade_submissions gs
         JOIN programs p ON p.id = gs.program_id
         JOIN courses c ON c.id = gs.course_id
         ORDER BY FIELD(gs.status, "pending", "rejected", "approved"), gs.created_at DESC'
    )->fetchAll();

    jsonResponse(['ok' => true, 'items' => $rows]);
}

if ($action === 'review_submission') {
    requireMethod('POST');
    requireCsrfForMutation();
    requireRole(['teacher', 'admin'], $userRole);

    $id = (int) ($input['id'] ?? 0);
    $status = (string) ($input['status'] ?? '');
    if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid review payload'], 400);
    }

    $stmt = $pdo->prepare('UPDATE grade_submissions SET status = :status, reviewed_by = :reviewed_by WHERE id = :id');
    $stmt->execute([
        'status' => $status,
        'reviewed_by' => $userId,
        'id' => $id,
    ]);

    jsonResponse(['ok' => true]);
}

if ($action === 'teacher_dashboard') {
    requireRole(['teacher', 'admin'], $userRole);

    $statusCounts = $pdo->query(
        'SELECT status, COUNT(*) AS count_total
         FROM grade_submissions
         GROUP BY status'
    )->fetchAll();

    $studentOverview = $pdo->query(
        'SELECT gs.student_name,
                p.name AS program_name,
                COUNT(*) AS total_submissions,
                SUM(CASE WHEN gs.status = "approved" THEN 1 ELSE 0 END) AS approved_submissions,
                SUM(CASE WHEN gs.status = "pending" THEN 1 ELSE 0 END) AS pending_submissions
         FROM grade_submissions gs
         JOIN programs p ON p.id = gs.program_id
         GROUP BY gs.student_name, p.name
         ORDER BY pending_submissions DESC, total_submissions DESC, gs.student_name ASC'
    )->fetchAll();

    $counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
    ];

    foreach ($statusCounts as $row) {
        $status = (string) ($row['status'] ?? '');
        if (isset($counts[$status])) {
            $counts[$status] = (int) ($row['count_total'] ?? 0);
        }
    }

    jsonResponse([
        'ok' => true,
        'counts' => $counts,
        'students' => $studentOverview,
    ]);
}

if ($action === 'student_gwa') {
    requireRole(['student'], $userRole);

    $rows = $pdo->prepare(
        'SELECT gs.student_name, p.id AS program_id, p.name AS program_name, gs.semester,
                c.code, c.title, c.units, gs.grade,
                ROUND(SUM(gs.grade * c.units) / NULLIF(SUM(c.units), 0), 2) AS gwa,
                COUNT(*) AS approved_count
         FROM grade_submissions gs
         JOIN courses c ON c.id = gs.course_id
         JOIN programs p ON p.id = gs.program_id
         WHERE gs.status = "approved" AND gs.submitted_by = :submitted_by
         GROUP BY gs.id
         ORDER BY gs.semester ASC, c.code ASC'
    );
    $rows->execute(['submitted_by' => $userId]);

    jsonResponse(['ok' => true, 'rows' => $rows->fetchAll()]);
}

if ($action === 'gwa_summary') {
    $summaryRows = $pdo->query(
        'SELECT gs.student_name, p.name AS program_name,
                ROUND(SUM(gs.grade * c.units) / NULLIF(SUM(c.units), 0), 2) AS gwa,
                COUNT(*) AS approved_count
         FROM grade_submissions gs
         JOIN courses c ON c.id = gs.course_id
         JOIN programs p ON p.id = gs.program_id
         WHERE gs.status = "approved"
         GROUP BY gs.student_name, p.name
         ORDER BY gs.student_name ASC, p.name ASC'
    )->fetchAll();

    $courseRows = $pdo->query(
        'SELECT gs.student_name, p.name AS program_name, c.code, c.title, c.units, gs.grade
         FROM grade_submissions gs
         JOIN courses c ON c.id = gs.course_id
         JOIN programs p ON p.id = gs.program_id
         WHERE gs.status = "approved"
         ORDER BY gs.student_name ASC, p.name ASC, c.code ASC'
    )->fetchAll();

    $coursesByStudent = [];
    foreach ($courseRows as $courseRow) {
        $key = $courseRow['student_name'] . '|' . $courseRow['program_name'];
        if (!isset($coursesByStudent[$key])) {
            $coursesByStudent[$key] = [];
        }
        $coursesByStudent[$key][] = $courseRow;
    }

    foreach ($summaryRows as &$summary) {
        $key = $summary['student_name'] . '|' . $summary['program_name'];
        $summary['courses'] = $coursesByStudent[$key] ?? [];
    }
    unset($summary);

    jsonResponse(['ok' => true, 'summary' => $summaryRows]);
}

jsonResponse(['ok' => false, 'error' => 'Invalid action'], 400);
