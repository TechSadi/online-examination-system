<?php
/**
 * Exams available to the signed-in student.
 *
 * Split into what is still to be sat and what is finished, because those are
 * two different questions and mixing them made the list harder to scan than
 * it needed to be.
 *
 * @var list<array<string,mixed>> $exams
 */
$available = [];
$completed = [];

foreach ($exams as $exam) {
    if (!empty($exam['already_taken'])) {
        $completed[] = $exam;
    } else {
        $available[] = $exam;
    }
}

/** One exam card. */
$card = static function (array $exam, bool $taken): void {
    $questionCount = (int) $exam['question_count'];
    $examId        = (int) $exam['exam_id'];
    $empty         = $questionCount === 0;
    ?>
    <article class="exam-card">
      <div class="exam-card-head">
        <h3 class="exam-card-title"><?= e($exam['title']) ?></h3>
        <?php if ($taken): ?>
          <span class="badge badge-success"><?= icon('check') ?> Done</span>
        <?php elseif ($empty): ?>
          <span class="badge badge-warning">Not ready</span>
        <?php endif; ?>
      </div>

      <p class="exam-card-desc">
        <?= e($exam['description'] ?: 'No description was provided for this exam.') ?>
      </p>

      <p class="exam-card-meta">
        <span><?= icon('clock') ?> <?= pluralise((int) $exam['duration'], 'minute') ?></span>
        <span><?= icon('question') ?> <?= pluralise($questionCount, 'question') ?></span>
      </p>

      <div class="exam-card-actions">
        <?php if ($taken): ?>
          <a class="btn btn-secondary" href="<?= e(url('/student/result.php?exam_id=' . $examId)) ?>">
            <?= icon('eye') ?> View result
          </a>
        <?php elseif ($empty): ?>
          <button type="button" class="btn btn-secondary" disabled
                  title="This exam has no questions yet.">Not available yet</button>
        <?php else: ?>
          <a class="btn btn-primary" href="<?= e(url('/student/take_exam.php?exam_id=' . $examId)) ?>">
            Start exam <?= icon('arrow-right') ?>
          </a>
        <?php endif; ?>
      </div>
    </article>
    <?php
};
?>
<div class="container page">
  <div class="page-head">
    <div class="page-head-text">
      <h1 class="page-title">Exams</h1>
      <p class="page-subtitle">
        Each exam can be taken once. The clock starts the moment you open it,
        so begin when you are ready.
      </p>
    </div>
  </div>

  <div class="page-messages">
    <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
  </div>

  <?php if ($exams === []): ?>
    <div class="card">
      <div class="empty">
        <div class="empty-icon"><?= icon('inbox') ?></div>
        <p class="empty-title">No exams have been published yet</p>
        <p class="empty-text">
          When your institution publishes an exam it will appear here. There is
          nothing you need to do in the meantime.
        </p>
      </div>
    </div>
  <?php else: ?>
    <div class="stack-lg">
      <section class="stack" aria-labelledby="available-heading">
        <h2 class="card-title" id="available-heading">
          To sit
          <span class="badge badge-neutral"><?= count($available) ?></span>
        </h2>

        <?php if ($available === []): ?>
          <div class="card">
            <div class="empty empty-compact">
              <div class="empty-icon"><?= icon('check-circle') ?></div>
              <p class="empty-title">Nothing outstanding</p>
              <p class="empty-text">You have sat every exam available to you.</p>
            </div>
          </div>
        <?php else: ?>
          <div class="card-grid">
            <?php foreach ($available as $exam) {
                $card($exam, false);
            } ?>
          </div>
        <?php endif; ?>
      </section>

      <?php if ($completed !== []): ?>
        <section class="stack" aria-labelledby="completed-heading">
          <h2 class="card-title" id="completed-heading">
            Completed
            <span class="badge badge-neutral"><?= count($completed) ?></span>
          </h2>

          <div class="card-grid">
            <?php foreach ($completed as $exam) {
                $card($exam, true);
            } ?>
          </div>
        </section>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
