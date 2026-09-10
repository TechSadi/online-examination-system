<?php
/**
 * All exam results, filterable by student and exam and searchable by name.
 *
 * @var list<array<string,mixed>> $results   one page of rows
 * @var list<array<string,mixed>> $students  filter options
 * @var list<array<string,mixed>> $exams     filter options
 * @var int                       $studentId
 * @var int                       $examId
 * @var string                    $search
 * @var \App\Core\Sorter          $sort
 * @var \App\Core\Paginator       $paginator
 */
$path     = '/admin/results.php';
$filtered = $studentId > 0 || $examId > 0 || $search !== '';

$sortHeader = static function (string $key, string $label) use ($sort, $path): void {
    \App\Core\View::partial('partials/sort_header', [
        'sort' => $sort, 'key' => $key, 'label' => $label, 'path' => $path,
    ]);
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Results</h1>
    <p class="page-subtitle">Every completed attempt across every exam.</p>
  </div>
</div>

<div class="table-card">
  <form class="table-toolbar" method="GET" action="<?= e(url($path)) ?>">
    <input type="hidden" name="sort" value="<?= e($sort->key) ?>">
    <input type="hidden" name="dir" value="<?= e($sort->direction) ?>">

    <div class="field">
      <label class="field-label" for="q">Search</label>
      <span class="field-affix">
        <?= icon('search') ?>
        <input type="search" id="q" name="q" class="field-input"
               placeholder="Student or exam" value="<?= e($search) ?>">
      </span>
    </div>

    <div class="field">
      <label class="field-label" for="student_id">Student</label>
      <select id="student_id" name="student_id" class="field-input">
        <option value="">All students</option>
        <?php foreach ($students as $student): ?>
          <option value="<?= (int) $student['student_id'] ?>"
            <?= $studentId === (int) $student['student_id'] ? 'selected' : '' ?>>
            <?= e($student['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label class="field-label" for="exam_id">Exam</label>
      <select id="exam_id" name="exam_id" class="field-input">
        <option value="">All exams</option>
        <?php foreach ($exams as $exam): ?>
          <option value="<?= (int) $exam['exam_id'] ?>"
            <?= $examId === (int) $exam['exam_id'] ? 'selected' : '' ?>>
            <?= e($exam['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="btn-row">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?> Apply</button>
      <?php if ($filtered): ?>
        <a class="btn btn-ghost" href="<?= e(url($path)) ?>"><?= icon('x') ?> Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($results === []): ?>
    <div class="empty">
      <div class="empty-icon"><?= icon($filtered ? 'filter' : 'results') ?></div>
      <?php if ($filtered): ?>
        <p class="empty-title">No results match these filters</p>
        <p class="empty-text">Try widening the search, or clear the filters to see everything.</p>
        <div class="empty-actions">
          <a class="btn btn-secondary" href="<?= e(url($path)) ?>">Clear filters</a>
        </div>
      <?php else: ?>
        <p class="empty-title">No exams have been completed yet</p>
        <p class="empty-text">
          Results appear here the moment a student submits an exam, with their
          score and whether they passed.
        </p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="table table-stack">
        <thead>
          <tr>
            <?php $sortHeader('student', 'Student'); ?>
            <?php $sortHeader('exam', 'Exam'); ?>
            <th scope="col">Score</th>
            <?php $sortHeader('percentage', 'Percentage'); ?>
            <th scope="col">Result</th>
            <?php $sortHeader('date_taken', 'Date'); ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $result):
              $pct    = percentage((int) $result['score'], (int) $result['total']);
              $passed = is_pass($pct);
          ?>
            <tr>
              <td data-label="Student" class="cell-lead">
                <span class="cell-primary"><?= e($result['student_name']) ?></span>
                <span class="cell-sub"><?= e($result['email']) ?></span>
              </td>
              <td data-label="Exam"><?= e($result['exam_title']) ?></td>
              <td data-label="Score"><?= (int) $result['score'] ?> of <?= (int) $result['total'] ?></td>
              <td data-label="Percentage">
                <div class="meter">
                  <div class="progress <?= $passed ? 'progress-success' : 'progress-danger' ?>">
                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span class="meter-value"><?= $pct ?>%</span>
                </div>
              </td>
              <td data-label="Result">
                <span class="status <?= $passed ? 'status-success' : 'status-danger' ?>">
                  <?= $passed ? 'Passed' : 'Not passed' ?>
                </span>
              </td>
              <td data-label="Date" class="cell-muted"><?= e(format_date($result['date_taken'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php \App\Core\View::partial('partials/pagination', [
        'paginator' => $paginator, 'path' => $path, 'noun' => 'result',
    ]); ?>
  <?php endif; ?>
</div>
