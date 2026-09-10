<?php
/**
 * login.php – Admin login
 * Accepts username OR email address.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');   // username or email
    $password   = trim($_POST['password']   ?? '');

    if ($identifier && $password) {
        $db = getDB();

        // Allow login with username OR email
        $stmt = $db->prepare('
            SELECT admin_id, username, full_name, password
            FROM admins
            WHERE username = ? OR email = ?
            LIMIT 1
        ');
        $stmt->execute([$identifier, $identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name']     = $admin['full_name'] ?: $admin['username'];
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username / email or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

$pageTitle = 'Admin Login';
$role      = 'public';
require_once ROOT . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">

    <div class="auth-header">
      <div class="logo">🛠️ <span>Admin</span> Panel</div>
      <p>Sign in to manage the examination system</p>
    </div>

    <div class="auth-body">

      <!-- Tab switcher -->
      <div style="display:flex;border:2px solid var(--clr-border);border-radius:8px;overflow:hidden;margin-bottom:22px;">
        <span style="flex:1;text-align:center;padding:10px;font-weight:600;font-size:.88rem;
                     background:var(--clr-primary);color:#fff;cursor:default;">
          🔐 Login
        </span>
        <a href="<?= BASE_URL ?>/admin/register.php"
           style="flex:1;text-align:center;padding:10px;font-weight:600;font-size:.88rem;
                  background:#fff;color:var(--clr-muted);text-decoration:none;">
          📝 Register
        </a>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">🔒 <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="identifier">Username or Email</label>
          <input type="text" id="identifier" name="identifier" class="form-control"
            placeholder="admin  or  admin@examhub.com"
            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
            required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
            class="form-control" placeholder="Your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">
          🔐 Sign In
        </button>
      </form>

      <div class="divider"></div>
    </div>

    <div class="auth-footer">
      New administrator?
      <a href="<?= BASE_URL ?>/admin/register.php">Create an account</a>
    </div>

  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
