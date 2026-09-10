<?php
/**
 * edit_exam.php – Edit an existing exam
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db     = getDB();
$examId = (int)($_GET['id'] ?? $_POST['exam_id'] ?? 0);
$errors = [];

if (!$examId) {
    header('Location: ' . BASE_URL . '/admin/exams.php');
    exit;
}

// Load exam
$stmt = $db->prepare('SELECT * FROM exams WHERE exam_id = ?');
$stmt->execute([$examId]);
$exam = $stmt->fetch();
if (!$exam) {
    header('Location: ' . BASE_URL . '/admin/exams.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration    = (int)($_POST['duration']   ?? 0);

    if (!$title)        $errors[] = 'Exam title is required.';
    if ($duration < 1)  $errors[] = 'Duration must be at least 1 minute.';

    if (empty($errors)) {
        $upd = $db->prepare('UPDATE exams SET title = ?, description = ?, duration = ? WHERE exam_id = ?');
        $upd->execute([$title, $description, $duration, $examId]);
        header('Location: ' . BASE_URL . '/admin/exams.php?msg=saved');
        exit;
    }

    // Re-populate from POST on error
    $exam['title']       = $_POST['title'];
    $exam['description'] = $_POST['description'];
    $exam['duration']    = $_POST['duration'];
}

$pageTitle = 'Edit Exam';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:4px;">Edit Exam</h2>
    <p class="text-muted mb-2">Update the exam details below.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div><strong>Please fix:</strong><ul style="margin:.5rem 0 0 1.2rem;">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width:640px;">
      <div class="card-header">✏️ Edit: <?= htmlspecialchars($exam['title']) ?></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="exam_id" value="<?= $examId ?>">
          <div class="form-group">
            <label for="title">Exam Title *</label>
            <input type="text" id="title" name="title" class="form-control"
              value="<?= htmlspecialchars($exam['title']) ?>" required>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($exam['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="duration">Duration (minutes) *</label>
            <input type="number" id="duration" name="duration" class="form-control"
              min="1" max="300" value="<?= (int)$exam['duration'] ?>" required>
          </div>
          <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            <a href="<?= BASE_URL ?>/admin/questions.php?exam_id=<?= $examId ?>" class="btn btn-outline">Manage Questions</a>
            <a href="<?= BASE_URL ?>/admin/exams.php" class="btn btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
