<?php
/**
 * Application top bar.
 *
 * One bar serves all three audiences. What changes between them is the
 * navigation it is handed, not the chrome around it:
 *
 *   public   marketing links and the two sign-in entry points
 *   auth     the mark alone - a sign-in screen should not offer a second
 *            "Sign in" button next to the form it already is
 *   exam     brand and account only, so nothing invites a candidate away
 *            from a running clock
 *   student  the three destinations that make up the student app
 *   admin    no inline links - the sidebar owns navigation - plus the
 *            control that opens that sidebar as a drawer on small screens
 *
 * @var string $role 'public' | 'auth' | 'student' | 'exam' | 'admin'
 */

use App\Core\Url;
use App\Middleware\Auth;

$current    = basename(Url::current());
$isStudent  = in_array($role, ['student', 'exam'], true) && Auth::isStudent();
$isAdmin    = $role === 'admin' && Auth::isAdmin();
$isAuth     = $role === 'auth';
$showNav    = $role === 'student';

/** Mark the link for the page being viewed. */
$currentPage = static fn (string $file): string => $current === $file ? ' aria-current="page"' : '';

$studentLinks = [
    ['dashboard.php', 'dashboard', 'Dashboard'],
    ['exams.php',     'exams',     'Exams'],
    ['results.php',   'results',   'My Results'],
];
?>
<header class="app-topbar">
  <?php if ($isAdmin): ?>
    <button type="button" class="nav-toggle" data-drawer-toggle
            aria-controls="admin-nav" aria-expanded="false">
      <?= icon('menu') ?>
      <span class="nav-toggle-label">Menu</span>
    </button>
  <?php endif; ?>

  <a class="app-brand" href="<?= e(url($isAdmin ? '/admin/dashboard.php' : '/')) ?>">
    <span class="app-brand-mark"><?= icon('logo') ?></span>
    <span class="app-brand-text">ExamHub</span>
    <?php if ($isAdmin): ?><span class="app-brand-tag">Admin</span><?php endif; ?>
  </a>

  <?php if ($isStudent && $showNav): ?>
    <nav class="app-topbar-nav" aria-label="Main">
      <?php foreach ($studentLinks as [$file, $iconName, $label]): ?>
        <a class="nav-link" href="<?= e(url('/student/' . $file)) ?>"<?= $currentPage($file) ?>>
          <?= icon($iconName) ?>
          <span class="nav-link-label"><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <div class="app-topbar-end">
    <?php \App\Core\View::partial('partials/theme_picker'); ?>

    <?php if ($isStudent): ?>
      <?php \App\Core\View::partial('partials/account_menu', [
          'name'       => Auth::studentName(),
          'roleLabel'  => 'Student',
          'logoutPath' => '/student/logout.php',
      ]); ?>
    <?php elseif ($isAdmin): ?>
      <?php \App\Core\View::partial('partials/account_menu', [
          'name'       => Auth::adminName(),
          'roleLabel'  => 'Administrator',
          'logoutPath' => '/admin/logout.php',
      ]); ?>
    <?php elseif (!$isAuth): ?>
      <?php /* The label shortens rather than the link disappearing - see the
               note in layout.css. This is the front door for administrators
               and it has to survive every width. */ ?>
      <nav class="app-topbar-nav app-topbar-nav-public" aria-label="Main">
        <a class="nav-link nav-link-admin" href="<?= e(url('/admin/login.php')) ?>">
          <span class="nav-link-admin-full">Admin sign in</span>
          <span class="nav-link-admin-short">Admin</span>
        </a>
      </nav>
      <a class="btn btn-secondary btn-sm" href="<?= e(url('/student/login.php')) ?>">Sign in</a>
      <a class="btn btn-primary btn-sm topbar-register"
         href="<?= e(url('/student/register.php')) ?>">Create account</a>
    <?php endif; ?>
  </div>
</header>
