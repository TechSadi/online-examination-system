<?php

/**
 * dashboard.php – Student dashboard
 */

define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireStudent();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$name      = $_SESSION['student_name'];

// Stats
$totalExams = $db->query('SELECT COUNT(*) FROM exams')->fetchColumn();

$stmtAttempts = $db->prepare('SELECT COUNT(*) FROM results WHERE student_id = ?');
$stmtAttempts->execute([$studentId]);
$attempted = $stmtAttempts->fetchColumn();

$stmtAvg = $db->prepare('SELECT AVG((score/total)*100) FROM results WHERE student_id = ? AND total > 0');
$stmtAvg->execute([$studentId]);
$avgScore = round($stmtAvg->fetchColumn() ?? 0);

// Recent results
$stmtRecent = $db->prepare('
    SELECT r.score, r.total, r.date_taken, e.title
    FROM results r
    JOIN exams e ON e.exam_id = r.exam_id
    WHERE r.student_id = ?
    ORDER BY r.date_taken DESC
    LIMIT 5
');
$stmtRecent->execute([$studentId]);
$recentResults = $stmtRecent->fetchAll();

$pageTitle = 'Student Dashboard';
$role      = 'student';
require_once ROOT . '/includes/header.php';
?>

<div class="page-header">
  <h1>Welcome back, <?= htmlspecialchars(explode(' ', $name)[0]) ?>! 👋</h1>
  <p>Here's an overview of your examination activity.</p>
</div>

<div class="container section">
  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-number"><?= $totalExams ?></div>
      <div class="stat-label">Available Exams</div>
    </div>
    <div class="stat-card accent">
      <div class="stat-number"><?= $attempted ?></div>
      <div class="stat-label">Exams Taken</div>
    </div>
    <div class="stat-card success">
      <div class="stat-number"><?= $totalExams - $attempted ?></div>
      <div class="stat-label">Remaining</div>
    </div>
    <div class="stat-card warning">
      <div class="stat-number"><?= $avgScore ?>%</div>
      <div class="stat-label">Average Score</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">
    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">⚡ Quick Actions</div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <a href="<?= BASE_URL ?>/student/exams.php"   class="btn btn-primary">📋 Browse Available Exams</a>
        <a href="<?= BASE_URL ?>/student/results.php" class="btn btn-outline">📊 View All My Results</a>
      </div>
    </div>

    <!-- Recent Results -->
    <div class="card">
      <div class="card-header">📊 Recent Results</div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($recentResults)): ?>
          <div class="empty-state" style="padding:32px 24px;">
            <div class="icon">📝</div>
            <p>No exams taken yet. <a href="<?= BASE_URL ?>/student/exams.php">Start one now!</a></p>
          </div>
        <?php else: ?>
          <table>
            <thead><tr><th>Exam</th><th>Score</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentResults as $r):
                $pct = $r['total'] > 0 ? round(($r['score'] / $r['total']) * 100) : 0;
                $cls = $pct >= 60 ? 'badge-success' : 'badge-danger';
              ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><span class="badge <?= $cls ?>"><?= $r['score'] ?>/<?= $r['total'] ?> (<?= $pct ?>%)</span></td>
                <td class="text-muted"><?= date('M j, Y', strtotime($r['date_taken'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
