-- ============================================================
--  Migration 001 - Phase 2 data integrity
--
--  Safe to run on an existing database. Every statement is
--  additive or widening; no data is deleted or rewritten.
--
--  Apply with:
--    mysql -u root -p online_exam_db < database/migrations/001_phase2_integrity.sql
-- ============================================================

USE online_exam_db;

-- ── 1. Score columns were TINYINT UNSIGNED (max 255) ────────
-- An exam with more than 255 questions silently overflowed.
-- SMALLINT keeps the same storage semantics with room to grow.
ALTER TABLE results
  MODIFY COLUMN score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  MODIFY COLUMN total SMALLINT UNSIGNED NOT NULL DEFAULT 0;

-- ── 2. Constrain the answer key to the four real options ────
-- correct_answer is read straight into the grader, so a value
-- outside 1-4 would make a question ungradeable.
ALTER TABLE questions
  ADD CONSTRAINT chk_questions_correct_answer
  CHECK (correct_answer BETWEEN 1 AND 4);

-- ── 3. Indexes for the orderings the app actually issues ────
-- Every results listing sorts by date_taken; the student
-- dashboard and history filter by student_id first.
CREATE INDEX idx_results_date_taken ON results (date_taken);
CREATE INDEX idx_results_student_date ON results (student_id, date_taken);

-- The admin exam list counts questions per exam on every load.
CREATE INDEX idx_questions_exam ON questions (exam_id);

-- Exams are always listed newest first.
CREATE INDEX idx_exams_created ON exams (created_at);

-- Students are always listed newest first.
CREATE INDEX idx_students_created ON students (created_at);
