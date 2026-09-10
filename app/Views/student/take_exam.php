<?php
/**
 * Live exam interface.
 *
 * Questions are rendered without their correct_answer: the view receives rows
 * from QuestionRepository::forExamWithoutAnswers(), so the answer key is never
 * present in the HTML.
 *
 * The countdown below is drawn from a figure the server calculated, and is
 * only a convenience for the student. The authoritative deadline lives in
 * exam_attempts.expires_at and is re-checked when the answers arrive, so
 * editing this page, stopping the timer, or never running the script at all
 * cannot buy any extra time.
 *
 * There is one timer on this screen, in the bar that stays pinned to the top
 * at every width. Flags are a client-side aid for marking a question to
 * revisit; they are never submitted and never influence the score.
 *
 * The paper is sittable without JavaScript. One question at a time is an
 * enhancement, so the <noscript> block below reveals every question at once
 * and swaps the scripted controls for a plain submit button. Without that,
 * a student with scripting blocked would be looking at an empty page during
 * an exam that is already counting down against them.
 *
 * @var array<string,mixed>       $exam
 * @var list<array<string,mixed>> $questions
 * @var int                       $secondsRemaining server-calculated
 * @var bool                      $resumed          true when re-entering
 */
$letters = ['A', 'B', 'C', 'D'];
$count   = count($questions);
?>
<noscript>
  <style>
    /* Scripting unavailable: show the whole paper and hide the controls
       that only exist because a script drives them. An inline <style> is
       used rather than an inline <script> because the Content-Security
       -Policy allows the first and rightly refuses the second. */
    .question { display: block; }
    .question + .question { margin-top: var(--sp-4); }
    .js-only { display: none !important; }
    .no-js-only { display: flex !important; }
    .exam-layout { padding-bottom: var(--sp-12) !important; }
    .question-nav { position: static !important; box-shadow: none !important; border-top: 0 !important; }
  </style>
</noscript>

<div class="exam-bar">
  <div class="exam-bar-inner">
    <h1 class="exam-bar-title"><?= e($exam['title']) ?></h1>

    <div class="exam-bar-progress">
      <div class="progress">
        <div class="progress-bar" id="exam-progress-fill" style="width:0%"></div>
      </div>
      <span class="exam-progress-count" id="exam-progress-label">0 of <?= $count ?> answered</span>
    </div>

    <p class="timer" id="exam-timer">
      <?= icon('clock') ?>
      <span class="timer-value" id="timer-display"
            data-seconds-remaining="<?= (int) $secondsRemaining ?>">
        <?= sprintf('%02d:%02d', intdiv((int) $secondsRemaining, 60), (int) $secondsRemaining % 60) ?>
      </span>
      <span class="timer-caption">left</span>
    </p>
  </div>
</div>

<?php /* Announced only at milestones. A per-second live region would have a
         screen reader reading the clock aloud continuously. */ ?>
<p class="sr-only" role="status" aria-live="polite" id="timer-announcer"></p>

