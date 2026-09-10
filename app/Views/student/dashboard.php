<?php
/**
 * Student dashboard.
 *
 * The four questions this page has to answer, in order: where am I, what is
 * my progress, what should I do next, and what happened recently. The "next
 * exam" panel is the only filled surface on the page because it is the only
 * part that is an instruction rather than a report.
 *
 * @var string                     $firstName
 * @var int                        $totalExams
 * @var int                        $attempted
 * @var int                        $remaining
 * @var int                        $avgScore
 * @var list<array<string,mixed>>  $recentResults
 * @var array<string,mixed>|null   $nextExam
 */
?>
<div class="container page">
  <div class="page-head">
    <div class="page-head-text">
      <h1 class="greeting-title">Hello, <?= e($firstName) ?></h1>
      <p class="greeting-sub">Here is where your examinations stand today.</p>
    </div>
  </div>

  <div class="page-messages">
    <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
  </div>

  <div class="stack-lg">
    <?php if ($nextExam !== null): ?>
      <section class="next-up">
        <div>
          <p class="next-up-label"><?= icon('zap') ?> Next up</p>
          <p class="next-up-title"><?= e($nextExam['title']) ?></p>
          <p class="next-up-meta">
            <span><?= icon('clock') ?> <?= pluralise((int) $nextExam['duration'], 'minute') ?></span>
            <span><?= icon('question') ?> <?= pluralise((int) $nextExam['question_count'], 'question') ?></span>
          </p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/student/take_exam.php?exam_id=' . (int) $nextExam['exam_id'])) ?>">
          Start exam <?= icon('arrow-right') ?>
        </a>
      </section>
    <?php elseif ($totalExams > 0): ?>
      <section class="next-up">
        <div>
          <p class="next-up-label"><?= icon('check-circle') ?> All caught up</p>
          <p class="next-up-title">You have completed every exam available to you.</p>
          <p class="next-up-meta"><span>New exams will appear here as soon as they are published.</span></p>
        </div>
        <a class="btn btn-secondary" href="<?= e(url('/student/results.php')) ?>">Review results</a>
      </section>
    <?php endif; ?>

    <section aria-labelledby="stats-heading">
      <h2 class="sr-only" id="stats-heading">Your figures</h2>
      <div class="stat-grid">
        <div class="stat">
          <p class="stat-head"><?= icon('exams') ?> Exams available</p>
          <p class="stat-value"><?= (int) $totalExams ?></p>
        </div>
        <div class="stat">
          <p class="stat-head"><?= icon('check-circle') ?> Completed</p>
          <p class="stat-value"><?= (int) $attempted ?></p>
        </div>
        <div class="stat">
          <p class="stat-head"><?= icon('clock') ?> Remaining</p>
          <p class="stat-value"><?= (int) $remaining ?></p>
        </div>
        <div class="stat <?= $attempted > 0 ? 'stat-accent' : '' ?>">
          <p class="stat-head"><?= icon('trend') ?> Average score</p>
          <p class="stat-value <?= $attempted > 0 ? '' : 'stat-value-empty' ?>">
            <?= $attempted > 0 ? (int) $avgScore . '%' : '&mdash;' ?>
          </p>
          <?php if ($attempted > 0): ?>
            <p class="stat-meta">Across <?= pluralise((int) $attempted, 'exam') ?></p>
          <?php else: ?>
            <p class="stat-meta">No exams taken yet</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card-header">
        <h2 class="card-title"><?= icon('results') ?> Recent results</h2>
        <?php if ($recentResults !== []): ?>
          <a class="btn btn-ghost btn-sm" href="<?= e(url('/student/results.php')) ?>">
            View all <?= icon('arrow-right') ?>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($recentResults === []): ?>
        <div class="empty empty-compact">
          <div class="empty-icon"><?= icon('inbox') ?></div>
          <p class="empty-title">No results yet</p>
          <p class="empty-text">
            Once you finish an exam, your score and a breakdown of how you did will show up here.
          </p>
          <div class="empty-actions">
            <a class="btn btn-primary" href="<?= e(url('/student/exams.php')) ?>">Browse exams</a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table class="table table-stack">
            <thead>
              <tr>
                <th scope="col">Exam</th>
                <th scope="col">Score</th>
                <th scope="col">Result</th>
                <th scope="col">Taken</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentResults as $result):
                  $pct    = percentage((int) $result['score'], (int) $result['total']);
                  $passed = is_pass($pct);
              ?>
                <tr>
                  <td data-label="Exam" class="cell-primary cell-lead"><?= e($result['title']) ?></td>
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
                  <td data-label="Taken" class="cell-muted"><?= e(format_date($result['date_taken'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
