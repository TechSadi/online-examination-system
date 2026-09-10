<?php
/**
 * A single exam result.
 *
 * @var array<string,mixed> $result
 * @var int                 $percentage
 * @var bool                $passed
 * @var int                 $passMark
 */
$score   = (int) $result['score'];
$total   = (int) $result['total'];
$wrong   = max(0, $total - $score);
$expired = ($result['status'] ?? '') === 'expired';
?>
<div class="container container-narrow page">
  <nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
      <li><a href="<?= e(url('/student/results.php')) ?>">My results</a></li>
      <li><?= icon('chevron-right') ?></li>
      <li aria-current="page"><?= e($result['title']) ?></li>
    </ol>
  </nav>

  <div class="page-head">
    <div class="page-head-text">
      <h1 class="page-title"><?= e($result['title']) ?></h1>
      <p class="page-subtitle">
        Submitted <?= e(format_datetime($result['date_taken'])) ?>
      </p>
    </div>
  </div>

  <div class="page-messages">
    <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

    <?php if ($expired): ?>
      <div class="alert alert-warning" role="status">
        <?= icon('clock') ?>
        <div class="alert-body">
          <p class="alert-title">This attempt ran out of time</p>
          <p>
            It was not handed in before the exam clock reached zero, so it was
            recorded with a score of zero.
          </p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="result-head">
      <div class="score-ring <?= $passed ? 'score-pass' : 'score-fail' ?>"
           style="--value: <?= $percentage ?>"
           role="img"
           aria-label="Score: <?= $percentage ?> per cent">
        <span class="score-ring-value"><?= $percentage ?>%</span>
        <span class="score-ring-label">Score</span>
      </div>

      <div class="result-summary">
        <h2 class="result-verdict">
          <?= $passed ? 'You passed' : 'You did not pass' ?>
        </h2>
        <p class="result-note">
          <?php if ($passed): ?>
            You answered <?= pluralise($score, 'question') ?> correctly out of
            <?= $total ?>, meeting the <?= (int) $passMark ?>% needed to pass.
          <?php else: ?>
            You needed <?= (int) $passMark ?>% to pass this exam and scored
            <?= $percentage ?>%. Reviewing the material and sitting a similar
            exam is the fastest way to close that gap.
          <?php endif; ?>
        </p>

        <div class="btn-row result-actions">
          <a class="btn btn-primary" href="<?= e(url('/student/exams.php')) ?>">
            Browse exams <?= icon('arrow-right') ?>
          </a>
          <a class="btn btn-secondary" href="<?= e(url('/student/results.php')) ?>">
            All my results
          </a>
        </div>
      </div>
    </div>

    <div class="result-figures">
      <div class="result-figure">
        <p class="result-figure-value text-success"><?= $score ?></p>
        <p class="result-figure-label">Correct</p>
      </div>
      <div class="result-figure">
        <p class="result-figure-value <?= $wrong > 0 ? 'text-danger' : '' ?>"><?= $wrong ?></p>
        <p class="result-figure-label">Incorrect</p>
      </div>
      <div class="result-figure">
        <p class="result-figure-value"><?= $total ?></p>
        <p class="result-figure-label">Questions</p>
      </div>
    </div>
  </div>
</div>
