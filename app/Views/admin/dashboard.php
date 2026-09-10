<?php
/**
 * Admin dashboard.
 *
 * @var string                    $adminName
 * @var array<string,int>         $stats
 * @var list<array<string,mixed>> $recentAttempts
 */
?>
<div class="page-heading">
  <h2>Admin Dashboard</h2>
  <p class="text-muted">Welcome back, <strong><?= e($adminName) ?></strong>!</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-number"><?= (int) $stats['students'] ?></div>
    <div class="stat-label">Registered Students</div>
  </div>
  <div class="stat-card accent">
    <div class="stat-number"><?= (int) $stats['exams'] ?></div>
    <div class="stat-label">Total Exams</div>
  </div>
  <div class="stat-card success">
    <div class="stat-number"><?= (int) $stats['questions'] ?></div>
    <div class="stat-label">Total Questions</div>
  </div>
  <div class="stat-card warning">
    <div class="stat-number"><?= (int) $stats['attempts'] ?></div>
    <div class="stat-label">Exam Attempts</div>
  </div>
  <div class="stat-card violet">
    <div class="stat-number"><?= (int) $stats['admins'] ?></div>
    <div class="stat-label">Admin Accounts</div>
  </div>
</div>

<div class="button-row">
  <a href="<?= e(url('/admin/add_exam.php')) ?>" class="btn btn-primary">&#10133; New Exam</a>
  <a href="<?= e(url('/admin/students.php')) ?>" class="btn btn-outline">&#127891; View Students</a>
  <a href="<?= e(url('/admin/results.php')) ?>" class="btn btn-outline">&#128202; View Results</a>
  <a href="<?= e(url('/admin/admins.php')) ?>" class="btn btn-outline">&#128737; Manage Admins</a>
</div>

<div class="card">
  <div class="card-header">&#128336; Recent Exam Attempts</div>
  <?php if ($recentAttempts === []): ?>
    <div class="card-body">
      <div class="empty-state empty-state-inline">
        <div class="icon">&#128235;</div>
        <p>No exam attempts yet.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="table-wrapper table-wrapper-flush">
      <table>
        <thead>
          <tr><th>Student</th><th>Exam</th><th>Score</th><th>%</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentAttempts as $attempt):
              $pct = percentage((int) $attempt['score'], (int) $attempt['total']);
          ?>
            <tr>
              <td><strong><?= e($attempt['student_name']) ?></strong></td>
              <td><?= e($attempt['exam_title']) ?></td>
              <td><?= (int) $attempt['score'] ?>/<?= (int) $attempt['total'] ?></td>
              <td><span class="badge <?= e(score_badge($pct)) ?>"><?= $pct ?>%</span></td>
              <td class="text-muted"><?= e(format_date($attempt['date_taken'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
