<?php
/**
 * Question management for a single exam: add/edit form beside the list.
 *
 * @var array<string,mixed>       $exam
 * @var list<array<string,mixed>> $questions
 * @var array<string,mixed>|null  $editQuestion
 * @var array<string,mixed>       $old
 */
$letters  = ['A', 'B', 'C', 'D'];
$isEdit   = $editQuestion !== null;
$field    = static function (string $key, mixed $fallback = '') use ($old, $editQuestion) {
    return (string) ($old[$key] ?? ($editQuestion[$key] ?? $fallback));
};
$correct  = (string) ($old['correct_answer'] ?? ($editQuestion['correct_answer'] ?? ''));
?>
<div class="page-heading page-heading-split">
  <div>
    <h2>Questions</h2>
    <p class="text-muted">
      Exam: <strong><?= e($exam['title']) ?></strong>
      &nbsp;|&nbsp; <?= (int) $exam['duration'] ?> min
      &nbsp;|&nbsp; <?= count($questions) ?> question(s)
    </p>
  </div>
  <div class="button-row button-row-tight">
    <a href="<?= e(url('/admin/edit_exam.php?id=' . (int) $exam['exam_id'])) ?>"
       class="btn btn-outline btn-sm">&#9999; Edit Exam</a>
    <a href="<?= e(url('/admin/exams.php')) ?>" class="btn btn-outline btn-sm">&larr; All Exams</a>
  </div>
</div>

<div class="split-grid">
  <div class="card">
    <div class="card-header"><?= $isEdit ? '&#9999; Edit Question' : '&#10133; Add Question' ?></div>
    <div class="card-body">
      <form method="POST" novalidate>
        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
        <input type="hidden" name="question_id" value="<?= (int) ($editQuestion['question_id'] ?? 0) ?>">

        <div class="form-group">
          <label for="question_text">Question Text *</label>
          <textarea id="question_text" name="question_text" class="form-control" rows="3"
                    placeholder="Enter the question here..."><?= e($field('question_text')) ?></textarea>
        </div>

        <?php foreach ([1, 2, 3, 4] as $n): ?>
          <div class="form-group">
            <label for="option<?= $n ?>">Option <?= $letters[$n - 1] ?> *</label>
            <input type="text" id="option<?= $n ?>" name="option<?= $n ?>" class="form-control"
                   placeholder="Option <?= $letters[$n - 1] ?>" value="<?= e($field('option' . $n)) ?>">
          </div>
        <?php endforeach; ?>

        <div class="form-group">
          <label for="correct_answer">Correct Answer *</label>
          <select id="correct_answer" name="correct_answer" class="form-control">
            <option value="">-- Select correct option --</option>
            <?php foreach ([1, 2, 3, 4] as $n): ?>
              <option value="<?= $n ?>" <?= $correct === (string) $n ? 'selected' : '' ?>>
                Option <?= $letters[$n - 1] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-sm">
            <?= $isEdit ? '&#128190; Update Question' : '&#10133; Add Question' ?>
          </button>
          <?php if ($isEdit): ?>
            <a href="<?= e(url('/admin/questions.php?exam_id=' . (int) $exam['exam_id'])) ?>"
               class="btn btn-outline btn-sm">&times; Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div>
    <?php if ($questions === []): ?>
      <div class="empty-state card">
        <div class="icon">&#10067;</div>
        <p>No questions yet. Add your first question using the form.</p>
      </div>
    <?php else: ?>
      <div class="question-list">
        <?php foreach ($questions as $i => $question): ?>
          <div class="card">
            <div class="card-body card-body-sm">
              <div class="question-list-head">
                <p class="question-list-text">
                  <span class="badge badge-primary">Q<?= $i + 1 ?></span>
                  <?= e($question['question_text']) ?>
                </p>
                <div class="question-list-actions">
                  <a href="<?= e(url('/admin/questions.php?exam_id=' . (int) $exam['exam_id']
                          . '&edit_q=' . (int) $question['question_id'])) ?>"
                     class="btn btn-sm btn-warning" aria-label="Edit question <?= $i + 1 ?>">&#9999;</a>
                  <form method="POST" action="<?= e(url('/admin/questions.php')) ?>" class="inline-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
                    <input type="hidden" name="question_id" value="<?= (int) $question['question_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                            data-confirm="Delete this question?"
                            aria-label="Delete question <?= $i + 1 ?>">&#128465;</button>
                  </form>
                </div>
              </div>

              <div class="option-grid">
                <?php foreach ([1, 2, 3, 4] as $n):
                    $isCorrect = (int) $question['correct_answer'] === $n;
                ?>
                  <div class="option-chip <?= $isCorrect ? 'is-correct' : '' ?>">
                    <strong><?= $letters[$n - 1] ?>.</strong>
                    <?= e($question['option' . $n]) ?><?= $isCorrect ? ' &#10003;' : '' ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
