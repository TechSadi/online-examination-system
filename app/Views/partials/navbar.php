<?php
/**
 * Top navigation. The links shown depend on who is signed in.
 *
 * @var string $role 'public' | 'student' | 'admin'
 */

use App\Middleware\Auth;
?>
<nav class="navbar">
  <a class="navbar-brand" href="<?= e(url('/')) ?>">
    &#128221; <span>Exam</span>Hub
  </a>
  <div class="navbar-links">
    <?php if ($role === 'student' && Auth::isStudent()): ?>
      <a href="<?= e(url('/student/dashboard.php')) ?>">&#127968; Dashboard</a>
      <a href="<?= e(url('/student/exams.php')) ?>">&#128203; Exams</a>
      <a href="<?= e(url('/student/results.php')) ?>">&#128202; My Results</a>
      <a href="<?= e(url('/student/logout.php')) ?>" class="btn-logout">&#128682; Logout</a>
    <?php elseif ($role === 'admin' && Auth::isAdmin()): ?>
      <a href="<?= e(url('/admin/dashboard.php')) ?>">&#127968; Dashboard</a>
      <a href="<?= e(url('/admin/logout.php')) ?>" class="btn-logout">&#128682; Logout</a>
    <?php else: ?>
      <a href="<?= e(url('/')) ?>">Home</a>
      <a href="<?= e(url('/student/login.php')) ?>">Student Login</a>
      <a href="<?= e(url('/student/register.php')) ?>">Register</a>
      <a href="<?= e(url('/admin/login.php')) ?>">Admin</a>
    <?php endif; ?>
  </div>
</nav>
