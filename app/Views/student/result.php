<?php
/**
 * Single exam result.
 *
 * @var array<string,mixed> $result
 * @var int                 $percentage
 * @var bool                $passed
 * @var int                 $passMark
 */
?>
<div class="page-header">
  <h1>&#128202; Exam Result</h1>
  <p><?= e($result['title']) ?></p>
</div>

<div class="container section">
  <div class="result-column">
    <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

    <div class="card">
      <div class="card-body">
        <div class="result-hero">
          <div class="score-circle <?= $passed ? 'pass' : 'fail' ?>">
            <div class="score-pct"><?= $percentage ?>%</div>
            <div class="score-sub">Score</div>
          </div>

          <h2 class="result-headline">
            <?= $passed ? '&#127881; Congratulations!' : '&#128542; Better Luck Next Time' ?>
          </h2>
          <p class="text-muted">
            <?php if ($passed): ?>
              You passed the exam. Great work!
            <?php else: ?>
              You did not reach the passing score (<?= $passMark ?>%). Keep practising!
            <?php endif; ?>
          </p>

          <div class="result-breakdown">
            <div class="item">
              <div class="val val-success"><?= (int) $result['score'] ?></div>
              <div class="lbl">Correct</div>
            </div>
            <div class="item">
              <div class="val val-danger"><?= (int) $result['total'] - (int) $result['score'] ?></div>
              <div class="lbl">Wrong</div>
            </div>
            <div class="item">
              <div class="val"><?= (int) $result['total'] ?></div>
              <div class="lbl">Total</div>
            </div>
          </div>

          <div class="progress-bar progress-bar-centered">
            <div class="progress-bar-fill <?= $passed ? 'fill-success' : 'fill-danger' ?>"
                 style="width:<?= $percentage ?>%"></div>
          </div>

          <p class="text-muted result-timestamp">
            Taken on <?= e(format_datetime($result['date_taken'])) ?>
          </p>
        </div>
      </div>
      <div class="card-footer card-footer-centered">
        <a href="<?= e(url('/student/exams.php')) ?>" class="btn btn-primary">Browse More Exams</a>
        <a href="<?= e(url('/student/results.php')) ?>" class="btn btn-outline">All My Results</a>
      </div>
    </div>
  </div>
</div>
