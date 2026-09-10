<?php
/**
 * Student sign-in.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="auth">
  <div class="auth-form-col">
    <div class="auth-form">
      <div class="auth-head">
        <h1>Sign in</h1>
        <p>Welcome back. Enter your details to reach your exams.</p>
      </div>

      <div class="auth-messages">
        <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
      </div>

      <form method="POST" novalidate data-loading>
        <?= csrf_field() ?>

        <div class="field">
          <label class="field-label" for="email">Email address</label>
          <input type="email" id="email" name="email" class="field-input"
                 autocomplete="username" inputmode="email"
                 value="<?= e(old($old, 'email')) ?>" required autofocus>
        </div>

        <div class="field">
          <label class="field-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="field-input"
                 autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block"
                data-loading-label="Signing in&hellip;">Sign in</button>
      </form>

      <p class="auth-alt">
        Don&rsquo;t have an account?
        <a href="<?= e(url('/student/register.php')) ?>">Create one</a>
      </p>
    </div>
  </div>

  <?php \App\Core\View::partial('partials/auth_aside', [
      'quote'  => 'Sit your exams in a place built for concentrating, not for wrestling with the software.',
      'points' => [
          ['clock',        'A clock you can trust. ', 'Your remaining time is calculated by the server, so a reload never costs you the exam.'],
          ['checklist',    'Never lose your place. ', 'A navigator shows what you have answered and what you flagged to come back to.'],
          ['check-circle', 'Results straight away. ', 'Your score and a full breakdown appear the moment you hand in.'],
      ],
  ]); ?>
</div>
