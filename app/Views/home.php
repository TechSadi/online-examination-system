<?php
/**
 * Public landing page.
 */
?>
<section class="hero">
  <div class="container">
    <div class="hero-inner">
      <p class="hero-eyebrow"><?= icon('shield') ?> Secure online assessment</p>

      <h1>Examinations that run themselves.</h1>

      <p class="hero-lead">
        ExamHub gives students a calm, focused place to sit an exam and gives
        educators the tools to set one up in minutes &mdash; timed, graded
        automatically, and marked the moment it is handed in.
      </p>

      <div class="hero-actions">
        <a href="<?= e(url('/student/register.php')) ?>" class="btn btn-primary btn-lg">
          Create a student account <?= icon('arrow-right') ?>
        </a>
        <a href="<?= e(url('/student/login.php')) ?>" class="btn btn-on-dark btn-lg">
          Sign in
        </a>
      </div>

      <p class="hero-note">Free for students. No card, no setup.</p>
    </div>
  </div>
</section>

<section class="section section-surface">
  <div class="container">
    <div class="section-head section-head-center">
      <h2 class="section-title">Built for the way exams actually run</h2>
      <p class="section-lead">
        Everything below is enforced on the server, so the experience is the
        same whatever a candidate does to the page in front of them.
      </p>
    </div>

    <div class="feature-grid">
      <?php foreach ([
          ['clock', 'A clock that cannot be cheated',
           'The deadline is fixed when an attempt begins and checked again when the answers arrive. Reloading resumes the same countdown rather than restarting it.'],
          ['zap', 'Marked the moment it is submitted',
           'Answers are graded against the answer key server-side and the result appears immediately, with a breakdown of what went right.'],
          ['checklist', 'A focused exam interface',
           'One question at a time, a navigator showing what is answered, flags for anything to revisit, and a timer that stays visible on any screen.'],
          ['dashboard', 'A real administration console',
           'Create exams, write questions, and follow every attempt across every student from one place.'],
      ] as [$iconName, $title, $body]): ?>
        <article class="feature">
          <div class="feature-icon"><?= icon($iconName) ?></div>
          <h3><?= e($title) ?></h3>
          <p><?= e($body) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-tight">
  <div class="container">
    <div class="cta">
      <div class="cta-text">
        <h2>Ready to sit your first exam?</h2>
        <p>Create an account and you will be looking at the exam list a minute from now.</p>
      </div>
      <a href="<?= e(url('/student/register.php')) ?>" class="btn btn-primary btn-lg">
        Get started <?= icon('arrow-right') ?>
      </a>
    </div>
  </div>
</section>
