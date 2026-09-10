<?php
/**
 * dashboard.php – Admin dashboard with system overview
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db = getDB();

$totalStudents  = $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalExams     = $db->query('SELECT COUNT(*) FROM exams')->fetchColumn();
$totalQuestions = $db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totalAttempts  = $db->query('SELECT COUNT(*) FROM results')->fetchColumn();
$totalAdmins    = $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();

// Recent exam attempts
$recentAttempts = $db->query('
    SELECT r.score, r.total, r.date_taken, s.name AS student_name, e.title AS exam_title
    FROM results r
    JOIN students s ON s.student_id = r.student_id
    JOIN exams    e ON e.exam_id    = r.exam_id
    ORDER BY r.date_taken DESC
    LIMIT 8
')->fetchAll();

$pageTitle = 'Admin Dashboard';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:4px;">Admin Dashboard</h2>
    <p class="text-muted">Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']) ?></strong>!</p>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?= $totalStudents ?></div>
        <div class="stat-label">Registered Students</div>
      </div>
      <div class="stat-card accent">
        <div class="stat-number"><?= $totalExams ?></div>
        <div class="stat-label">Total Exams</div>
      </div>
      <div class="stat-card success">
        <div class="stat-number"><?= $totalQuestions ?></div>
        <div class="stat-label">Total Questions</div>
      </div>
      <div class="stat-card warning">
        <div class="stat-number"><?= $totalAttempts ?></div>
        <div class="stat-label">Exam Attempts</div>
      </div>
      <div class="stat-card" style="border-top-color:#7c3aed;">
        <div class="stat-number" style="color:#7c3aed;"><?= $totalAdmins ?></div>
        <div class="stat-label">Admin Accounts</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px;">
      <a href="<?= BASE_URL ?>/admin/add_exam.php"     class="btn btn-primary">➕ New Exam</a>
      <a href="<?= BASE_URL ?>/admin/students.php"     class="btn btn-outline">🎓 View Students</a>
      <a href="<?= BASE_URL ?>/admin/results.php"      class="btn btn-outline">📊 View Results</a>
      <a href="<?= BASE_URL ?>/admin/admins.php"       class="btn btn-outline">🛡️ Manage Admins</a>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <div class="card-header">🕒 Recent Exam Attempts</div>
      <?php if (empty($recentAttempts)): ?>
        <div class="card-body">
          <div class="empty-state" style="padding:32px 0;">
            <div class="icon">📭</div>
            <p>No exam attempts yet.</p>
          </div>
        </div>
      <?php else: ?>
        <div class="table-wrapper" style="border-radius:0;box-shadow:none;border:none;">
          <table>
            <thead>
              <tr><th>Student</th><th>Exam</th><th>Score</th><th>%</th><th>Date</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentAttempts as $a):
                $pct    = $a['total'] > 0 ? round(($a['score'] / $a['total']) * 100) : 0;
                $passed = $pct >= 60;
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($a['student_name']) ?></strong></td>
                <td><?= htmlspecialchars($a['exam_title']) ?></td>
                <td><?= $a['score'] ?>/<?= $a['total'] ?></td>
                <td><span class="badge <?= $passed ? 'badge-success' : 'badge-danger' ?>"><?= $pct ?>%</span></td>
                <td class="text-muted"><?= date('M j, Y', strtotime($a['date_taken'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
