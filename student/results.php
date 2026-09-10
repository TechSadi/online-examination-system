<?php
/**
 * results.php – Student's full results history
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

$db        = getDB();
$studentId = $_SESSION['student_id'];

$stmt = $db->prepare('
    SELECT r.score, r.total, r.date_taken, r.exam_id, e.title, e.duration
    FROM results r
    JOIN exams e ON e.exam_id = r.exam_id
    WHERE r.student_id = ?
    ORDER BY r.date_taken DESC
');
$stmt->execute([$studentId]);
$results = $stmt->fetchAll();

$pageTitle = 'My Results';
$role      = 'student';
require_once ROOT . '/includes/header.php';
?>

<div class="page-header">
  <h1>📊 My Results</h1>
  <p>Your complete examination history.</p>
</div>

<div class="container section">
  <?php if (empty($results)): ?>
    <div class="empty-state">
      <div class="icon">📭</div>
      <p>You haven't taken any exams yet. <a href="<?= BASE_URL ?>/student/exams.php">Start one now!</a></p>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Exam Title</th>
            <th>Score</th>
            <th>Percentage</th>
            <th>Status</th>
            <th>Date Taken</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $i => $r):
            $pct    = $r['total'] > 0 ? round(($r['score'] / $r['total']) * 100) : 0;
            $passed = $pct >= 60;
          ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
            <td><?= $r['score'] ?> / <?= $r['total'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="progress-bar" style="width:80px;margin:0;">
                  <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $passed ? 'var(--clr-success)' : 'var(--clr-danger)' ?>;"></div>
                </div>
                <span style="font-size:.85rem;font-weight:600;"><?= $pct ?>%</span>
              </div>
            </td>
            <td>
              <span class="badge <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                <?= $passed ? 'PASS' : 'FAIL' ?>
              </span>
            </td>
            <td class="text-muted"><?= date('M j, Y', strtotime($r['date_taken'])) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/student/result.php?exam_id=<?= $r['exam_id'] ?>" class="btn btn-sm btn-outline">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
