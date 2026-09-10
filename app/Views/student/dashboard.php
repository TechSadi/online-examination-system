<?php
/**
 * Student dashboard.
 *
 * @var string                     $firstName
 * @var int                        $totalExams
 * @var int                        $attempted
 * @var int                        $remaining
 * @var int                        $avgScore
 * @var list<array<string,mixed>>  $recentResults
 */
?>
<div class="page-header">
  <h1>Welcome back, <?= e($firstName) ?>! &#128075;</h1>
  <p>Here&rsquo;s an overview of your examination activity.</p>
</div>

<div class="container section">
  <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-number"><?= (int) $totalExams ?></div>
      <div class="stat-label">Available Exams</div>
    </div>
    <div class="stat-card accent">
      <div class="stat-number"><?= (int) $attempted ?></div>
      <div class="stat-label">Exams Taken</div>
    </div>
    <div class="stat-card success">
      <div class="stat-number"><?= (int) $remaining ?></div>
      <div class="stat-label">Remaining</div>
    </div>
    <div class="stat-card warning">
      <div class="stat-number"><?= (int) $avgScore ?>%</div>
      <div class="stat-label">Average Score</div>
    </div>
  </div>

  <div class="dashboard-grid">
    <div class="card">
      <div class="card-header">&#9889; Quick Actions</div>
      <div class="card-body card-body-stack">
        <a href="<?= e(url('/student/exams.php')) ?>" class="btn btn-primary">&#128203; Browse Available Exams</a>
        <a href="<?= e(url('/student/results.php')) ?>" class="btn btn-outline">&#128202; View All My Results</a>
      </div>
    </div>

    <div class="card">
      <div class="card-header">&#128202; Recent Results</div>
      <div class="card-body card-body-flush">
        <?php if ($recentResults === []): ?>
          <div class="empty-state empty-state-inline">
            <div class="icon">&#128221;</div>
            <p>No exams taken yet. <a href="<?= e(url('/student/exams.php')) ?>">Start one now!</a></p>
          </div>
        <?php else: ?>
          <table>
            <thead><tr><th>Exam</th><th>Score</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentResults as $result):
                  $pct = percentage((int) $result['score'], (int) $result['total']);
              ?>
                <tr>
                  <td><?= e($result['title']) ?></td>
                  <td>
                    <span class="badge <?= e(score_badge($pct)) ?>">
                      <?= (int) $result['score'] ?>/<?= (int) $result['total'] ?> (<?= $pct ?>%)
                    </span>
                  </td>
                  <td class="text-muted"><?= e(format_date($result['date_taken'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
