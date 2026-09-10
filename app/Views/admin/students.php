<?php
/**
 * Student accounts with activity summary.
 *
 * @var list<array<string,mixed>> $students
 */
?>
<div class="page-heading">
  <h2>Manage Students</h2>
  <p class="text-muted"><?= count($students) ?> registered student(s)</p>
</div>

<?php if ($students === []): ?>
  <div class="empty-state">
    <div class="icon">&#128100;</div>
    <p>No students registered yet.</p>
  </div>
<?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Email</th><th>Exams Taken</th>
          <th>Avg Score</th><th>Registered</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $i => $student):
            $attempts = (int) $student['attempts'];
            $average  = $student['avg_score'];
        ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($student['name']) ?></strong></td>
            <td><?= e($student['email']) ?></td>
            <td>
              <span class="badge <?= $attempts > 0 ? 'badge-success' : 'badge-warning' ?>"><?= $attempts ?></span>
            </td>
            <td>
              <?php if ($average !== null): ?>
                <span class="badge <?= e(score_badge((int) round((float) $average))) ?>">
                  <?= e((string) $average) ?>%
                </span>
              <?php else: ?>
                <span class="text-muted">&mdash;</span>
              <?php endif; ?>
            </td>
            <td class="text-muted"><?= e(format_date($student['created_at'])) ?></td>
            <td class="cell-actions">
              <a href="<?= e(url('/admin/results.php?student_id=' . (int) $student['student_id'])) ?>"
                 class="btn btn-sm btn-primary">&#128202; Results</a>
              <form method="POST" action="<?= e(url('/admin/students.php')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="student_id" value="<?= (int) $student['student_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="Delete student &quot;<?= e($student['name']) ?>&quot; and all their data?">
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
