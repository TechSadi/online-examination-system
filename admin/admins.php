<?php
/**
 * admins.php – Admin: view all admin accounts, delete others
 * A logged-in admin cannot delete their own account.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db           = getDB();
$currentAdminId = (int)$_SESSION['admin_id'];

/* ── Delete another admin ──────────────────────────────── */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $targetId = (int)$_GET['delete'];

    if ($targetId === $currentAdminId) {
        $deleteError = 'You cannot delete your own account while logged in.';
    } else {
        // Prevent deleting the last admin
        $count = (int)$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        if ($count <= 1) {
            $deleteError = 'Cannot delete the only remaining admin account.';
        } else {
            $del = $db->prepare('DELETE FROM admins WHERE admin_id = ?');
            $del->execute([$targetId]);
            header('Location: ' . BASE_URL . '/admin/admins.php?msg=deleted');
            exit;
        }
    }
}

$msg = $_GET['msg'] ?? '';

/* ── Load all admins ───────────────────────────────────── */
$admins = $db->query('SELECT * FROM admins ORDER BY created_at ASC')->fetchAll();

$pageTitle = 'Manage Admins';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">

    <div style="display:flex;justify-content:space-between;align-items:center;
                margin-bottom:24px;flex-wrap:wrap;gap:12px;">
      <div>
        <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:2px;">
          Manage Administrators
        </h2>
        <p class="text-muted"><?= count($admins) ?> admin account(s) registered.</p>
      </div>
      <a href="<?= BASE_URL ?>/admin/register.php" class="btn btn-primary">
        ➕ Add New Admin
      </a>
    </div>

    <?php if ($msg === 'deleted'): ?>
      <div class="alert alert-success" data-auto-dismiss>✅ Admin account deleted.</div>
    <?php endif; ?>

    <?php if (!empty($deleteError)): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($deleteError) ?></div>
    <?php endif; ?>

    <!-- Info banner -->
    <div class="alert alert-info" style="margin-bottom:20px;">
      🔒 <strong>Note:</strong> New admins must register using the invite code
      (<code>EXAMHUB2025</code> by default — change it in
      <code>admin/register.php</code> before deploying).
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $i => $a):
            $isSelf = ((int)$a['admin_id'] === $currentAdminId);
          ?>
          <tr <?= $isSelf ? 'style="background:#eff6ff;"' : '' ?>>
            <td><?= $i + 1 ?></td>
            <td>
              <strong><?= htmlspecialchars($a['full_name'] ?: $a['username']) ?></strong>
              <?php if ($isSelf): ?>
                <span class="badge badge-primary" style="margin-left:6px;">You</span>
              <?php endif; ?>
            </td>
            <td><code><?= htmlspecialchars($a['username']) ?></code></td>
            <td><?= htmlspecialchars($a['email'] ?? '—') ?></td>
            <td class="text-muted"><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
            <td>
              <?php if ($isSelf): ?>
                <span class="text-muted" style="font-size:.82rem;">— current session —</span>
              <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/admins.php?delete=<?= $a['admin_id'] ?>"
                   class="btn btn-sm btn-danger"
                   data-confirm="Delete admin '<?= htmlspecialchars($a['username']) ?>'? This cannot be undone.">
                  🗑️ Delete
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
