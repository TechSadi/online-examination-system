<?php
/**
 * Full result history for the signed-in student.
 *
 * The filter is a set of links rather than a form: each view has its own URL,
 * so it can be bookmarked and the browser's back button behaves.
 *
 * @var list<array<string,mixed>> $results   already filtered for display
 * @var list<array<string,mixed>> $allResults every result, for the counts
 * @var string                    $filter    'all' | 'passed' | 'failed'
 * @var int                       $passMark
 */
$counts = ['all' => 0, 'passed' => 0, 'failed' => 0];

foreach ($allResults as $row) {
    $counts['all']++;
    $counts[is_pass(percentage((int) $row['score'], (int) $row['total'])) ? 'passed' : 'failed']++;
}

$views = ['all' => 'All', 'passed' => 'Passed', 'failed' => 'Not passed'];
?>
<div class="container page">
  <div class="page-head">
    <div class="page-head-text">
      <h1 class="page-title">My results</h1>
      <p class="page-subtitle">
        Every exam you have completed. The pass mark is <?= (int) $passMark ?>%.
      </p>
    </div>
  </div>

  <div class="page-messages">
    <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
  </div>

  <?php if ($allResults === []): ?>
    <div class="card">
      <div class="empty">
        <div class="empty-icon"><?= icon('results') ?></div>
        <p class="empty-title">Nothing to report yet</p>
        <p class="empty-text">
          Your results appear here as soon as you finish your first exam,
          together with a breakdown of how you did.
        </p>
        <div class="empty-actions">
          <a class="btn btn-primary" href="<?= e(url('/student/exams.php')) ?>">
            Browse exams <?= icon('arrow-right') ?>
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="table-card">
      <div class="table-caption">
        <nav class="segmented" aria-label="Filter results">
          <?php foreach ($views as $key => $label): ?>
            <a class="segmented-item"
               href="<?= e(url('/student/results.php' . ($key === 'all' ? '' : '?filter=' . $key))) ?>"
               <?= $filter === $key ? 'aria-current="true"' : '' ?>>
              <?= e($label) ?> <span class="segmented-count"><?= $counts[$key] ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
        <span><?= pluralise(count($results), 'result') ?> shown</span>
      </div>

      <?php if ($results === []): ?>
        <div class="empty empty-compact">
          <div class="empty-icon"><?= icon('filter') ?></div>
          <p class="empty-title">No results in this view</p>
          <p class="empty-text">
            You have no <?= $filter === 'passed' ? 'passed' : 'failed' ?> exams yet.
          </p>
          <div class="empty-actions">
            <a class="btn btn-secondary" href="<?= e(url('/student/results.php')) ?>">Show all results</a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table class="table table-stack">
            <thead>
              <tr>
                <th scope="col">Exam</th>
                <th scope="col">Score</th>
                <th scope="col">Percentage</th>
                <th scope="col">Result</th>
                <th scope="col">Taken</th>
                <th scope="col"><span class="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $result):
                  $pct    = percentage((int) $result['score'], (int) $result['total']);
                  $passed = is_pass($pct);
              ?>
                <tr>
                  <td data-label="Exam" class="cell-primary cell-lead"><?= e($result['title']) ?></td>
                  <td data-label="Score"><?= (int) $result['score'] ?> of <?= (int) $result['total'] ?></td>
                  <td data-label="Percentage">
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
                  <td class="cell-actions" data-label="">
                    <a class="btn btn-secondary btn-sm"
                       href="<?= e(url('/student/result.php?exam_id=' . (int) $result['exam_id'])) ?>">
                      View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
