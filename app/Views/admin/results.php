<?php
/**
 * All exam results, filterable by student and exam.
 *
 * @var list<array<string,mixed>> $results
 * @var list<array<string,mixed>> $students
 * @var list<array<string,mixed>> $exams
 * @var int                       $studentId
 * @var int                       $examId
 */
$filtered = $studentId > 0 || $examId > 0;
?>
<div class="page-heading">
  <h2>Exam Results</h2>
  <p class="text-muted">Total records: <strong><?= count($results) ?></strong></p>
</div>

<div class="card mb-2">
  <div class="card-body card-body-sm">
    <form method="GET" class="filter-form">
      <div class="form-group filter-field">
        <label for="student_id">Filter by Student</label>
        <select id="student_id" name="student_id" class="form-control">
          <option value="">All Students</option>
          <?php foreach ($students as $student): ?>
            <option value="<?= (int) $student['student_id'] ?>"
              <?= $studentId === (int) $student['student_id'] ? 'selected' : '' ?>>
              <?= e($student['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group filter-field">
        <label for="exam_id">Filter by Exam</label>
        <select id="exam_id" name="exam_id" class="form-control">
          <option value="">All Exams</option>
          <?php foreach ($exams as $exam): ?>
            <option value="<?= (int) $exam['exam_id'] ?>"
              <?= $examId === (int) $exam['exam_id'] ? 'selected' : '' ?>>
              <?= e($exam['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="button-row button-row-tight">
        <button type="submit" class="btn btn-primary btn-sm">&#128269; Filter</button>
        <a href="<?= e(url('/admin/results.php')) ?>" class="btn btn-outline btn-sm">&times; Clear</a>
      </div>
    </form>
  </div>
</div>

<?php if ($results === []): ?>
  <div class="empty-state">
    <div class="icon">&#128202;</div>
    <p>No results found<?= $filtered ? ' for this filter' : '' ?>.</p>
  </div>
<?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Student</th><th>Email</th><th>Exam</th>
          <th>Score</th><th>%</th><th>Status</th><th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $i => $result):
            $pct    = percentage((int) $result['score'], (int) $result['total']);
            $passed = is_pass($pct);
        ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($result['student_name']) ?></strong></td>
            <td class="text-muted"><?= e($result['email']) ?></td>
            <td><?= e($result['exam_title']) ?></td>
            <td><?= (int) $result['score'] ?>/<?= (int) $result['total'] ?></td>
            <td>
              <div class="cell-progress">
                <div class="progress-bar progress-bar-inline progress-bar-narrow">
                  <div class="progress-bar-fill <?= $passed ? 'fill-success' : 'fill-danger' ?>"
                       style="width:<?= $pct ?>%"></div>
                </div>
                <span class="cell-progress-value"><?= $pct ?>%</span>
              </div>
            </td>
            <td><span class="badge <?= e(score_badge($pct)) ?>"><?= $passed ? 'PASS' : 'FAIL' ?></span></td>
            <td class="text-muted"><?= e(format_date($result['date_taken'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
