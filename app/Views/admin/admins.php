<?php
/**
 * Administrator accounts.
 *
 * @var list<array<string,mixed>> $admins
 * @var int                       $currentAdminId
 */
?>
<div class="page-heading page-heading-split">
  <div>
    <h2>Manage Administrators</h2>
    <p class="text-muted"><?= count($admins) ?> admin account(s) registered.</p>
  </div>
  <a href="<?= e(url('/admin/register.php')) ?>" class="btn btn-primary">&#10133; Add New Admin</a>
</div>

<div class="alert alert-info mb-2">
  &#128274; <strong>Note:</strong> Only a signed-in administrator can create another
  administrator account. There is no public registration page.
</div>

<div class="table-wrapper">
  <table>
    <thead>
      <tr><th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Registered</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $i => $admin):
          $isSelf = (int) $admin['admin_id'] === $currentAdminId;
      ?>
        <tr class="<?= $isSelf ? 'row-self' : '' ?>">
          <td><?= $i + 1 ?></td>
          <td>
            <strong><?= e($admin['full_name'] ?: $admin['username']) ?></strong>
            <?php if ($isSelf): ?>
              <span class="badge badge-primary">You</span>
            <?php endif; ?>
          </td>
          <td><code><?= e($admin['username']) ?></code></td>
          <td><?= e($admin['email'] ?: '-') ?></td>
          <td class="text-muted"><?= e(format_date($admin['created_at'])) ?></td>
          <td>
            <?php if ($isSelf): ?>
              <span class="text-muted text-sm">&mdash; current session &mdash;</span>
            <?php else: ?>
              <form method="POST" action="<?= e(url('/admin/admins.php')) ?>" class="inline-form">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="admin_id" value="<?= (int) $admin['admin_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm="Delete admin &quot;<?= e($admin['username']) ?>&quot;? This cannot be undone.">
                  &#128465; Delete
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
