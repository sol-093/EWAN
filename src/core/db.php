<?php
declare(strict_types=1);

$host = 'localhost';
$dbName = 'ewan_db';
$dbUser = 'root';
$dbPass = '';

$dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // 1049 means unknown database. Create it on first run and retry.
    $mysqlErrorCode = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
    if ($mysqlErrorCode === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
        try {
            $setupPdo = new PDO("mysql:host=$host;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $setupPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $setupPdo->exec("USE `$dbName`");
            $setupPdo->exec(
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
                    full_name VARCHAR(150) NULL,
                    program_id INT NULL,
                    current_year_level TINYINT NULL,
                    current_semester VARCHAR(20) NULL,
                    staff_affiliation VARCHAR(180) NULL,
                    account_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
                    verified_by INT NULL,
                    verified_at TIMESTAMP NULL,
                    reset_token_hash VARCHAR(255) NULL,
                    reset_token_expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            );

            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $setupError) {
            throw new RuntimeException('Database setup failed: ' . $setupError->getMessage(), 0, $setupError);
        }
    } else {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
        full_name VARCHAR(150) NULL,
        program_id INT NULL,
        current_year_level TINYINT NULL,
        current_semester VARCHAR(20) NULL,
        staff_affiliation VARCHAR(180) NULL,
        account_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        reset_token_hash VARCHAR(255) NULL,
        reset_token_expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

$userColumnMigrations = [
    "ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER role",
    "ALTER TABLE users ADD COLUMN program_id INT NULL AFTER full_name",
    "ALTER TABLE users ADD COLUMN current_year_level TINYINT NULL AFTER program_id",
    "ALTER TABLE users ADD COLUMN current_semester VARCHAR(20) NULL AFTER current_year_level",
    "ALTER TABLE users ADD COLUMN staff_affiliation VARCHAR(180) NULL AFTER current_semester",
    "ALTER TABLE users ADD COLUMN account_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'verified' AFTER staff_affiliation",
    "ALTER TABLE users ADD COLUMN verified_by INT NULL AFTER account_status",
    "ALTER TABLE users ADD COLUMN verified_at TIMESTAMP NULL AFTER verified_by",
    "ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(255) NULL AFTER verified_at",
    "ALTER TABLE users ADD COLUMN reset_token_expires_at TIMESTAMP NULL AFTER reset_token_hash",
];

foreach ($userColumnMigrations as $statement) {
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), '1060')) {
            throw $e;
        }
    }
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT NOT NULL,
        code VARCHAR(30) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        units INT NOT NULL DEFAULT 3,
        credit_lec INT NOT NULL DEFAULT 3,
        credit_lab INT NOT NULL DEFAULT 0,
        contact_lec INT NOT NULL DEFAULT 3,
        contact_lab INT NOT NULL DEFAULT 0,
        prerequisite VARCHAR(255) NULL,
        semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
    )"
);

// Ensure semester column exists (silent migration for existing databases)
try {
    $pdo->exec("ALTER TABLE courses ADD COLUMN semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester'");
} catch (PDOException $e) {
    // Ignore "Duplicate column name" error (SQLSTATE 42S21 or error code 1060)
    if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), '1060')) {
        throw $e;
    }
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS grade_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(150) NOT NULL,
        program_id INT NOT NULL,
        course_id INT NOT NULL,
        grade DECIMAL(3,2) NOT NULL,
        semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester',
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        teacher_feedback TEXT NULL,
        appeal_message TEXT NULL,
        appeal_status ENUM('none', 'pending', 'resolved') NOT NULL DEFAULT 'none',
        submitted_by INT NOT NULL,
        reviewed_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_course_term (student_name, course_id, semester),
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS grade_submission_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_submission_id INT NULL,
        student_name VARCHAR(150) NOT NULL,
        program_id INT NOT NULL,
        course_id INT NOT NULL,
        grade DECIMAL(3,2) NOT NULL,
        semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester',
        status ENUM('approved', 'rejected') NOT NULL,
        teacher_feedback TEXT NULL,
        appeal_message TEXT NULL,
        appeal_status ENUM('none', 'pending', 'resolved') NOT NULL DEFAULT 'none',
        submitted_by INT NOT NULL,
        reviewed_by INT NULL,
        original_created_at TIMESTAMP NULL,
        original_updated_at TIMESTAMP NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX history_student_idx (submitted_by, archived_at),
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    )"
);

