<?php
declare(strict_types=1);

require_once __DIR__ . '/src/core/security.php';
ensureSessionStarted();

$page = (string) ($_GET['page'] ?? '');

$routes = [
    '' => __DIR__ . '/landing.php',
    'home' => __DIR__ . '/src/pages/homepage.php',
    'login' => __DIR__ . '/src/pages/login.php',
    'register' => __DIR__ . '/src/pages/register.php',
    'forgot_password' => __DIR__ . '/src/pages/forgot_password.php',
    'reset_password' => __DIR__ . '/src/pages/reset_password.php',
    'logout' => __DIR__ . '/src/pages/logout.php',
    'api' => __DIR__ . '/src/actions/api.php',
    'admin' => __DIR__ . '/src/pages/admin.php',
    'admin_courses' => __DIR__ . '/src/pages/admin_courses.php',
    'admin_students' => __DIR__ . '/src/pages/admin_students.php',
    'admin_staff' => __DIR__ . '/src/pages/admin_staff.php',
    'admin_users' => __DIR__ . '/src/pages/admin_users.php',
    'teacher_dashboard' => __DIR__ . '/src/pages/teacher_dashboard.php',
    'teacher_queue' => __DIR__ . '/src/pages/teacher_queue.php',
    'teacher_history' => __DIR__ . '/src/pages/teacher_history.php',
    'teacher_placement' => __DIR__ . '/src/pages/teacher_placement.php',
    'student' => __DIR__ . '/src/pages/student.php',
    'student_calculator' => __DIR__ . '/src/pages/student_calculator.php',
    'student_submit' => __DIR__ . '/src/pages/student_submit.php',
    'student_history' => __DIR__ . '/src/pages/student_history.php',
    'student_prospectus' => __DIR__ . '/src/pages/student_prospectus.php',
];

// Redirect logged-in users away from landing to homepage/dashboard
if ($page === '' && isset($_SESSION['user_id'])) {
    header('Location: index.php?page=home');
    exit;
}

if (!isset($routes[$page])) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}

$target = $routes[$page];
if (!is_file($target)) {
    http_response_code(500);
    echo 'Route target is missing.';
    exit;
}

require $target;
