<?php
/**
 * exams.php – List available exams for the student
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

$db        = getDB();
$studentId = $_SESSION['student_id'];

// Get all exams plus whether this student has already completed each
$stmt = $db->prepare('
    SELECT e.*,
           (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) AS question_count,
           (SELECT result_id FROM results WHERE student_id = ? AND exam_id = e.exam_id LIMIT 1) AS already_taken
    FROM exams e
    ORDER BY e.created_at DESC
');
$stmt->execute([$studentId]);
$exams = $stmt->fetchAll();

$pageTitle = 'Available Exams';
$role      = 'student';
require_once ROOT . '/includes/header.php';
?>

<div class="page-header">
  <h1>📋 Available Exams</h1>
  <p>Select an exam below to begin. Each exam can only be taken once.</p>
</div>

<div class="container">
  <?php if (empty($exams)): ?>
    <div class="empty-state" style="padding:80px 24px;">
      <div class="icon">📭</div>
      <p>No exams available at the moment. Check back later!</p>
    </div>
  <?php else: ?>
    <div class="exams-grid">
      <?php foreach ($exams as $exam): ?>
        <div class="exam-card">
          <div class="exam-card-header">
            <h3><?= htmlspecialchars($exam['title']) ?></h3>
            <div class="duration">⏱️ <?= $exam['duration'] ?> minutes &nbsp;|&nbsp; ❓ <?= $exam['question_count'] ?> questions</div>
          </div>
          <div class="exam-card-body">
            <p><?= htmlspecialchars($exam['description'] ?: 'No description provided.') ?></p>
            <?php if ($exam['already_taken']): ?>
              <span class="badge badge-success" style="margin-bottom:10px;display:inline-block;">✅ Completed</span><br>
              <a href="<?= BASE_URL ?>/student/result.php?exam_id=<?= $exam['exam_id'] ?>" class="btn btn-outline btn-sm">View Result</a>
            <?php elseif ($exam['question_count'] == 0): ?>
              <span class="badge badge-warning">⚠️ No questions yet</span>
            <?php else: ?>
              <a href="<?= BASE_URL ?>/student/take_exam.php?exam_id=<?= $exam['exam_id'] ?>" class="btn btn-primary btn-sm">Start Exam →</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
