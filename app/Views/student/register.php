<?php
/**
 * Student registration.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
$minLength = (int) config('security.password_min_length', 8);
?>
<div class="auth">
  <div class="auth-form-col">
    <div class="auth-form">
      <div class="auth-head">
        <h1>Create your account</h1>
        <p>It takes a minute, and your first exam is waiting on the other side.</p>
      </div>

      <div class="auth-messages">
        <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>
      </div>

      <form method="POST" novalidate data-loading>
        <?= csrf_field() ?>

        <div class="field">
          <label class="field-label" for="name">Full name</label>
          <input type="text" id="name" name="name" class="field-input"
                 autocomplete="name" value="<?= e(old($old, 'name')) ?>" required autofocus>
        </div>

        <div class="field">
          <label class="field-label" for="email">Email address</label>
          <input type="email" id="email" name="email" class="field-input"
                 autocomplete="email" inputmode="email"
                 value="<?= e(old($old, 'email')) ?>" required>
          <small class="field-hint">You will sign in with this address.</small>
        </div>

        <div class="field-pair">
          <div class="field">
            <label class="field-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="field-input"
                   autocomplete="new-password" minlength="<?= $minLength ?>"
                   aria-describedby="password-hint" required>
            <small class="field-hint" id="password-hint">At least <?= $minLength ?> characters.</small>
          </div>

          <div class="field">
            <label class="field-label" for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm" class="field-input"
                   autocomplete="new-password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block"
                data-loading-label="Creating account&hellip;">Create account</button>
      </form>

      <p class="auth-alt">
        Already registered?
        <a href="<?= e(url('/student/login.php')) ?>">Sign in</a>
        <br>
        Administrator?
        <a href="<?= e(url('/admin/login.php')) ?>">Sign in to the console</a>.
      </p>
    </div>
  </div>

  <?php \App\Core\View::partial('partials/auth_aside', [
      'quote'  => 'One account, every exam your institution sets, and a record of how you did in each.',
      'points' => [
          ['book',    'Everything in one list. ', 'Available exams, how long each takes, and how many questions it holds.'],
          ['trend',   'Watch your progress. ', 'Every result you have ever recorded, with your running average.'],
          ['shield',  'Your data is looked after. ', 'Passwords are hashed, sessions are hardened, and results are yours alone to see.'],
      ],
  ]); ?>
</div>
