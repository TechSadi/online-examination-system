<?php
/**
 * Admin sidebar navigation.
 *
 * The active link is decided here, from the current request path. The old
 * version used a global $currentFile plus a competing substring guess in
 * app.js; both are gone.
 */

use App\Core\Url;

$current = basename(Url::current());

$isActive = static fn (string $file): string => $current === $file ? 'active' : '';

$sections = [
    'Main' => [
        ['dashboard.php', '&#127968;', 'Dashboard'],
    ],
    'Exams' => [
        ['exams.php',    '&#128203;', 'Manage Exams'],
        ['add_exam.php', '&#10133;',  'Add Exam'],
    ],
    'Students' => [
        ['students.php', '&#127891;', 'Manage Students'],
        ['results.php',  '&#128202;', 'View Results'],
    ],
    'Administrators' => [
        ['admins.php',   '&#128737;', 'Manage Admins'],
        ['register.php', '&#10133;',  'Add Admin'],
    ],
];
?>
<aside class="sidebar">
  <?php foreach ($sections as $heading => $links): ?>
    <p class="sidebar-section"><?= e($heading) ?></p>
    <ul class="sidebar-menu">
      <?php foreach ($links as [$file, $icon, $label]): ?>
        <li>
          <a href="<?= e(url('/admin/' . $file)) ?>" class="<?= $isActive($file) ?>"
             <?= $isActive($file) === 'active' ? 'aria-current="page"' : '' ?>>
            <span class="menu-icon"><?= $icon ?></span> <?= e($label) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>

  <p class="sidebar-section">Account</p>
  <ul class="sidebar-menu">
    <li>
      <a href="<?= e(url('/admin/logout.php')) ?>">
        <span class="menu-icon">&#128682;</span> Logout
      </a>
    </li>
  </ul>
</aside>
