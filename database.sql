CREATE DATABASE IF NOT EXISTS ewan_db;
USE ewan_db;

CREATE TABLE IF NOT EXISTS users (
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
);

CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS grade_submissions (
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
);

CREATE TABLE IF NOT EXISTS grade_submission_history (
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
);
