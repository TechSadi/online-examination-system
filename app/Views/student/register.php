<?php
/**
 * Student registration form.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <div class="logo">&#128221; <span>Exam</span>Hub</div>
      <p>Create your student account</p>
    </div>

    <div class="auth-body">
      <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" class="form-control"
                 placeholder="Jane Smith" value="<?= e(old($old, 'name')) ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="jane@example.com" value="<?= e(old($old, 'email')) ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Min 6 chars" required>
          </div>
          <div class="form-group">
            <label for="confirm">Confirm Password</label>
            <input type="password" id="confirm" name="confirm" class="form-control"
                   placeholder="Repeat password" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">Create Account &rarr;</button>
      </form>
    </div>

    <div class="auth-footer">
      Already have an account? <a href="<?= e(url('/student/login.php')) ?>">Sign in here</a>
    </div>
  </div>
</div>
