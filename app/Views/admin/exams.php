<?php
/**
 * Exam list with management actions.
 *
 * @var list<array<string,mixed>> $exams
 */
?>
<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Exams</h1>
    <p class="page-subtitle">Create, edit and remove examinations, and manage their questions.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('/admin/add_exam.php')) ?>">
      <?= icon('plus') ?> New exam
    </a>
  </div>
</div>

<?php if ($exams === []): ?>
  <div class="card">
    <div class="empty">
      <div class="empty-icon"><?= icon('exams') ?></div>
      <p class="empty-title">No exams yet</p>
      <p class="empty-text">
        An exam holds a title, a duration and a set of multiple-choice questions.
        Create one and you can start adding questions to it straight away.
      </p>
      <div class="empty-actions">
        <a class="btn btn-primary" href="<?= e(url('/admin/add_exam.php')) ?>">
          <?= icon('plus') ?> Create your first exam
        </a>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="table-card">
    <div class="table-caption">
      <span><?= pluralise(count($exams), 'exam') ?></span>
    </div>

    <div class="table-scroll">
      <table class="table table-stack">
        <thead>
          <tr>
            <th scope="col">Title</th>
            <th scope="col">Duration</th>
            <th scope="col">Questions</th>
            <th scope="col">Attempts</th>
            <th scope="col">Created</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exams as $exam):
              $examId    = (int) $exam['exam_id'];
              $questions = (int) $exam['question_count'];
              $attempts  = (int) $exam['attempt_count'];

              $consequence = sprintf(
                  'Deleting "%s" removes its %s%s. This cannot be undone.',
                  $exam['title'],
                  pluralise($questions, 'question'),
                  $attempts > 0
                      ? ' and the ' . pluralise($attempts, 'recorded result')
                      : ''
              );
          ?>
            <tr>
              <td data-label="Title" class="cell-primary cell-lead"><?= e($exam['title']) ?></td>
              <td data-label="Duration"><?= pluralise((int) $exam['duration'], 'minute') ?></td>
              <td data-label="Questions">
                <?php if ($questions === 0): ?>
                  <span class="badge badge-warning">None yet</span>
                <?php else: ?>
                  <?= $questions ?>
                <?php endif; ?>
              </td>
              <td data-label="Attempts"><?= $attempts ?></td>
              <td data-label="Created" class="cell-muted"><?= e(format_date($exam['created_at'])) ?></td>
              <td class="cell-actions" data-label="">
                <div class="btn-row">
                  <a class="btn btn-secondary btn-sm"
                     href="<?= e(url('/admin/questions.php?exam_id=' . $examId)) ?>">
                    <?= icon('checklist') ?> Questions
                  </a>
                  <a class="btn btn-secondary btn-sm btn-icon"
                     href="<?= e(url('/admin/edit_exam.php?id=' . $examId)) ?>"
                     aria-label="Edit <?= e($exam['title']) ?>">
                    <?= icon('edit') ?>
                  </a>
                  <form method="POST" action="<?= e(url('/admin/exams.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="exam_id" value="<?= $examId ?>">
                    <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"
                            aria-label="Delete <?= e($exam['title']) ?>"
                            data-confirm-title="Delete this exam?"
                            data-confirm="<?= e($consequence) ?>"
                            data-confirm-label="Delete exam">
                      <?= icon('trash') ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
