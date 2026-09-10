<?php
/**
 * Administrator accounts.
 *
 * @var list<array<string,mixed>> $admins
 * @var int                       $currentAdminId
 */
?>
<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title">Administrators</h1>
    <p class="page-subtitle">Accounts with full access to this console.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('/admin/register.php')) ?>">
      <?= icon('plus') ?> New administrator
    </a>
  </div>
</div>

<div class="stack">
  <div class="alert alert-info">
    <?= icon('lock') ?>
    <div class="alert-body">
      <p class="alert-title">There is no public sign-up for administrators</p>
      <p>Only a signed-in administrator can create another account, and every
         account listed here has the same level of access.</p>
    </div>
  </div>

  <div class="table-card">
    <div class="table-caption">
      <span><?= pluralise(count($admins), 'account') ?></span>
    </div>

    <div class="table-scroll">
      <table class="table table-stack">
        <thead>
          <tr>
            <th scope="col">Name</th>
            <th scope="col">Username</th>
            <th scope="col">Email</th>
            <th scope="col">Added</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $admin):
              $adminId = (int) $admin['admin_id'];
              $isSelf  = $adminId === $currentAdminId;
              $name    = (string) ($admin['full_name'] ?: $admin['username']);
          ?>
            <tr class="<?= $isSelf ? 'row-highlight' : '' ?>">
              <td data-label="Name" class="cell-lead">
                <span class="cell-primary"><?= e($name) ?></span>
                <?php if ($isSelf): ?>
                  <span class="badge badge-primary">You</span>
                <?php endif; ?>
              </td>
              <td data-label="Username"><code><?= e($admin['username']) ?></code></td>
              <td data-label="Email" class="cell-muted"><?= e($admin['email'] ?: '—') ?></td>
              <td data-label="Added" class="cell-muted"><?= e(format_date($admin['created_at'])) ?></td>
              <td class="cell-actions" data-label="">
                <?php if ($isSelf): ?>
                  <span class="text-muted text-sm">Signed in as this account</span>
                <?php else: ?>
                  <form method="POST" action="<?= e(url('/admin/admins.php')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="admin_id" value="<?= $adminId ?>">
                    <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"
                            aria-label="Delete administrator <?= e($admin['username']) ?>"
                            data-confirm-title="Delete this administrator?"
                            data-confirm="<?= e($name) ?> will lose access to the console immediately. This cannot be undone."
                            data-confirm-label="Delete administrator">
                      <?= icon('trash') ?>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
