<?php
/**
 * students.php – Admin: view and delete student accounts
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db = getDB();

// Delete student
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = $db->prepare('DELETE FROM students WHERE student_id = ?');
    $del->execute([(int)$_GET['delete']]);
    header('Location: ' . BASE_URL . '/admin/students.php?msg=deleted');
    exit;
}

$msg = $_GET['msg'] ?? '';

// Load students with attempt counts
$students = $db->query('
    SELECT s.*,
      (SELECT COUNT(*) FROM results WHERE student_id = s.student_id) AS attempts,
      (SELECT ROUND(AVG((score/total)*100),1)
       FROM results WHERE student_id = s.student_id AND total > 0) AS avg_score
    FROM students s
    ORDER BY s.created_at DESC
')->fetchAll();

$pageTitle = 'Manage Students';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:4px;">Manage Students</h2>
    <p class="text-muted mb-2"><?= count($students) ?> registered student(s)</p>

    <?php if ($msg === 'deleted'): ?>
      <div class="alert alert-success" data-auto-dismiss>✅ Student account deleted.</div>
    <?php endif; ?>

    <?php if (empty($students)): ?>
      <div class="empty-state">
        <div class="icon">👤</div>
        <p>No students registered yet.</p>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Exams Taken</th>
              <th>Avg Score</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $s): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td>
                <span class="badge <?= $s['attempts'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                  <?= $s['attempts'] ?>
                </span>
              </td>
              <td>
                <?php if ($s['avg_score'] !== null): ?>
                  <span class="badge <?= $s['avg_score'] >= 60 ? 'badge-success' : 'badge-danger' ?>">
                    <?= $s['avg_score'] ?>%
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>/admin/results.php?student_id=<?= $s['student_id'] ?>"
                   class="btn btn-sm btn-primary">📊 Results</a>
                <a href="<?= BASE_URL ?>/admin/students.php?delete=<?= $s['student_id'] ?>"
                   class="btn btn-sm btn-danger"
                   data-confirm="Delete student '<?= htmlspecialchars($s['name']) ?>' and all their data?">🗑️ Delete</a>
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
