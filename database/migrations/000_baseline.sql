-- ============================================================
--  Migration 000 - baseline schema
--
--  The whole schema as it stands, in one file, so a brand new
--  database reaches the current shape in a single step.
--
--  Three things this file deliberately does NOT do:
--
--  1. CREATE DATABASE / USE. A managed provider hands you a
--     database that already exists, under a name it chose, and
--     a connection with no privilege to create another. The
--     database is selected by DB_NAME on the connection; every
--     statement here is relative to it.
--
--  2. Seed an administrator. The previous schema inserted one
--     with the password "admin123" and published the hash in a
--     file kept in version control - a known credential on
--     every deployment that ever imported it. Accounts are
--     created by bin/create-admin.php instead, which takes the
--     password from the environment and never writes it down.
--
--  3. Seed sample exams. Demo content belongs in a seed, not in
--     the schema; production should not come up pre-populated
--     with a quiz about the capital of France. See
--     database/seeds/demo.sql.
--
--  Applied by:  php bin/migrate.php
-- ============================================================

CREATE TABLE IF NOT EXISTS admins (
    admin_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(60)  NOT NULL UNIQUE,
    email      VARCHAR(150) NOT NULL UNIQUE,
    full_name  VARCHAR(100) NOT NULL DEFAULT '',
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_students_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exams (
    exam_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    duration    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_exams_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    question_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id        INT UNSIGNED NOT NULL,
    question_text  TEXT NOT NULL,
    option1        VARCHAR(255) NOT NULL,
    option2        VARCHAR(255) NOT NULL,
    option3        VARCHAR(255) NOT NULL,
    option4        VARCHAR(255) NOT NULL,
    correct_answer TINYINT UNSIGNED NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    CONSTRAINT chk_questions_correct_answer CHECK (correct_answer BETWEEN 1 AND 4),
    INDEX idx_questions_exam (exam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The authoritative record of a student sitting an exam.
--
-- started_at / expires_at / submitted_at are DATETIME, not TIMESTAMP, and are
-- always written by PHP in UTC: a deadline must not depend on the database
-- server's time zone, which on a managed host is not ours to choose.
--
-- UNIQUE (student_id, exam_id) is what actually enforces one attempt per exam.
-- Two concurrent "start exam" requests can both pass an application-level
-- check; only one of them can insert this row.
CREATE TABLE IF NOT EXISTS exam_attempts (
    attempt_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id   INT UNSIGNED NOT NULL,
    exam_id      INT UNSIGNED NOT NULL,
    started_at   DATETIME NOT NULL,
    expires_at   DATETIME NOT NULL,
    submitted_at DATETIME NULL DEFAULT NULL,
    status       ENUM('in_progress', 'submitted', 'expired') NOT NULL DEFAULT 'in_progress',
    score        SMALLINT UNSIGNED NULL DEFAULT NULL,
    total        SMALLINT UNSIGNED NULL DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id)    REFERENCES exams(exam_id)       ON DELETE CASCADE,
    UNIQUE KEY uq_attempts_student_exam (student_id, exam_id),
    INDEX idx_attempts_status (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What the student actually chose, so a score can be audited rather than
-- taken on trust. The composite primary key makes a duplicate answer for one
-- question impossible.
CREATE TABLE IF NOT EXISTS attempt_answers (
    attempt_id  INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected    TINYINT UNSIGNED NULL DEFAULT NULL,
    is_correct  TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (attempt_id, question_id),
    FOREIGN KEY (attempt_id)  REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id)    ON DELETE CASCADE,
    CONSTRAINT chk_attempt_answers_selected
        CHECK (selected IS NULL OR selected BETWEEN 1 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per failed sign-in, counted over a moving window. ip_address is
-- VARBINARY(16) so it holds the packed form of both IPv4 and IPv6.
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role         ENUM('student', 'admin') NOT NULL,
    identifier   VARCHAR(190) NOT NULL,
    ip_address   VARBINARY(16) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_login_attempts_identifier (role, identifier, attempted_at),
    INDEX idx_login_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS results (
    result_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NULL DEFAULT NULL,
    student_id INT UNSIGNED NOT NULL,
    exam_id    INT UNSIGNED NOT NULL,
    score      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    date_taken TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id)     ON DELETE CASCADE,
    FOREIGN KEY (exam_id)    REFERENCES exams(exam_id)           ON DELETE CASCADE,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attempt (student_id, exam_id),
    UNIQUE KEY uq_results_attempt (attempt_id),
    INDEX idx_results_date_taken (date_taken),
    INDEX idx_results_student_date (student_id, date_taken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