// Ensure semester column exists and matches current term labels for existing databases
try {
    $pdo->exec("ALTER TABLE grade_submissions ADD COLUMN semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester'");
} catch (PDOException $e) {
    if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), '1060')) {
        throw $e;
    }
}

$courseColumnMigrations = [
    "ALTER TABLE courses ADD COLUMN credit_lec INT NOT NULL DEFAULT 3 AFTER units",
    "ALTER TABLE courses ADD COLUMN credit_lab INT NOT NULL DEFAULT 0 AFTER credit_lec",
    "ALTER TABLE courses ADD COLUMN contact_lec INT NOT NULL DEFAULT 3 AFTER credit_lab",
    "ALTER TABLE courses ADD COLUMN contact_lab INT NOT NULL DEFAULT 0 AFTER contact_lec",
    "ALTER TABLE courses ADD COLUMN prerequisite VARCHAR(255) NULL AFTER contact_lab",
];

foreach ($courseColumnMigrations as $statement) {
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), '1060')) {
            throw $e;
        }
    }
}

$gradeSubmissionColumnMigrations = [
    "ALTER TABLE grade_submissions ADD COLUMN teacher_feedback TEXT NULL AFTER status",
    "ALTER TABLE grade_submissions ADD COLUMN appeal_message TEXT NULL AFTER teacher_feedback",
    "ALTER TABLE grade_submissions ADD COLUMN appeal_status ENUM('none', 'pending', 'resolved') NOT NULL DEFAULT 'none' AFTER appeal_message",
    "ALTER TABLE grade_submission_history ADD COLUMN teacher_feedback TEXT NULL AFTER status",
    "ALTER TABLE grade_submission_history ADD COLUMN appeal_message TEXT NULL AFTER teacher_feedback",
    "ALTER TABLE grade_submission_history ADD COLUMN appeal_status ENUM('none', 'pending', 'resolved') NOT NULL DEFAULT 'none' AFTER appeal_message",
];

foreach ($gradeSubmissionColumnMigrations as $statement) {
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S21' && !str_contains($e->getMessage(), '1060')) {
            throw $e;
        }
    }
}

try {
    $pdo->exec("ALTER TABLE grade_submissions MODIFY COLUMN semester VARCHAR(30) NOT NULL DEFAULT '1st Year - 1st Semester'");
} catch (PDOException $e) {
    throw $e;
}

$defaultPrograms = [
    'Information System',
    'Computer Science',
    'Midwifery',
    'Nursing',
    'Civil Engineering',
    'Psychology',
    'Life Science',
];

$insertProgramStmt = $pdo->prepare('INSERT IGNORE INTO programs (name) VALUES (:name)');
foreach ($defaultPrograms as $programName) {
    $insertProgramStmt->execute(['name' => $programName]);
}

$programRows = $pdo->query('SELECT id, name FROM programs')->fetchAll();
$programMap = [];
foreach ($programRows as $programRow) {
    $programMap[$programRow['name']] = (int) $programRow['id'];
}

