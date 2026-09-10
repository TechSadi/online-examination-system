<?php
/**
 * Exam list with management actions.
 *
 * @var list<array<string,mixed>> $exams
 */
?>
<div class="page-heading page-heading-split">
  <div>
    <h2>Manage Exams</h2>
    <p class="text-muted">Create, edit, and delete examinations.</p>
  </div>
  <a href="<?= e(url('/admin/add_exam.php')) ?>" class="btn btn-primary">&#10133; Add New Exam</a>
</div>

<?php if ($exams === []): ?>
  <div class="empty-state">
    <div class="icon">&#128235;</div>
    <p>No exams yet. <a href="<?= e(url('/admin/add_exam.php')) ?>">Create your first exam.</a></p>
  </div>
<?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Title</th><th>Duration</th><th>Questions</th>
          <th>Attempts</th><th>Created</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($exams as $i => $exam):
            $attempts = (int) $exam['attempt_count'];
            $warning  = $attempts > 0
                ? sprintf(' This will also delete %d recorded result(s).', $attempts)
                : '';
        ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($exam['title']) ?></strong></td>
            <td><?= (int) $exam['duration'] ?> min</td>
            <td>
              <span class="badge <?= (int) $exam['question_count'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                <?= (int) $exam['question_count'] ?>
              </span>
            </td>
            <td><?= $attempts ?></td>
            <td class="text-muted"><?= e(format_date($exam['created_at'])) ?></td>
            <td class="cell-actions">
              <a href="<?= e(url('/admin/edit_exam.php?id=' . (int) $exam['exam_id'])) ?>"
                 class="btn btn-sm btn-warning">&#9999; Edit</a>
              <a href="<?= e(url('/admin/questions.php?exam_id=' . (int) $exam['exam_id'])) ?>"
                 class="btn btn-sm btn-primary">&#10067; Questions</a>
              <form method="POST" action="<?= e(url('/admin/exams.php')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="Delete &quot;<?= e($exam['title']) ?>&quot; and all its data?<?= e($warning) ?>">
                  &#128465; Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
