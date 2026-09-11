-- ============================================================
--  Migration 002 - Phase 3 security & exam integrity
--
--  Introduces the server-authoritative exam attempt model and
--  the login throttling log.
--
--  Safe to run on an existing database: every statement is
--  additive and re-runnable, and existing results are backfilled
--  into attempts so history stays consistent. On a database
--  created from 000_baseline the whole file is a no-op.
--
--  No USE statement: the database is chosen by DB_NAME on the
--  connection, which on a managed host is a name the provider
--  picked rather than one this file can assume.
--
--  Applied by:  php bin/migrate.php
-- ============================================================

-- ── 1. Exam attempts ────────────────────────────────────────
-- The authoritative record of a student sitting an exam.
--
-- started_at / expires_at / submitted_at are DATETIME, not
-- TIMESTAMP, and are always written by PHP in UTC. The deadline
-- must never depend on the MySQL server's timezone, which is not
-- guaranteed to match the application's.
--
-- expires_at is pinned when the attempt starts, so editing an
-- exam's duration mid-attempt cannot extend or shorten a sitting
-- that is already under way.
--
-- UNIQUE (student_id, exam_id) enforces one attempt per exam at
-- the storage layer, so two concurrent "start exam" requests
-- cannot both create an attempt.
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

-- ── 2. Per-question answers ─────────────────────────────────
-- Stores what the student actually chose and whether it was
-- correct, so a score can be audited rather than taken on trust.
--
-- The composite primary key makes a duplicate answer for the
-- same question impossible.
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

-- ── 3. Login throttling log ─────────────────────────────────
-- One row per failed sign-in. Successful sign-ins clear the
-- rows for that identifier.
--
-- ip_address is VARBINARY(16) so it holds the packed form of
-- both IPv4 and IPv6 addresses (inet_pton).
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role         ENUM('student', 'admin') NOT NULL,
    identifier   VARCHAR(190) NOT NULL,
    ip_address   VARBINARY(16) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_login_attempts_identifier (role, identifier, attempted_at),
    INDEX idx_login_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Tie each result to the attempt that produced it ──────
-- MySQL has no ADD COLUMN IF NOT EXISTS, so the column and its
-- keys are added only when absent. This keeps the migration
-- re-runnable.
SET @has_column := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'results'
       AND COLUMN_NAME  = 'attempt_id'
);

SET @sql := IF(@has_column = 0,
    'ALTER TABLE results
       ADD COLUMN attempt_id INT UNSIGNED NULL DEFAULT NULL AFTER result_id,
       ADD UNIQUE KEY uq_results_attempt (attempt_id),
       ADD CONSTRAINT fk_results_attempt
           FOREIGN KEY (attempt_id) REFERENCES exam_attempts(attempt_id) ON DELETE CASCADE',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── 5. Backfill attempts for results recorded before Phase 3 ─
-- Without this, a pre-existing result would have no attempt
-- behind it and the student's history would look inconsistent.
-- The exact start time is unknowable, so started_at, expires_at
-- and submitted_at all take the recorded date.
INSERT INTO exam_attempts
       (student_id, exam_id, started_at, expires_at, submitted_at, status, score, total)
SELECT r.student_id, r.exam_id, r.date_taken, r.date_taken, r.date_taken,
       'submitted', r.score, r.total
  FROM results r
  LEFT JOIN exam_attempts a
         ON a.student_id = r.student_id
        AND a.exam_id    = r.exam_id
 WHERE a.attempt_id IS NULL;

UPDATE results r
  JOIN exam_attempts a
    ON a.student_id = r.student_id
   AND a.exam_id    = r.exam_id
   SET r.attempt_id = a.attempt_id
 WHERE r.attempt_id IS NULL;