$defaultCourses = [
    // Information System
    ['Information System', 'IS111', 'Introduction to Computing', 3, '1st Year - 1st Semester'],
    ['Information System', 'IS112', 'Computer Programming 1', 3, '1st Year - 1st Semester'],
    ['Information System', 'IS121', 'Computer Programming 2', 3, '1st Year - 2nd Semester'],
    ['Information System', 'IS122', 'Discrete Mathematics', 3, '1st Year - 2nd Semester'],
    ['Information System', 'IS211', 'Database Management Systems 1', 3, '2nd Year - 1st Semester'],
    ['Information System', 'IS212', 'Web Systems and Technologies', 3, '2nd Year - 1st Semester'],
    ['Information System', 'IS221', 'Systems Analysis and Design', 3, '2nd Year - 2nd Semester'],
    ['Information System', 'IS222', 'Information Management', 3, '2nd Year - 2nd Semester'],

    // Computer Science
    ['Computer Science', 'CS111', 'Introduction to Computer Science', 3, '1st Year - 1st Semester'],
    ['Computer Science', 'CS112', 'Programming 1', 3, '1st Year - 1st Semester'],
    ['Computer Science', 'CS121', 'Programming 2', 3, '1st Year - 2nd Semester'],
    ['Computer Science', 'CS122', 'Discrete Structures 1', 3, '1st Year - 2nd Semester'],
    ['Computer Science', 'CS211', 'Data Structures and Algorithms', 4, '2nd Year - 1st Semester'],
    ['Computer Science', 'CS212', 'Object Oriented Programming', 3, '2nd Year - 1st Semester'],
    ['Computer Science', 'CS221', 'Algorithms and Complexity', 3, '2nd Year - 2nd Semester'],
    ['Computer Science', 'CS222', 'Computer Architecture', 3, '2nd Year - 2nd Semester'],

    // Nursing
    ['Nursing', 'NS111', 'Anatomy and Physiology', 5, '1st Year - 1st Semester'],
    ['Nursing', 'NS112', 'Biochemistry for Nursing', 3, '1st Year - 1st Semester'],
    ['Nursing', 'NS121', 'Theoretical Foundations in Nursing', 3, '1st Year - 2nd Semester'],
    ['Nursing', 'NS122', 'Health Assessment', 3, '1st Year - 2nd Semester'],
    ['Nursing', 'NS211', 'Fundamentals of Nursing Practice', 5, '2nd Year - 1st Semester'],
    ['Nursing', 'NS212', 'Community Health Nursing 1', 3, '2nd Year - 1st Semester'],

    // Midwifery
    ['Midwifery', 'MW111', 'Anatomy and Physiology', 3, '1st Year - 1st Semester'],
    ['Midwifery', 'MW112', 'Fundamentals of Midwifery', 4, '1st Year - 1st Semester'],
    ['Midwifery', 'MW121', 'Obstetrics 1', 4, '1st Year - 2nd Semester'],
    ['Midwifery', 'MW122', 'Maternal and Child Health', 3, '1st Year - 2nd Semester'],

    // Civil Engineering
    ['Civil Engineering', 'CE111', 'Calculus 1', 3, '1st Year - 1st Semester'],
    ['Civil Engineering', 'CE112', 'Engineering Drawing', 2, '1st Year - 1st Semester'],
    ['Civil Engineering', 'CE121', 'Calculus 2', 3, '1st Year - 2nd Semester'],
    ['Civil Engineering', 'CE122', 'Physics for Engineers', 4, '1st Year - 2nd Semester'],
    ['Civil Engineering', 'CE211', 'Statics of Rigid Bodies', 3, '2nd Year - 1st Semester'],
    ['Civil Engineering', 'CE212', 'Surveying 1', 4, '2nd Year - 1st Semester'],

    // Psychology
    ['Psychology', 'PY111', 'Introduction to Psychology', 3, '1st Year - 1st Semester'],
    ['Psychology', 'PY112', 'Psychological Statistics', 3, '1st Year - 1st Semester'],
    ['Psychology', 'PY121', 'Developmental Psychology', 3, '1st Year - 2nd Semester'],
    ['Psychology', 'PY122', 'Theories of Personality', 3, '1st Year - 2nd Semester'],

    // Life Science
    ['Life Science', 'LS111', 'General Biology 1', 4, '1st Year - 1st Semester'],
    ['Life Science', 'LS112', 'General Chemistry 1', 4, '1st Year - 1st Semester'],
    ['Life Science', 'LS121', 'General Biology 2', 4, '1st Year - 2nd Semester'],
    ['Life Science', 'LS122', 'General Chemistry 2', 4, '1st Year - 2nd Semester'],
];

$insertCourseStmt = $pdo->prepare(
    'INSERT IGNORE INTO courses (program_id, code, title, units, semester) VALUES (:program_id, :code, :title, :units, :semester)'
);

foreach ($defaultCourses as $course) {
    [$programName, $code, $title, $units, $semester] = $course;
    if (!isset($programMap[$programName])) {
        continue;
    }
    $insertCourseStmt->execute([
        'program_id' => $programMap[$programName],
        'code' => $code,
        'title' => $title,
        'units' => $units,
        'semester' => $semester
    ]);
}
