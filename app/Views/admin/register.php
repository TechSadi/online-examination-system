<?php
/**
 * Create an administrator account. Reachable only by a signed-in admin.
 *
 * @var list<string>        $errors
 * @var array<string,mixed> $old
 */
?>
<div class="page-heading">
  <h2>Add Administrator</h2>
  <p class="text-muted">Create a new administrator account.</p>
</div>

<div class="card card-narrow">
  <div class="card-header">&#128737; New Administrator</div>
  <div class="card-body">
    <form method="POST" novalidate>
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" class="form-control"
               placeholder="e.g. John Smith" value="<?= e(old($old, 'full_name')) ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="username">Username *</label>
          <input type="text" id="username" name="username" class="form-control"
                 placeholder="e.g. john_admin" value="<?= e(old($old, 'username')) ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address *</label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="admin@school.com" value="<?= e(old($old, 'email')) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password *</label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Min 6 characters" required>
        </div>
        <div class="form-group">
          <label for="confirm">Confirm Password *</label>
          <input type="password" id="confirm" name="confirm" class="form-control"
                 placeholder="Repeat password" required>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">&#128736; Create Admin Account</button>
        <a href="<?= e(url('/admin/admins.php')) ?>" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
