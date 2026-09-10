<?php
/**
 * Full result history for the signed-in student.
 *
 * @var list<array<string,mixed>> $results
 */
?>
<div class="page-header">
  <h1>&#128202; My Results</h1>
  <p>Your complete examination history.</p>
</div>

<div class="container section">
  <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

  <?php if ($results === []): ?>
    <div class="empty-state">
      <div class="icon">&#128235;</div>
      <p>You haven&rsquo;t taken any exams yet. <a href="<?= e(url('/student/exams.php')) ?>">Start one now!</a></p>
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
          <?php foreach ($results as $i => $result):
              $pct    = percentage((int) $result['score'], (int) $result['total']);
              $passed = is_pass($pct);
          ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= e($result['title']) ?></strong></td>
              <td><?= (int) $result['score'] ?> / <?= (int) $result['total'] ?></td>
              <td>
                <div class="cell-progress">
                  <div class="progress-bar progress-bar-inline">
                    <div class="progress-bar-fill <?= $passed ? 'fill-success' : 'fill-danger' ?>"
                         style="width:<?= $pct ?>%"></div>
                  </div>
                  <span class="cell-progress-value"><?= $pct ?>%</span>
                </div>
              </td>
              <td>
                <span class="badge <?= e(score_badge($pct)) ?>"><?= $passed ? 'PASS' : 'FAIL' ?></span>
              </td>
              <td class="text-muted"><?= e(format_date($result['date_taken'])) ?></td>
              <td>
                <a href="<?= e(url('/student/result.php?exam_id=' . (int) $result['exam_id'])) ?>"
                   class="btn btn-sm btn-outline">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
