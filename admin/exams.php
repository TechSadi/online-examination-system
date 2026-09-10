<?php
/**
 * exams.php – Admin: list all exams with edit/delete/question management
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db = getDB();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $db->prepare('DELETE FROM exams WHERE exam_id = ?');
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/exams.php?msg=deleted');
    exit;
}

$msg = $_GET['msg'] ?? '';

$exams = $db->query('
    SELECT e.*,
      (SELECT COUNT(*) FROM questions WHERE exam_id = e.exam_id) AS question_count,
      (SELECT COUNT(*) FROM results    WHERE exam_id = e.exam_id) AS attempt_count
    FROM exams e
    ORDER BY e.created_at DESC
')->fetchAll();

$pageTitle = 'Manage Exams';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
      <div>
        <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:2px;">Manage Exams</h2>
        <p class="text-muted">Create, edit, and delete examinations.</p>
      </div>
      <a href="<?= BASE_URL ?>/admin/add_exam.php" class="btn btn-primary">➕ Add New Exam</a>
    </div>

    <?php if ($msg === 'deleted'): ?>
      <div class="alert alert-success" data-auto-dismiss>✅ Exam deleted successfully.</div>
    <?php elseif ($msg === 'saved'): ?>
      <div class="alert alert-success" data-auto-dismiss>✅ Exam saved successfully.</div>
    <?php endif; ?>

    <?php if (empty($exams)): ?>
      <div class="empty-state">
        <div class="icon">📭</div>
        <p>No exams yet. <a href="<?= BASE_URL ?>/admin/add_exam.php">Create your first exam.</a></p>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Duration</th>
              <th>Questions</th>
              <th>Attempts</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($exams as $i => $e): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
              <td><?= $e['duration'] ?> min</td>
              <td>
                <span class="badge <?= $e['question_count'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                  <?= $e['question_count'] ?>
                </span>
              </td>
              <td><?= $e['attempt_count'] ?></td>
              <td class="text-muted"><?= date('M j, Y', strtotime($e['created_at'])) ?></td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>/admin/edit_exam.php?id=<?= $e['exam_id'] ?>"
                   class="btn btn-sm btn-warning">✏️ Edit</a>
                <a href="<?= BASE_URL ?>/admin/questions.php?exam_id=<?= $e['exam_id'] ?>"
                   class="btn btn-sm btn-primary">❓ Questions</a>
                <a href="<?= BASE_URL ?>/admin/exams.php?delete=<?= $e['exam_id'] ?>"
                   class="btn btn-sm btn-danger"
                   data-confirm="Delete '<?= htmlspecialchars($e['title']) ?>' and all its data?">🗑️ Delete</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
