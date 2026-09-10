<?php
/**
 * Admin navigation.
 *
 * The same markup is the persistent sidebar on a wide screen and the
 * off-canvas drawer on a narrow one. Before this phase the sidebar was
 * `display: none` below 900px with nothing offered in its place, so every
 * administration screen was unreachable from a phone; the drawer is the fix,
 * not a decoration.
 *
 * The active link is decided here, from the current request path.
 */

use App\Core\Url;

$current = basename(Url::current());

$sections = [
    'Overview' => [
        ['dashboard.php', 'dashboard', 'Dashboard'],
    ],
    'Assessment' => [
        ['exams.php',     'exams',    'Exams'],
        ['add_exam.php',  'plus',     'New exam'],
    ],
    'People' => [
        ['students.php',  'students', 'Students'],
        ['results.php',   'results',  'Results'],
    ],
    'Administration' => [
        ['admins.php',    'admins',   'Administrators'],
        ['register.php',  'plus',     'New administrator'],
    ],
];
?>
<aside class="app-sidebar" id="admin-nav" aria-label="Admin sections">
  <div class="drawer-head">
    <span class="app-brand">
      <span class="app-brand-mark"><?= icon('logo') ?></span>
      <span class="app-brand-text">ExamHub</span>
    </span>
    <button type="button" class="btn btn-ghost btn-icon btn-sm" data-drawer-close aria-label="Close navigation menu">
      <?= icon('x') ?>
    </button>
  </div>

  <nav>
    <?php foreach ($sections as $heading => $links): ?>
      <div class="sidebar-group">
        <p class="sidebar-heading"><?= e($heading) ?></p>
        <?php foreach ($links as [$file, $iconName, $label]): ?>
          <a class="sidebar-link" href="<?= e(url('/admin/' . $file)) ?>"
             <?= $current === $file ? 'aria-current="page"' : '' ?>>
            <?= icon($iconName) ?> <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>
</aside>
