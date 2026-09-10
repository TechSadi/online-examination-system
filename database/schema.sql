-- ============================================================
--  Online Examination System – Database Script
--  Compatible with MySQL 5.7+ / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS online_exam_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE online_exam_db;

CREATE TABLE IF NOT EXISTS admins (
    admin_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(60)  NOT NULL UNIQUE,
    email      VARCHAR(150) NOT NULL UNIQUE,
    full_name  VARCHAR(100) NOT NULL DEFAULT '',
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin  (username: admin | email: admin@examhub.com | password: admin123)
INSERT INTO admins (username, email, full_name, password) VALUES
  ('admin', 'admin@examhub.com', 'Super Admin',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ── Migration note ────────────────────────────────────────
-- If you already ran the old schema, run these ALTER statements instead
-- of re-importing the whole file:
--
-- ALTER TABLE admins
--   ADD COLUMN email     VARCHAR(150) NOT NULL DEFAULT '' AFTER username,
--   ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER email,
--   ADD UNIQUE KEY uq_admin_email (email);
--
-- UPDATE admins SET email='admin@examhub.com', full_name='Super Admin'
--   WHERE username='admin';
-- ─────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exams (
    exam_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    duration    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions (
    question_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id        INT UNSIGNED NOT NULL,
    question_text  TEXT NOT NULL,
    option1        VARCHAR(255) NOT NULL,
    option2        VARCHAR(255) NOT NULL,
    option3        VARCHAR(255) NOT NULL,
    option4        VARCHAR(255) NOT NULL,
    correct_answer TINYINT UNSIGNED NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS results (
    result_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    exam_id    INT UNSIGNED NOT NULL,
    score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    total      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    date_taken TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id)    REFERENCES exams(exam_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attempt (student_id, exam_id)
) ENGINE=InnoDB;

-- Sample data
INSERT INTO exams (title, description, duration) VALUES
  ('General Knowledge Basics', 'A short quiz on everyday general knowledge.', 10),
  ('Introduction to PHP', 'Test your PHP fundamentals.', 15);

INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_answer) VALUES
  (1, 'What is the capital of France?', 'Berlin', 'Madrid', 'Paris', 'Rome', 3),
  (1, 'How many continents are there on Earth?', '5', '6', '7', '8', 3),
  (1, 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 2),
  (2, 'Which tag is used to output in PHP?', 'echo', 'print', 'Both A and B', 'None', 3),
  (2, 'PHP stands for?', 'Personal Home Page', 'Hypertext Preprocessor', 'Private HTML Page', 'Pre Hypertext Processor', 2),
  (2, 'Which function checks if a variable is set in PHP?', 'isset()', 'empty()', 'defined()', 'exists()', 1);
