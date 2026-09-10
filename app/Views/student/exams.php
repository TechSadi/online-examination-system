<?php
/**
 * Exams available to the signed-in student.
 *
 * @var list<array<string,mixed>> $exams
 */
?>
<div class="page-header">
  <h1>&#128203; Available Exams</h1>
  <p>Select an exam below to begin. Each exam can only be taken once.</p>
</div>

<div class="container">
  <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

  <?php if ($exams === []): ?>
    <div class="empty-state empty-state-tall">
      <div class="icon">&#128235;</div>
      <p>No exams available at the moment. Check back later!</p>
    </div>
  <?php else: ?>
    <div class="exams-grid">
      <?php foreach ($exams as $exam):
          $questionCount = (int) $exam['question_count'];
          $alreadyTaken  = !empty($exam['already_taken']);
      ?>
        <div class="exam-card">
          <div class="exam-card-header">
            <h3><?= e($exam['title']) ?></h3>
            <div class="duration">
              &#9201; <?= (int) $exam['duration'] ?> minutes
              &nbsp;|&nbsp; &#10067; <?= $questionCount ?> questions
            </div>
          </div>
          <div class="exam-card-body">
            <p><?= e($exam['description'] ?: 'No description provided.') ?></p>

            <?php if ($alreadyTaken): ?>
              <span class="badge badge-success badge-block">&#9989; Completed</span>
              <a href="<?= e(url('/student/result.php?exam_id=' . (int) $exam['exam_id'])) ?>"
                 class="btn btn-outline btn-sm">View Result</a>
            <?php elseif ($questionCount === 0): ?>
              <span class="badge badge-warning">&#9888; No questions yet</span>
            <?php else: ?>
              <a href="<?= e(url('/student/take_exam.php?exam_id=' . (int) $exam['exam_id'])) ?>"
                 class="btn btn-primary btn-sm">Start Exam &rarr;</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
