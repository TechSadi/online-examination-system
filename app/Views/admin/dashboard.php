<?php
/**
 * Admin dashboard.
 *
 * @var string                    $adminName
 * @var array<string,int>         $stats
 * @var list<array<string,mixed>> $recentAttempts
 */
?>
<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?= e($adminName) ?>. Here is the state of the system.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('/admin/add_exam.php')) ?>">
      <?= icon('plus') ?> New exam
    </a>
  </div>
</div>

<div class="stack-lg">
  <section aria-labelledby="stats-heading">
    <h2 class="sr-only" id="stats-heading">System totals</h2>
    <div class="stat-grid">
      <div class="stat">
        <p class="stat-head"><?= icon('students') ?> Students</p>
        <p class="stat-value"><?= number_format((int) $stats['students']) ?></p>
      </div>
      <div class="stat">
        <p class="stat-head"><?= icon('exams') ?> Exams</p>
        <p class="stat-value"><?= number_format((int) $stats['exams']) ?></p>
      </div>
      <div class="stat">
        <p class="stat-head"><?= icon('question') ?> Questions</p>
        <p class="stat-value"><?= number_format((int) $stats['questions']) ?></p>
      </div>
      <div class="stat stat-accent">
        <p class="stat-head"><?= icon('results') ?> Attempts recorded</p>
        <p class="stat-value"><?= number_format((int) $stats['attempts']) ?></p>
      </div>
      <div class="stat">
        <p class="stat-head"><?= icon('admins') ?> Administrators</p>
        <p class="stat-value"><?= number_format((int) $stats['admins']) ?></p>
      </div>
    </div>
  </section>

  <section class="card">
    <div class="card-header">
      <h2 class="card-title"><?= icon('clock') ?> Recent attempts</h2>
      <?php if ($recentAttempts !== []): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/results.php')) ?>">
          All results <?= icon('arrow-right') ?>
        </a>
      <?php endif; ?>
    </div>

    <?php if ($recentAttempts === []): ?>
      <div class="empty empty-compact">
        <div class="empty-icon"><?= icon('inbox') ?></div>
        <p class="empty-title">No exams have been sat yet</p>
        <p class="empty-text">
          <?php if ((int) $stats['exams'] === 0): ?>
            Create an exam and add some questions to it, and attempts will start appearing here.
          <?php else: ?>
            As soon as a student completes an exam, their attempt will show up here.
          <?php endif; ?>
        </p>
        <?php if ((int) $stats['exams'] === 0): ?>
          <div class="empty-actions">
            <a class="btn btn-primary" href="<?= e(url('/admin/add_exam.php')) ?>">
              <?= icon('plus') ?> Create the first exam
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="table table-stack">
          <thead>
            <tr>
              <th scope="col">Student</th>
              <th scope="col">Exam</th>
              <th scope="col">Score</th>
              <th scope="col">Result</th>
              <th scope="col">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentAttempts as $attempt):
                $pct    = percentage((int) $attempt['score'], (int) $attempt['total']);
                $passed = is_pass($pct);
            ?>
              <tr>
                <td data-label="Student" class="cell-primary cell-lead"><?= e($attempt['student_name']) ?></td>
                <td data-label="Exam"><?= e($attempt['exam_title']) ?></td>
                <td data-label="Score">
                  <div class="meter">
                    <div class="progress <?= $passed ? 'progress-success' : 'progress-danger' ?>">
                      <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="meter-value"><?= $pct ?>%</span>
                  </div>
                </td>
                <td data-label="Result">
                  <span class="status <?= $passed ? 'status-success' : 'status-danger' ?>">
                    <?= $passed ? 'Passed' : 'Not passed' ?>
                  </span>
                </td>
                <td data-label="Date" class="cell-muted"><?= e(format_date($attempt['date_taken'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
