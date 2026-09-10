<?php
/**
 * Student sign-in form.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <div class="logo">&#128221; <span>Exam</span>Hub</div>
      <p>Sign in to your student account</p>
    </div>

    <div class="auth-body">
      <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="jane@example.com" value="<?= e(old($old, 'email')) ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">Sign In &rarr;</button>
      </form>
    </div>

    <div class="auth-footer">
      Don&rsquo;t have an account? <a href="<?= e(url('/student/register.php')) ?>">Register here</a>
    </div>
  </div>
</div>
