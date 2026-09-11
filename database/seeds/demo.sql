-- ============================================================
--  Demo content - two short exams with three questions each.
--
--  Optional, and separate from the schema on purpose: a fresh
--  production database should not come up already containing a
--  quiz about the capital of France. Load it only where sample
--  content is wanted, typically local development or a demo
--  deployment.
--
--  Applied by:  php bin/migrate.php --seed
--
--  Re-runnable. Titles are unique in practice rather than by
--  constraint, so each insert is conditional on the exam not
--  already being there; running it twice does not double the
--  catalogue.
-- ============================================================

INSERT INTO exams (title, description, duration)
SELECT 'General Knowledge Basics', 'A short quiz on everyday general knowledge.', 10
 WHERE NOT EXISTS (SELECT 1 FROM exams WHERE title = 'General Knowledge Basics');

INSERT INTO exams (title, description, duration)
SELECT 'Introduction to PHP', 'Test your PHP fundamentals.', 15
 WHERE NOT EXISTS (SELECT 1 FROM exams WHERE title = 'Introduction to PHP');

-- Questions are attached by looking the exam up by title rather than by a
-- hardcoded id, because the ids depend on what was already in the table.
INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_answer)
SELECT e.exam_id, q.question_text, q.option1, q.option2, q.option3, q.option4, q.correct_answer
  FROM (SELECT exam_id FROM exams WHERE title = 'General Knowledge Basics') e
  JOIN (
        SELECT 'What is the capital of France?' AS question_text,
               'Berlin' AS option1, 'Madrid' AS option2, 'Paris' AS option3, 'Rome' AS option4,
               3 AS correct_answer
  UNION SELECT 'How many continents are there on Earth?', '5', '6', '7', '8', 3
  UNION SELECT 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 2
       ) q
 WHERE NOT EXISTS (
        SELECT 1 FROM questions x
         WHERE x.exam_id = e.exam_id AND x.question_text = q.question_text
       );

INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_answer)
SELECT e.exam_id, q.question_text, q.option1, q.option2, q.option3, q.option4, q.correct_answer
  FROM (SELECT exam_id FROM exams WHERE title = 'Introduction to PHP') e
  JOIN (
        SELECT 'Which tag is used to output in PHP?' AS question_text,
               'echo' AS option1, 'print' AS option2, 'Both A and B' AS option3, 'None' AS option4,
               3 AS correct_answer
  UNION SELECT 'PHP stands for?', 'Personal Home Page', 'Hypertext Preprocessor',
               'Private HTML Page', 'Pre Hypertext Processor', 2
  UNION SELECT 'Which function checks if a variable is set in PHP?',
               'isset()', 'empty()', 'defined()', 'exists()', 1
       ) q
 WHERE NOT EXISTS (
        SELECT 1 FROM questions x
         WHERE x.exam_id = e.exam_id AND x.question_text = q.question_text
       );
