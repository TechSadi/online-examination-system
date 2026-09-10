<?php
/**
 * Public landing page.
 */
?>
<section class="hero">
  <div class="hero-content">
    <h1>Your <span>Smart</span><br>Examination Platform</h1>
    <p>Take exams online, track your progress, and get instant results.
       A modern, secure platform designed for students and educators.</p>
    <div class="hero-btns">
      <a href="<?= e(url('/student/register.php')) ?>" class="btn btn-accent btn-lg">&#127891; Register as Student</a>
      <a href="<?= e(url('/student/login.php')) ?>" class="btn btn-outline btn-lg btn-on-dark">Login</a>
    </div>
  </div>
</section>

<section class="section section-plain">
  <div class="container">
    <h2 class="section-title">Why ExamHub?</h2>
    <p class="text-muted text-center mb-2">Everything you need for a smooth online examination experience.</p>

    <div class="features-grid">
      <?php foreach ([
          ['&#9201;',  'Auto-Timed Exams', 'Built-in countdown timer with automatic submission when time expires. No missed deadlines.'],
          ['&#128202;', 'Instant Results',  'See your score the moment you submit. A clear breakdown of how you performed.'],
          ['&#128274;', 'Secure Platform',  'Hashed passwords, prepared statements, and session-based authentication protect your data.'],
          ['&#128736;', 'Admin Controls',   'Admins can create exams, manage questions, and track every student performance.'],
      ] as [$icon, $title, $body]): ?>
        <div class="feature-card">
          <div class="icon"><?= $icon ?></div>
          <h3><?= e($title) ?></h3>
          <p><?= e($body) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-muted">
  <div class="container text-center">
    <h2 class="section-title">Ready to get started?</h2>
    <p class="text-muted mb-2">Create your free student account and take your first exam in minutes.</p>
    <a href="<?= e(url('/student/register.php')) ?>" class="btn btn-primary btn-lg">Create Free Account &rarr;</a>
  </div>
</section>