<div class="container">
  <?php if (!empty($resumed)): ?>
    <div class="alert alert-warning exam-notice" role="status">
      <?= icon('clock') ?>
      <div class="alert-body">
        <p class="alert-title">Attempt resumed</p>
        <p>Your exam clock started when you first opened this page and has kept running since.</p>
      </div>
    </div>
  <?php endif; ?>

  <noscript>
    <div class="alert alert-warning exam-notice" role="status">
      <?= icon('warning') ?>
      <div class="alert-body">
        <p class="alert-title">JavaScript is turned off</p>
        <p>
          Every question is shown below and your answers will still be marked,
          but the countdown will not update on its own. You have
          <?= (int) ceil((int) $secondsRemaining / 60) ?> minute(s) left from
          when this page loaded, and the deadline is enforced by the server.
        </p>
      </div>
    </div>
  </noscript>

  <div class="exam-layout">
    <div>
      <form id="exam-form" method="POST" action="<?= e(url('/student/submit_exam.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">

        <?php foreach ($questions as $index => $question):
            $questionId = (int) $question['question_id'];
        ?>
          <section class="question" data-index="<?= $index ?>"
                   aria-label="Question <?= $index + 1 ?> of <?= $count ?>">
            <div class="question-card">
              <div class="question-head">
                <span class="question-counter">Question <?= $index + 1 ?> of <?= $count ?></span>
                <button type="button" class="btn btn-ghost btn-sm js-only" data-flag
                        data-index="<?= $index ?>" aria-pressed="false">
                  <?= icon('flag') ?> <span data-flag-label>Flag</span>
                </button>
              </div>

              <fieldset>
                <legend class="question-text"><?= e($question['question_text']) ?></legend>

                <div class="options">
                  <?php foreach ([1, 2, 3, 4] as $option): ?>
                    <label class="option">
                      <input type="radio" name="q_<?= $questionId ?>" value="<?= $option ?>">
                      <span class="option-letter" aria-hidden="true"><?= $letters[$option - 1] ?></span>
                      <span class="option-text"><?= e((string) $question['option' . $option]) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
            </div>
          </section>
        <?php endforeach; ?>

        <div class="question-nav">
          <button type="button" id="btn-prev" class="btn btn-secondary js-only">
            <?= icon('arrow-left') ?> Previous
          </button>

          <div class="question-nav-end">
            <button type="button" id="btn-next" class="btn btn-primary js-only">
              Next <?= icon('arrow-right') ?>
            </button>
            <button type="button" id="btn-submit" class="btn btn-success js-only" hidden>
              <?= icon('check') ?> Submit exam
            </button>

            <?php /* Only ever seen when scripting is unavailable: a real
                     submit control, since the one above opens a dialog. */ ?>
            <button type="submit" class="btn btn-success no-js-only">
              <?= icon('check') ?> Submit exam
            </button>
          </div>
        </div>
      </form>
    </div>

    <aside class="exam-rail" aria-label="Exam tools">
      <div class="card js-only">
        <div class="card-header">
          <h2 class="card-title"><?= icon('checklist') ?> Questions</h2>
        </div>
        <div class="card-body card-body-tight">
          <div class="navigator-grid">
            <?php foreach ($questions as $index => $question): ?>
              <button type="button" class="q-nav" data-index="<?= $index ?>"
                      aria-label="Question <?= $index + 1 ?>, unanswered"><?= $index + 1 ?></button>
            <?php endforeach; ?>
          </div>

          <div class="navigator-legend">
            <span class="legend-item"><span class="legend-swatch legend-current"></span> Current question</span>
            <span class="legend-item"><span class="legend-swatch legend-answered"></span> Answered</span>
            <span class="legend-item"><span class="legend-swatch"></span> Not answered</span>
            <span class="legend-item"><span class="legend-swatch legend-flagged"></span> Flagged to revisit</span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body card-body-tight">
          <ul class="exam-help">
            <li><?= icon('check') ?> <span>Choosing an option records it straight away.</span></li>
            <li class="exam-help-keys"><?= icon('arrow-right') ?> <span>Move with the buttons, the numbers above, or <kbd>&larr;</kbd> and <kbd>&rarr;</kbd>.</span></li>
            <li><?= icon('flag') ?> <span>Flag anything you want to come back to.</span></li>
            <li><?= icon('clock') ?> <span>The exam is submitted automatically when the time runs out.</span></li>
          </ul>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php /* Confirmation before handing in. A <dialog> gets focus trapping,
         Escape-to-close and the top layer from the browser. */ ?>
<dialog class="modal" id="submit-dialog" aria-labelledby="submit-dialog-title">
  <div class="modal-body">
    <div class="modal-icon modal-icon-info"><?= icon('check-circle') ?></div>
    <div>
      <h2 class="modal-title" id="submit-dialog-title">Submit your exam?</h2>
      <p class="modal-text">
        Once you hand in you cannot return to these questions, and your result
        is worked out immediately.
      </p>
      <div id="submit-unanswered" hidden></div>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dialog-cancel>Keep working</button>
    <button type="button" class="btn btn-success" id="confirm-submit">
      <?= icon('check') ?> Submit exam
    </button>
  </div>
</dialog>

<div class="exam-submitting" id="exam-submitting" hidden>
  <div class="exam-submitting-inner">
    <div class="spinner" aria-hidden="true"></div>
    <p role="status">Submitting your answers&hellip;</p>
    <small>Please keep this page open.</small>
  </div>
</div>
