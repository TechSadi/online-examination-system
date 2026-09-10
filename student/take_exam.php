<?php
/**
 * take_exam.php – The live exam interface
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$examId    = (int)($_GET['exam_id'] ?? 0);

if (!$examId) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

// Load exam
$stmtExam = $db->prepare('SELECT * FROM exams WHERE exam_id = ?');
$stmtExam->execute([$examId]);
$exam = $stmtExam->fetch();
if (!$exam) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

// Prevent re-taking
$stmtDone = $db->prepare('SELECT result_id FROM results WHERE student_id = ? AND exam_id = ?');
$stmtDone->execute([$studentId, $examId]);
if ($stmtDone->fetch()) {
    header('Location: ' . BASE_URL . '/student/result.php?exam_id=' . $examId);
    exit;
}

// Load questions
$stmtQ = $db->prepare('SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id');
$stmtQ->execute([$examId]);
$questions = $stmtQ->fetchAll();

if (empty($questions)) {
    header('Location: ' . BASE_URL . '/student/exams.php');
    exit;
}

$pageTitle      = 'Taking: ' . $exam['title'];
$role           = 'student';
$includeExamJS  = true;   // tells footer to load exam.js
require_once ROOT . '/includes/header.php';
?>

<style>
/* Hide all slides by default; JS shows the active one */
.question-slide { display: none; }
.question-slide.active { display: block; }
</style>

<div class="page-header" style="padding:24px;">
  <h1 style="font-size:1.5rem;">📝 <?= htmlspecialchars($exam['title']) ?></h1>
  <p>Answer all questions before the timer runs out.</p>
</div>

<div class="container">
  <!-- Progress bar -->
  <div style="margin:20px 0 0;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
      <span style="font-size:.84rem;font-weight:600;color:var(--clr-muted);">Progress</span>
      <span id="exam-progress-label" style="font-size:.84rem;color:var(--clr-muted);">0 / <?= count($questions) ?> answered</span>
    </div>
    <div class="progress-bar">
      <div class="progress-bar-fill" id="exam-progress-fill" style="width:0%"></div>
    </div>
  </div>

  <div class="exam-layout">
    <!-- ── Left: Question Area ─────────────────────── -->
    <div>
      <!-- Hidden form – JS populates hidden inputs before submit -->
      <form id="exam-form" method="POST" action="<?= BASE_URL ?>/student/submit_exam.php">
        <input type="hidden" name="exam_id" value="<?= $examId ?>">
        <!-- questions are rendered below; answers injected by exam.js -->

        <?php foreach ($questions as $idx => $q): ?>
          <div class="question-slide" data-index="<?= $idx ?>">
            <div class="question-card">
              <div class="question-number">Question <?= $idx + 1 ?> of <?= count($questions) ?></div>
              <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>

              <div class="options-list">
                <?php
                $letters  = ['A','B','C','D'];
                $options  = [$q['option1'], $q['option2'], $q['option3'], $q['option4']];
                foreach ($options as $oi => $optText):
                  $val = $oi + 1; // 1-4
                ?>
                <label class="option-item">
                  <input type="radio" name="q_<?= $q['question_id'] ?>" value="<?= $val ?>">
                  <span class="option-letter"><?= $letters[$oi] ?></span>
                  <span class="option-label"><?= htmlspecialchars($optText) ?></span>
                </label>
                <?php endforeach; ?>
              </div>

              <!-- Hidden: store question_id for server-side grading -->
              <input type="hidden" name="question_ids[]" value="<?= $q['question_id'] ?>">

              <div class="question-nav-bar">
                <button type="button" id="<?= $idx === 0 ? 'btn-prev' : '' ?>"
                  <?php if ($idx === 0): ?>id="btn-prev"<?php endif; ?>
                  class="btn btn-outline btn-sm"
                  onclick="document.getElementById('btn-prev').click()"
                  style="<?= $idx === 0 ? '' : 'display:none' ?>">
                </button>

                <?php if ($idx === 0): ?>
                <!-- Prev/Next/Submit are global – rendered once outside loop -->
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Global nav bar (always visible) -->
        <div class="question-nav-bar" style="margin-top:20px;">
          <button type="button" id="btn-prev" class="btn btn-outline">← Previous</button>
          <button type="button" id="btn-next" class="btn btn-primary">Next →</button>
          <button type="button" id="btn-submit" class="btn btn-success" style="display:none;">
            ✅ Submit Exam
          </button>
        </div>
      </form>
    </div>

    <!-- ── Right: Timer + Nav Grid ────────────────── -->
    <div>
      <div class="timer-widget" id="timer-widget">
        <div class="timer-label">Time Remaining</div>
        <div class="timer-display" id="timer-display" data-duration="<?= (int)$exam['duration'] ?>">
          <?= sprintf('%02d:00', $exam['duration']) ?>
        </div>
        <div class="timer-label" id="timer-label">⏱️ Stay focused!</div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-header" style="font-size:.84rem;padding:12px 16px;">Question Navigator</div>
        <div class="card-body" style="padding:12px;">
          <div class="question-nav-grid">
            <?php foreach ($questions as $idx => $q): ?>
              <button type="button" class="q-nav-btn" data-index="<?= $idx ?>"><?= $idx + 1 ?></button>
            <?php endforeach; ?>
          </div>
          <div class="q-nav-legend" style="margin-top:12px;">
            <div><span class="dot" style="background:var(--clr-primary);display:inline-block;width:12px;height:12px;border-radius:3px;"></span> Current</div>
            <div><span class="dot" style="background:var(--clr-success);display:inline-block;width:12px;height:12px;border-radius:3px;"></span> Answered</div>
            <div><span class="dot" style="background:#e2e8f0;display:inline-block;width:12px;height:12px;border-radius:3px;border:2px solid #cbd5e1;"></span> Unanswered</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px;">
        <div class="card-body" style="padding:14px 16px;font-size:.82rem;color:var(--clr-muted);line-height:1.6;">
          <strong style="color:var(--clr-text);">⚠️ Instructions:</strong><br>
          • Use arrow keys or buttons to navigate<br>
          • Clicking an option saves your answer<br>
          • The exam auto-submits when time is up<br>
          • Do not close or refresh the page
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
