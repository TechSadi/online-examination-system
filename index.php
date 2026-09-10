<?php
/**
 * index.php – Public home page
 */
define('ROOT', __DIR__);
$pageTitle = 'Welcome';
$role      = 'public';
require_once ROOT . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-content">
    <h1>Your <span>Smart</span><br>Examination Platform</h1>
    <p>Take exams online, track your progress, and get instant results. A modern, secure platform designed for students and educators.</p>
    <div class="hero-btns">
      <a href="<?= BASE_URL ?>/student/register.php" class="btn btn-accent btn-lg">🎓 Register as Student</a>
      <a href="<?= BASE_URL ?>/student/login.php"    class="btn btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,.5)">Login</a>
    </div>
  </div>
</section>

<!-- Features -->
<section class="section" style="background:#fff;">
  <div class="container">
    <h2 style="font-family:var(--font-display);font-size:2rem;text-align:center;margin-bottom:8px;">Why ExamHub?</h2>
    <p class="text-muted text-center mb-2">Everything you need for a smooth online examination experience.</p>
    <div class="features-grid">
      <div class="feature-card">
        <div class="icon">⏱️</div>
        <h3>Auto-Timed Exams</h3>
        <p>Built-in countdown timer with automatic submission when time expires. No missed deadlines.</p>
      </div>
      <div class="feature-card">
        <div class="icon">📊</div>
        <h3>Instant Results</h3>
        <p>See your score the moment you submit. Detailed breakdown of right and wrong answers.</p>
      </div>
      <div class="feature-card">
        <div class="icon">🔒</div>
        <h3>Secure Platform</h3>
        <p>Hashed passwords, prepared statements, and session-based authentication protect your data.</p>
      </div>
      <div class="feature-card">
        <div class="icon">🛠️</div>
        <h3>Admin Controls</h3>
        <p>Admins can create exams, manage questions, and track every student's performance.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section" style="background:var(--clr-bg);">
  <div class="container text-center">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:12px;">Ready to get started?</h2>
    <p class="text-muted mb-2">Create your free student account and take your first exam in minutes.</p>
    <a href="<?= BASE_URL ?>/student/register.php" class="btn btn-primary btn-lg">Create Free Account →</a>
  </div>
</section>

<?php require_once ROOT . '/includes/footer.php'; ?>
