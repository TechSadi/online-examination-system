<?php
/**
 * Student accounts with an activity summary.
 *
 * @var list<array<string,mixed>> $students  one page of rows
 * @var string                    $search
 * @var \App\Core\Sorter          $sort
 * @var \App\Core\Paginator       $paginator
 */
$path       = '/admin/students.php';
$isSearch   = $search !== '';
$sortHeader = static function (string $key, string $label) use ($sort, $path): void {
    \App\Core\View::partial('partials/sort_header', [
        'sort' => $sort, 'key' => $key, 'label' => $label, 'path' => $path,
    ]);
};
?>
<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Students</h1>
    <p class="page-subtitle">Registered accounts and how each one is performing.</p>
  </div>
</div>

<div class="table-card">
  <form class="table-toolbar" method="GET" action="<?= e(url($path)) ?>">
    <?php /* The sort survives a search, and a new search returns to page one. */ ?>
    <input type="hidden" name="sort" value="<?= e($sort->key) ?>">
    <input type="hidden" name="dir" value="<?= e($sort->direction) ?>">

    <div class="field">
      <label class="field-label" for="q">Search</label>
      <span class="field-affix">
        <?= icon('search') ?>
        <input type="search" id="q" name="q" class="field-input"
               placeholder="Name or email" value="<?= e($search) ?>">
      </span>
    </div>

    <div class="btn-row">
      <button type="submit" class="btn btn-secondary">Search</button>
      <?php if ($isSearch): ?>
        <a class="btn btn-ghost" href="<?= e(url($path)) ?>"><?= icon('x') ?> Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($students === []): ?>
    <div class="empty">
      <div class="empty-icon"><?= icon($isSearch ? 'search' : 'students') ?></div>
      <?php if ($isSearch): ?>
        <p class="empty-title">No students match &ldquo;<?= e($search) ?>&rdquo;</p>
        <p class="empty-text">Check the spelling, or search for part of an email address instead.</p>
        <div class="empty-actions">
          <a class="btn btn-secondary" href="<?= e(url($path)) ?>">Show all students</a>
        </div>
      <?php else: ?>
        <p class="empty-title">No students have registered yet</p>
        <p class="empty-text">
          Students create their own accounts from the sign-up page. Once they do,
          they will be listed here with their results.
        </p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="table table-stack">
        <thead>
          <tr>
            <?php $sortHeader('name', 'Name'); ?>
            <?php $sortHeader('email', 'Email'); ?>
            <?php $sortHeader('attempts', 'Exams taken'); ?>
            <?php $sortHeader('avg_score', 'Average'); ?>
            <?php $sortHeader('created_at', 'Registered'); ?>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student):
              $studentId = (int) $student['student_id'];
              $attempts  = (int) $student['attempts'];
              $average   = $student['avg_score'];
          ?>
            <tr>
              <td data-label="Name" class="cell-primary cell-lead"><?= e($student['name']) ?></td>
              <td data-label="Email" class="cell-muted"><?= e($student['email']) ?></td>
              <td data-label="Exams taken"><?= $attempts ?></td>
              <td data-label="Average">
                <?php if ($average === null): ?>
                  <span class="text-muted">&mdash;</span>
                <?php else:
                    $pct    = (int) round((float) $average);
                    $passed = is_pass($pct);
                ?>
                  <div class="meter">
                    <div class="progress <?= $passed ? 'progress-success' : 'progress-danger' ?>">
                      <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="meter-value"><?= $pct ?>%</span>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Registered" class="cell-muted"><?= e(format_date($student['created_at'])) ?></td>
              <td class="cell-actions" data-label="">
                <div class="btn-row">
                  <a class="btn btn-secondary btn-sm"
                     href="<?= e(url('/admin/results.php?student_id=' . $studentId)) ?>">
                    <?= icon('results') ?> Results
                  </a>
                  <form method="POST" action="<?= e(url($path)) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                    <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"
                            aria-label="Delete <?= e($student['name']) ?>"
                            data-confirm-title="Delete this student account?"
                            data-confirm="<?= e(sprintf(
                                '%s will be removed along with %s. This cannot be undone.',
                                $student['name'],
                                $attempts > 0 ? pluralise($attempts, 'recorded result') : 'their account data'
                            )) ?>"
                            data-confirm-label="Delete student">
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

    <?php \App\Core\View::partial('partials/pagination', [
        'paginator' => $paginator, 'path' => $path, 'noun' => 'student',
    ]); ?>
  <?php endif; ?>
</div>
