<?php
/**
 * Question management for a single exam: the add/edit form beside the list.
 *
 * @var array<string,mixed>       $exam
 * @var list<array<string,mixed>> $questions
 * @var array<string,mixed>|null  $editQuestion
 * @var array<string,mixed>       $old
 */
$letters = ['A', 'B', 'C', 'D'];
$isEdit  = $editQuestion !== null;
$examId  = (int) $exam['exam_id'];

$field = static function (string $key, mixed $fallback = '') use ($old, $editQuestion) {
    return (string) ($old[$key] ?? ($editQuestion[$key] ?? $fallback));
};

$correct = (string) ($old['correct_answer'] ?? ($editQuestion['correct_answer'] ?? ''));
?>
<nav aria-label="Breadcrumb">
  <ol class="breadcrumb">
    <li><a href="<?= e(url('/admin/exams.php')) ?>">Exams</a></li>
    <li><?= icon('chevron-right') ?></li>
    <li><a href="<?= e(url('/admin/edit_exam.php?id=' . $examId)) ?>"><?= e($exam['title']) ?></a></li>
    <li><?= icon('chevron-right') ?></li>
    <li aria-current="page">Questions</li>
  </ol>
</nav>

<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Questions</h1>
    <p class="page-subtitle">
      <?= e($exam['title']) ?> &middot; <?= pluralise((int) $exam['duration'], 'minute') ?>
      &middot; <?= pluralise(count($questions), 'question') ?>
    </p>
  </div>
  <div class="page-actions">
    <a class="btn btn-secondary" href="<?= e(url('/admin/edit_exam.php?id=' . $examId)) ?>">
      <?= icon('edit') ?> Exam settings
    </a>
  </div>
</div>

<?php if ($questions === []): ?>
  <div class="alert alert-info" role="status">
    <?= icon('info') ?>
    <div class="alert-body">
      <p class="alert-title">This exam is not ready to sit yet</p>
      <p>Students cannot start an exam that has no questions. Add the first one below.</p>
    </div>
  </div>
<?php endif; ?>

<div class="grid-split">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <?= $isEdit ? icon('edit') . ' Edit question' : icon('plus') . ' Add a question' ?>
      </h2>
    </div>

    <div class="card-body">
      <form method="POST" novalidate data-loading>
        <?= csrf_field() ?>
        <input type="hidden" name="exam_id" value="<?= $examId ?>">
        <input type="hidden" name="question_id" value="<?= (int) ($editQuestion['question_id'] ?? 0) ?>">

        <div class="field">
          <label class="field-label" for="question_text">Question</label>
          <textarea id="question_text" name="question_text" class="field-input" rows="3"
                    placeholder="What do you want to ask?"><?= e($field('question_text')) ?></textarea>
        </div>

        <fieldset class="field">
          <legend class="field-label">Answer options</legend>

          <?php foreach ([1, 2, 3, 4] as $n): ?>
            <div class="field field-option">
              <label class="sr-only" for="option<?= $n ?>">Option <?= $letters[$n - 1] ?></label>
              <span class="field-option-letter" aria-hidden="true"><?= $letters[$n - 1] ?></span>
              <input type="text" id="option<?= $n ?>" name="option<?= $n ?>" class="field-input"
                     placeholder="Option <?= $letters[$n - 1] ?>"
                     value="<?= e($field('option' . $n)) ?>">
            </div>
          <?php endforeach; ?>
        </fieldset>

        <div class="field">
          <label class="field-label" for="correct_answer">Correct answer</label>
          <select id="correct_answer" name="correct_answer" class="field-input">
            <option value="">Choose the correct option&hellip;</option>
            <?php foreach ([1, 2, 3, 4] as $n): ?>
              <option value="<?= $n ?>" <?= $correct === (string) $n ? 'selected' : '' ?>>
                Option <?= $letters[$n - 1] ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="field-hint">Never shown to students; used only for marking.</small>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" data-loading-label="Saving&hellip;">
            <?= $isEdit ? icon('save') . ' Update question' : icon('plus') . ' Add question' ?>
          </button>
          <?php if ($isEdit): ?>
            <a class="btn btn-secondary"
               href="<?= e(url('/admin/questions.php?exam_id=' . $examId)) ?>">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div>
    <?php if ($questions === []): ?>
      <div class="card">
        <div class="empty empty-compact">
          <div class="empty-icon"><?= icon('question') ?></div>
          <p class="empty-title">No questions yet</p>
          <p class="empty-text">
            Questions you add appear here, with the correct answer highlighted so
            you can check the paper at a glance.
          </p>
        </div>
      </div>
    <?php else: ?>
      <div class="stack-sm">
        <?php foreach ($questions as $i => $question):
            $questionId = (int) $question['question_id'];
            $editing    = $isEdit && (int) $editQuestion['question_id'] === $questionId;
        ?>
          <article class="card question-item <?= $editing ? 'is-editing' : '' ?>">
            <div class="card-body card-body-tight">
              <div class="question-item-head">
                <p class="question-item-text">
                  <span class="question-item-number"><?= $i + 1 ?></span><?= e($question['question_text']) ?>
                </p>

                <div class="btn-row">
                  <a class="btn btn-secondary btn-sm btn-icon"
                     href="<?= e(url('/admin/questions.php?exam_id=' . $examId . '&edit_q=' . $questionId)) ?>"
                     aria-label="Edit question <?= $i + 1 ?>">
                    <?= icon('edit') ?>
                  </a>
                  <form method="POST" action="<?= e(url('/admin/questions.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exam_id" value="<?= $examId ?>">
                    <input type="hidden" name="question_id" value="<?= $questionId ?>">
                    <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"
                            aria-label="Delete question <?= $i + 1 ?>"
                            data-confirm-title="Delete this question?"
                            data-confirm="Question <?= $i + 1 ?> will be removed from this exam. This cannot be undone."
                            data-confirm-label="Delete question">
                      <?= icon('trash') ?>
                    </button>
                  </form>
                </div>
              </div>

              <div class="option-grid">
                <?php foreach ([1, 2, 3, 4] as $n):
                    $isCorrect = (int) $question['correct_answer'] === $n;
                ?>
                  <p class="option-chip <?= $isCorrect ? 'is-correct' : '' ?>">
                    <span class="option-chip-letter"><?= $letters[$n - 1] ?></span>
                    <?= e($question['option' . $n]) ?>
                    <?php if ($isCorrect): ?>
                      <?= icon('check', '', 'Correct answer') ?>
                    <?php endif; ?>
                  </p>
                <?php endforeach; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
