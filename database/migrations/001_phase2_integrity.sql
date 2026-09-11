-- ============================================================
--  Migration 001 - Phase 2 data integrity
--
--  Brings a database created before Phase 2 up to the baseline
--  shape: widened score columns, a constrained answer key, and
--  indexes for the orderings the application actually issues.
--
--  On a database created from 000_baseline every statement here
--  is already true, so each one is guarded and the file is a
--  no-op. That is deliberate: the runner must be correct on a
--  new database and on one several versions behind, without the
--  operator having to know which they have.
--
--  MySQL has no ADD CONSTRAINT IF NOT EXISTS and no CREATE INDEX
--  IF NOT EXISTS, so the guard is a lookup in information_schema
--  feeding a prepared statement. DATABASE() resolves to whatever
--  DB_NAME selected, so the file names no database of its own.
--
--  Applied by:  php bin/migrate.php
-- ============================================================

-- ── 1. Score columns were TINYINT UNSIGNED (max 255) ────────
-- An exam with more than 255 questions silently overflowed.
-- Re-running this is a no-op: the columns are already SMALLINT.
ALTER TABLE results
  MODIFY COLUMN score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY COLUMN total SMALLINT UNSIGNED NOT NULL DEFAULT 0;

-- ── 2. Constrain the answer key to the four real options ────
-- correct_answer is read straight into the grader, so a value
-- outside 1-4 would make a question ungradeable.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME        = 'questions'
       AND CONSTRAINT_NAME   = 'chk_questions_correct_answer'
);
SET @sql := IF(@exists = 0,
    'ALTER TABLE questions
       ADD CONSTRAINT chk_questions_correct_answer
       CHECK (correct_answer BETWEEN 1 AND 4)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── 3. Indexes for the orderings the app actually issues ────

-- Every results listing sorts by date_taken.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'results'
       AND INDEX_NAME   = 'idx_results_date_taken'
);
SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_results_date_taken ON results (date_taken)', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- The student dashboard and history filter by student_id first.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'results'
       AND INDEX_NAME   = 'idx_results_student_date'
);
SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_results_student_date ON results (student_id, date_taken)', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- The admin exam list counts questions per exam on every load.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'questions'
       AND INDEX_NAME   = 'idx_questions_exam'
);
SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_questions_exam ON questions (exam_id)', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Exams are always listed newest first.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'exams'
       AND INDEX_NAME   = 'idx_exams_created'
);
SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_exams_created ON exams (created_at)', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Students are always listed newest first.
SET @exists := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'students'
       AND INDEX_NAME   = 'idx_students_created'
);
SET @sql := IF(@exists = 0,
    'CREATE INDEX idx_students_created ON students (created_at)', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
