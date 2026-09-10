<?php
/**
 * add_exam.php – Create a new exam
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$errors = [];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration    = (int)($_POST['duration']   ?? 0);

    if (!$title)        $errors[] = 'Exam title is required.';
    if ($duration < 1)  $errors[] = 'Duration must be at least 1 minute.';
    if ($duration > 300) $errors[] = 'Duration cannot exceed 300 minutes.';

    if (empty($errors)) {
        $stmt = $db->prepare('INSERT INTO exams (title, description, duration) VALUES (?, ?, ?)');
        $stmt->execute([$title, $description, $duration]);
        $newId = $db->lastInsertId();
        header('Location: ' . BASE_URL . '/admin/questions.php?exam_id=' . $newId . '&msg=exam_created');
        exit;
    }
}

$pageTitle = 'Add New Exam';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:4px;">Add New Exam</h2>
    <p class="text-muted mb-2">Fill in the details below to create a new examination.</p>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div><strong>Please fix:</strong><ul style="margin:.5rem 0 0 1.2rem;">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width:640px;">
      <div class="card-header">📋 Exam Details</div>
      <div class="card-body">
        <form method="POST" novalidate>
          <div class="form-group">
            <label for="title">Exam Title *</label>
            <input type="text" id="title" name="title" class="form-control"
              placeholder="e.g. Introduction to Python"
              value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"
              placeholder="Brief description of this exam (optional)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="duration">Duration (minutes) *</label>
            <input type="number" id="duration" name="duration" class="form-control"
              min="1" max="300" placeholder="30"
              value="<?= htmlspecialchars($_POST['duration'] ?? '30') ?>" required>
          </div>
          <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary">💾 Save & Add Questions →</button>
            <a href="<?= BASE_URL ?>/admin/exams.php" class="btn btn-outline">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
