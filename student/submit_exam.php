<?php
/**
 * submit_exam.php – Grades the submitted exam and saves the result.
 * Only accepts POST requests.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

$db        = getDB();
$studentId = $_SESSION['student_id'];
$examId    = (int)($_POST['exam_id'] ?? 0);

if (!$examId) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

// Prevent double-submission
$stmtDone = $db->prepare('SELECT result_id FROM results WHERE student_id = ? AND exam_id = ?');
$stmtDone->execute([$studentId, $examId]);
if ($stmtDone->fetch()) {
    header('Location: ' . BASE_URL . '/student/result.php?exam_id=' . $examId);
    exit;
}

// Load all questions for this exam (with correct answers)
$stmtQ = $db->prepare('SELECT question_id, correct_answer FROM questions WHERE exam_id = ?');
$stmtQ->execute([$examId]);
$questions = $stmtQ->fetchAll();

if (empty($questions)) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

// Grade: compare submitted answers against correct_answer
$score = 0;
$total = count($questions);

// The JS injects hidden inputs answers[index] but we also receive q_<question_id> radios.
// We rely on q_<question_id> for accuracy.
foreach ($questions as $q) {
    $qid       = $q['question_id'];
    $submitted = (int)($_POST['q_' . $qid] ?? 0);
    if ($submitted === (int)$q['correct_answer']) {
        $score++;
    }
}

// Save result (INSERT IGNORE handles race conditions)
$stmtIns = $db->prepare('
    INSERT IGNORE INTO results (student_id, exam_id, score, total)
    VALUES (?, ?, ?, ?)
');
$stmtIns->execute([$studentId, $examId, $score, $total]);

// Redirect to result page
header('Location: ' . BASE_URL . '/student/result.php?exam_id=' . $examId);
exit;
