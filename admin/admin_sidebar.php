<?php
/**
 * admin_sidebar.php – Reusable admin sidebar navigation.
 * Include after header.php inside an .admin-wrapper div.
 */
$currentFile = basename($_SERVER['PHP_SELF']);
function isActive(string $file): string {
    global $currentFile;
    return $currentFile === $file ? 'active' : '';
}
?>
<aside class="sidebar">
  <p class="sidebar-section">Main</p>
  <ul class="sidebar-menu">
    <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= isActive('dashboard.php') ?>">
      <span class="menu-icon">🏠</span> Dashboard
    </a></li>
  </ul>

  <p class="sidebar-section">Exams</p>
  <ul class="sidebar-menu">
    <li><a href="<?= BASE_URL ?>/admin/exams.php" class="<?= isActive('exams.php') ?>">
      <span class="menu-icon">📋</span> Manage Exams
    </a></li>
    <li><a href="<?= BASE_URL ?>/admin/add_exam.php" class="<?= isActive('add_exam.php') ?>">
      <span class="menu-icon">➕</span> Add Exam
    </a></li>
  </ul>

  <p class="sidebar-section">Students</p>
  <ul class="sidebar-menu">
    <li><a href="<?= BASE_URL ?>/admin/students.php" class="<?= isActive('students.php') ?>">
      <span class="menu-icon">🎓</span> Manage Students
    </a></li>
    <li><a href="<?= BASE_URL ?>/admin/results.php" class="<?= isActive('results.php') ?>">
      <span class="menu-icon">📊</span> View Results
    </a></li>
  </ul>

  <p class="sidebar-section">Administrators</p>
  <ul class="sidebar-menu">
    <li><a href="<?= BASE_URL ?>/admin/admins.php" class="<?= isActive('admins.php') ?>">
      <span class="menu-icon">🛡️</span> Manage Admins
    </a></li>
    <li><a href="<?= BASE_URL ?>/admin/register.php" class="<?= isActive('register.php') ?>">
      <span class="menu-icon">➕</span> Add Admin
    </a></li>
  </ul>

  <p class="sidebar-section">Account</p>
  <ul class="sidebar-menu">
    <li><a href="<?= BASE_URL ?>/admin/logout.php">
      <span class="menu-icon">🚪</span> Logout
    </a></li>
  </ul>
</aside>
