<?php
/**
 * header.php – Shared HTML head + navbar
 * Usage: require_once ROOT . '/includes/header.php';
 *
 * Expected variables (set before including):
 *   $pageTitle  string  – <title> content
 *   $role       string  – 'student' | 'admin' | 'public'
 */

if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';

$pageTitle = $pageTitle ?? 'Online Exam System';
$role      = $role      ?? 'public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> – ExamHub</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📝</text></svg>">
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────── -->
<nav class="navbar">
  <a class="navbar-brand" href="<?= BASE_URL ?>/">
    📝 <span>Exam</span>Hub
  </a>
  <div class="navbar-links">
    <?php if ($role === 'student' && isStudentLoggedIn()): ?>
      <a href="<?= BASE_URL ?>/student/dashboard.php">🏠 Dashboard</a>
      <a href="<?= BASE_URL ?>/student/exams.php">📋 Exams</a>
      <a href="<?= BASE_URL ?>/student/results.php">📊 My Results</a>
      <a href="<?= BASE_URL ?>/student/logout.php" class="btn-logout">🚪 Logout</a>
    <?php elseif ($role === 'admin' && isAdminLoggedIn()): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php">🏠 Dashboard</a>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="btn-logout">🚪 Logout</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/">Home</a>
      <a href="<?= BASE_URL ?>/student/login.php">Student Login</a>
      <a href="<?= BASE_URL ?>/student/register.php">Register</a>
      <a href="<?= BASE_URL ?>/admin/login.php">Admin</a>
    <?php endif; ?>
  </div>
</nav>
