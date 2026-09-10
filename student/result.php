<?php
/**
 * result.php – Show a student's result for a specific exam.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$examId    = (int)($_GET['exam_id'] ?? 0);

if (!$examId) {
    header('Location: ' . BASE_URL . '/student/results.php');
    exit;
}

// Load result
$stmt = $db->prepare('
    SELECT r.*, e.title, e.description, e.duration
    FROM results r
    JOIN exams e ON e.exam_id = r.exam_id
    WHERE r.student_id = ? AND r.exam_id = ?
');
$stmt->execute([$studentId, $examId]);
$result = $stmt->fetch();

if (!$result) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

$pct    = $result['total'] > 0 ? round(($result['score'] / $result['total']) * 100) : 0;
$passed = $pct >= 60;

$pageTitle = 'Result – ' . $result['title'];
$role      = 'student';
require_once ROOT . '/includes/header.php';
?>

<div class="page-header">
  <h1>📊 Exam Result</h1>
  <p><?= htmlspecialchars($result['title']) ?></p>
</div>

<div class="container section">
  <div style="max-width:640px;margin:0 auto;">
    <div class="card">
      <div class="card-body">
        <div class="result-hero">
          <!-- Score Circle -->
          <div class="score-circle <?= $passed ? 'pass' : 'fail' ?>">
            <div class="score-pct"><?= $pct ?>%</div>
            <div class="score-sub">Score</div>
          </div>

          <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:8px;">
            <?= $passed ? '🎉 Congratulations!' : '😔 Better Luck Next Time' ?>
          </h2>
          <p class="text-muted">
            <?= $passed
              ? 'You passed the exam. Great work!'
              : 'You did not reach the passing score (60%). Keep practising!' ?>
          </p>

          <div class="result-breakdown">
            <div class="item">
              <div class="val" style="color:var(--clr-success);"><?= $result['score'] ?></div>
              <div class="lbl">Correct</div>
            </div>
            <div class="item">
              <div class="val" style="color:var(--clr-danger);"><?= $result['total'] - $result['score'] ?></div>
              <div class="lbl">Wrong</div>
            </div>
            <div class="item">
              <div class="val"><?= $result['total'] ?></div>
              <div class="lbl">Total</div>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="progress-bar" style="max-width:320px;margin:0 auto 20px;">
            <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $passed ? 'var(--clr-success)' : 'var(--clr-danger)' ?>;"></div>
          </div>

          <p class="text-muted" style="font-size:.84rem;">
            Taken on <?= date('F j, Y \a\t g:i a', strtotime($result['date_taken'])) ?>
          </p>
        </div>
      </div>
      <div class="card-footer" style="justify-content:center;gap:12px;">
        <a href="<?= BASE_URL ?>/student/exams.php"   class="btn btn-primary">Browse More Exams</a>
        <a href="<?= BASE_URL ?>/student/results.php" class="btn btn-outline">All My Results</a>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
