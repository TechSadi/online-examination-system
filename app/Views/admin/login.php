<?php
/**
 * Admin sign-in form.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <div class="logo">&#128736; <span>Admin</span> Panel</div>
      <p>Sign in to manage the examination system</p>
    </div>

    <div class="auth-body">
      <?php \App\Core\View::partial('partials/alerts', ['flashes' => $flashes, 'errors' => $errors]); ?>

      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="identifier">Username or Email</label>
          <input type="text" id="identifier" name="identifier" class="form-control"
                 placeholder="admin or admin@examhub.com"
                 value="<?= e(old($old, 'identifier')) ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">&#128272; Sign In</button>
      </form>
    </div>

    <div class="auth-footer">
      Administrator accounts are created from inside the admin panel.
    </div>
  </div>
</div>
