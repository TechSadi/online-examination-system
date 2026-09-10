<?php
/**
 * Administrator sign-in.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="auth">
  <div class="auth-form-col">
    <div class="auth-form">
      <div class="auth-head">
        <h1>Administrator sign-in</h1>
        <p>Manage exams, questions, students and results.</p>
      </div>

      <div class="auth-messages">
        <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
      </div>

      <form method="POST" novalidate data-loading>
        <?= csrf_field() ?>

        <div class="field">
          <label class="field-label" for="identifier">Username or email</label>
          <input type="text" id="identifier" name="identifier" class="field-input"
                 autocomplete="username" value="<?= e(old($old, 'identifier')) ?>" required autofocus>
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
        Administrator accounts are created from inside the console.
        Looking for the student area?
        <a href="<?= e(url('/student/login.php')) ?>">Sign in here</a>.
      </p>
    </div>
  </div>

  <?php \App\Core\View::partial('partials/auth_aside', [
      'quote'  => 'The administration console: every exam, every question and every attempt in one place.',
      'points' => [
          ['exams',   'Author exams quickly. ', 'Set a duration, write the questions, and it is ready to sit.'],
          ['results', 'Follow every attempt. ', 'Filter results by student or exam and see where a cohort is struggling.'],
          ['lock',    'Access is controlled. ', 'Only a signed-in administrator can create another administrator.'],
      ],
  ]); ?>
</div>
