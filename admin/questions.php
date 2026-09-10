<?php
/**
 * questions.php – Admin: manage questions for an exam
 * Handles: listing, adding, editing (inline GET edit), and deleting questions.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db     = getDB();
$examId = (int)($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
$errors = [];
$success = '';

if (!$examId) {
    header('Location: ' . BASE_URL . '/admin/exams.php');
    exit;
}

// Load the exam
$stmtExam = $db->prepare('SELECT * FROM exams WHERE exam_id = ?');
$stmtExam->execute([$examId]);
$exam = $stmtExam->fetch();
if (!$exam) {
    header('Location: ' . BASE_URL . '/admin/exams.php');
    exit;
}

/* ── Delete question ──────────────────────────────────── */
if (isset($_GET['delete_q']) && is_numeric($_GET['delete_q'])) {
    $del = $db->prepare('DELETE FROM questions WHERE question_id = ? AND exam_id = ?');
    $del->execute([(int)$_GET['delete_q'], $examId]);
    header('Location: ' . BASE_URL . '/admin/questions.php?exam_id=' . $examId . '&msg=q_deleted');
    exit;
}

/* ── Edit mode: load question for pre-fill ─────────────── */
$editQuestion = null;
if (isset($_GET['edit_q']) && is_numeric($_GET['edit_q'])) {
    $stmtEdit = $db->prepare('SELECT * FROM questions WHERE question_id = ? AND exam_id = ?');
    $stmtEdit->execute([(int)$_GET['edit_q'], $examId]);
    $editQuestion = $stmtEdit->fetch();
}

/* ── Add / Update question via POST ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId    = (int)($_POST['question_id'] ?? 0);
    $questionText  = trim($_POST['question_text'] ?? '');
    $option1       = trim($_POST['option1'] ?? '');
    $option2       = trim($_POST['option2'] ?? '');
    $option3       = trim($_POST['option3'] ?? '');
    $option4       = trim($_POST['option4'] ?? '');
    $correctAnswer = (int)($_POST['correct_answer'] ?? 0);

    if (!$questionText) $errors[] = 'Question text is required.';
    if (!$option1)      $errors[] = 'Option A is required.';
    if (!$option2)      $errors[] = 'Option B is required.';
    if (!$option3)      $errors[] = 'Option C is required.';
    if (!$option4)      $errors[] = 'Option D is required.';
    if ($correctAnswer < 1 || $correctAnswer > 4) $errors[] = 'Select a correct answer (1–4).';

    if (empty($errors)) {
        if ($questionId) {
            // UPDATE existing
            $upd = $db->prepare('
                UPDATE questions
                SET question_text = ?, option1 = ?, option2 = ?, option3 = ?,
                    option4 = ?, correct_answer = ?
                WHERE question_id = ? AND exam_id = ?
            ');
            $upd->execute([$questionText, $option1, $option2, $option3, $option4, $correctAnswer, $questionId, $examId]);
            $success = 'Question updated successfully.';
        } else {
            // INSERT new
            $ins = $db->prepare('
                INSERT INTO questions (exam_id, question_text, option1, option2, option3, option4, correct_answer)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $ins->execute([$examId, $questionText, $option1, $option2, $option3, $option4, $correctAnswer]);
            $success = 'Question added successfully.';
        }
        header('Location: ' . BASE_URL . '/admin/questions.php?exam_id=' . $examId . '&msg=saved');
        exit;
    }

    // Pre-fill on error
    $editQuestion = [
        'question_id'    => $questionId,
        'question_text'  => $_POST['question_text'],
        'option1'        => $_POST['option1'],
        'option2'        => $_POST['option2'],
        'option3'        => $_POST['option3'],
        'option4'        => $_POST['option4'],
        'correct_answer' => $_POST['correct_answer'],
    ];
}

$msg = $_GET['msg'] ?? '';

/* ── Load all questions ────────────────────────────────── */
$stmtQ = $db->prepare('SELECT * FROM questions WHERE exam_id = ? ORDER BY question_id');
$stmtQ->execute([$examId]);
$questions = $stmtQ->fetchAll();

$pageTitle = 'Manage Questions';
$role      = 'admin';
require_once ROOT . '/includes/header.php';

