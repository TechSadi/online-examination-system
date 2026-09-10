<?php
/**
 * Create an administrator account. Reachable only by a signed-in admin.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
$minLength = (int) config('security.password_min_length', 8);
?>
<nav aria-label="Breadcrumb">
  <ol class="breadcrumb">
    <li><a href="<?= e(url('/admin/admins.php')) ?>">Administrators</a></li>
    <li><?= icon('chevron-right') ?></li>
    <li aria-current="page">New administrator</li>
  </ol>
</nav>

<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">New administrator</h1>
    <p class="page-subtitle">
      The account you create has the same access to this console as your own.
    </p>
  </div>
</div>

<div class="card card-form">
  <div class="card-body">
    <form method="POST" novalidate data-loading>
      <?= csrf_field() ?>

      <div class="field">
        <label class="field-label" for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" class="field-input"
               autocomplete="name" value="<?= e(old($old, 'full_name')) ?>" required autofocus>
      </div>

      <div class="field-pair">
        <div class="field">
          <label class="field-label" for="username">Username</label>
          <input type="text" id="username" name="username" class="field-input"
                 autocomplete="off" value="<?= e(old($old, 'username')) ?>" required>
        </div>

        <div class="field">
          <label class="field-label" for="email">Email address</label>
          <input type="email" id="email" name="email" class="field-input"
                 autocomplete="email" inputmode="email"
                 value="<?= e(old($old, 'email')) ?>" required>
        </div>
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

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" data-loading-label="Creating account&hellip;">
          <?= icon('admins') ?> Create administrator
        </button>
        <a class="btn btn-secondary" href="<?= e(url('/admin/admins.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
