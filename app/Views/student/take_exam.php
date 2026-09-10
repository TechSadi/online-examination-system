<?php
/**
 * Live exam interface.
 *
 * Questions are rendered without their correct_answer: the view receives rows
 * from QuestionRepository::forExamWithoutAnswers(), so the answer key is never
 * present in the HTML.
 *
 * @var array<string,mixed>       $exam
 * @var list<array<string,mixed>> $questions
 */
$letters = ['A', 'B', 'C', 'D'];
$count   = count($questions);
?>
<div class="page-header page-header-compact">
  <h1>&#128221; <?= e($exam['title']) ?></h1>
  <p>Answer all questions before the timer runs out.</p>
</div>

<div class="container">
  <div class="exam-progress">
    <div class="exam-progress-head">
      <span class="exam-progress-title">Progress</span>
      <span id="exam-progress-label" class="exam-progress-count">0 / <?= $count ?> answered</span>
    </div>
    <div class="progress-bar">
      <div class="progress-bar-fill" id="exam-progress-fill" style="width:0%"></div>
    </div>
  </div>

  <div class="exam-layout">
    <div>
      <form id="exam-form" method="POST" action="<?= e(url('/student/submit_exam.php')) ?>">
        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">

        <?php foreach ($questions as $index => $question): ?>
          <div class="question-slide" data-index="<?= $index ?>">
            <div class="question-card">
              <div class="question-number">Question <?= $index + 1 ?> of <?= $count ?></div>
              <div class="question-text"><?= e($question['question_text']) ?></div>

              <div class="options-list">
                <?php foreach ([1, 2, 3, 4] as $option):
                    $text = (string) $question['option' . $option];
                ?>
                  <label class="option-item">
                    <input type="radio" name="q_<?= (int) $question['question_id'] ?>" value="<?= $option ?>">
                    <span class="option-letter"><?= $letters[$option - 1] ?></span>
                    <span class="option-label"><?= e($text) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="question-nav-bar exam-nav-bar">
          <button type="button" id="btn-prev" class="btn btn-outline">&larr; Previous</button>
          <button type="button" id="btn-next" class="btn btn-primary">Next &rarr;</button>
          <button type="button" id="btn-submit" class="btn btn-success" hidden>&#9989; Submit Exam</button>
        </div>
      </form>
    </div>

    <div>
      <div class="timer-widget" id="timer-widget">
        <div class="timer-label">Time Remaining</div>
        <div class="timer-display" id="timer-display" data-duration="<?= (int) $exam['duration'] ?>"
             role="timer" aria-live="polite" aria-atomic="true">
          <?= sprintf('%02d:00', (int) $exam['duration']) ?>
        </div>
        <div class="timer-label" id="timer-label">&#9201; Stay focused!</div>
      </div>

      <div class="card mt-2">
        <div class="card-header card-header-sm">Question Navigator</div>
        <div class="card-body card-body-sm">
          <div class="question-nav-grid">
            <?php foreach ($questions as $index => $question): ?>
              <button type="button" class="q-nav-btn" data-index="<?= $index ?>"
                      aria-label="Go to question <?= $index + 1 ?>"><?= $index + 1 ?></button>
            <?php endforeach; ?>
          </div>
          <div class="q-nav-legend">
            <div><span class="dot dot-current"></span> Current</div>
            <div><span class="dot dot-answered"></span> Answered</div>
            <div><span class="dot dot-unanswered"></span> Unanswered</div>
          </div>
        </div>
      </div>

      <div class="card mt-2">
        <div class="card-body exam-instructions">
          <strong>&#9888; Instructions:</strong><br>
          &bull; Use arrow keys or buttons to navigate<br>
          &bull; Clicking an option saves your answer<br>
          &bull; The exam auto-submits when time is up<br>
          &bull; Do not close or refresh the page
        </div>
      </div>
    </div>
  </div>
</div>