$letters = ['A', 'B', 'C', 'D'];
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:8px;">
      <div>
        <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:2px;">Questions</h2>
        <p class="text-muted">Exam: <strong><?= htmlspecialchars($exam['title']) ?></strong>
           &nbsp;|&nbsp; <?= $exam['duration'] ?> min
           &nbsp;|&nbsp; <?= count($questions) ?> question(s)</p>
      </div>
      <div style="display:flex;gap:10px;">
        <a href="<?= BASE_URL ?>/admin/edit_exam.php?id=<?= $examId ?>" class="btn btn-outline btn-sm">✏️ Edit Exam</a>
        <a href="<?= BASE_URL ?>/admin/exams.php" class="btn btn-outline btn-sm">← All Exams</a>
      </div>
    </div>

    <?php if ($msg === 'saved'):    ?><div class="alert alert-success" data-auto-dismiss>✅ Question saved.</div><?php endif; ?>
    <?php if ($msg === 'q_deleted'):?><div class="alert alert-success" data-auto-dismiss>✅ Question deleted.</div><?php endif; ?>
    <?php if ($msg === 'exam_created'):?><div class="alert alert-info" data-auto-dismiss>📋 Exam created! Now add questions below.</div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start;">

      <!-- ── Add / Edit Form ──────────────────────── -->
      <div class="card">
        <div class="card-header">
          <?= $editQuestion ? '✏️ Edit Question' : '➕ Add Question' ?>
        </div>
        <div class="card-body">
          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <div><ul style="margin:0 0 0 1.2rem;">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
              </ul></div>
            </div>
          <?php endif; ?>

          <form method="POST">
            <input type="hidden" name="exam_id" value="<?= $examId ?>">
            <input type="hidden" name="question_id" value="<?= (int)($editQuestion['question_id'] ?? 0) ?>">

            <div class="form-group">
              <label>Question Text *</label>
              <textarea name="question_text" class="form-control" rows="3"
                placeholder="Enter the question here…"><?= htmlspecialchars($editQuestion['question_text'] ?? '') ?></textarea>
            </div>

            <?php foreach (['option1','option2','option3','option4'] as $oi => $optName): ?>
            <div class="form-group">
              <label>Option <?= $letters[$oi] ?> *</label>
              <input type="text" name="<?= $optName ?>" class="form-control"
                placeholder="Option <?= $letters[$oi] ?>"
                value="<?= htmlspecialchars($editQuestion[$optName] ?? '') ?>">
            </div>
            <?php endforeach; ?>

            <div class="form-group">
              <label>Correct Answer *</label>
              <select name="correct_answer" class="form-control">
                <option value="">-- Select correct option --</option>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                  <option value="<?= $i ?>" <?= (($editQuestion['correct_answer'] ?? '') == $i) ? 'selected' : '' ?>>
                    Option <?= $letters[$i-1] ?>
                  </option>
                <?php endfor; ?>
              </select>
            </div>

            <div style="display:flex;gap:10px;">
              <button type="submit" class="btn btn-primary btn-sm">
                <?= $editQuestion ? '💾 Update Question' : '➕ Add Question' ?>
              </button>
              <?php if ($editQuestion): ?>
                <a href="<?= BASE_URL ?>/admin/questions.php?exam_id=<?= $examId ?>" class="btn btn-outline btn-sm">✕ Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Questions List ───────────────────────── -->
      <div>
        <?php if (empty($questions)): ?>
          <div class="empty-state card" style="padding:40px;">
            <div class="icon">❓</div>
            <p>No questions yet. Add your first question using the form.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:14px;">
            <?php foreach ($questions as $qi => $q): ?>
              <div class="card" style="transition:box-shadow .2s;">
                <div class="card-body" style="padding:16px 18px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                    <p style="font-weight:600;font-size:.92rem;margin-bottom:10px;flex:1;">
                      <span class="badge badge-primary" style="margin-right:6px;">Q<?= $qi + 1 ?></span>
                      <?= htmlspecialchars($q['question_text']) ?>
                    </p>
                    <div style="display:flex;gap:6px;flex-shrink:0;">
                      <a href="<?= BASE_URL ?>/admin/questions.php?exam_id=<?= $examId ?>&edit_q=<?= $q['question_id'] ?>"
                         class="btn btn-sm btn-warning">✏️</a>
                      <a href="<?= BASE_URL ?>/admin/questions.php?exam_id=<?= $examId ?>&delete_q=<?= $q['question_id'] ?>"
                         class="btn btn-sm btn-danger"
                         data-confirm="Delete this question?">🗑️</a>
                    </div>
                  </div>
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;">
                    <?php foreach (['option1','option2','option3','option4'] as $oi => $optKey):
                      $isCorrect = ($q['correct_answer'] == ($oi + 1));
                    ?>
                      <div style="font-size:.82rem;padding:5px 10px;border-radius:5px;
                        background:<?= $isCorrect ? '#dcfce7' : '#f8f9fd' ?>;
                        border:1px solid <?= $isCorrect ? '#86efac' : 'var(--clr-border)' ?>;
                        color:<?= $isCorrect ? '#166534' : 'var(--clr-text)' ?>;">
                        <strong><?= $letters[$oi] ?>.</strong>
                        <?= htmlspecialchars($q[$optKey]) ?>
                        <?= $isCorrect ? ' ✓' : '' ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /grid -->
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
