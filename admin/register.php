<?php
/**
 * register.php – Admin self-registration
 *
 * Security model: registering a new admin account requires knowing the
 * ADMIN_INVITE_CODE defined below. Change this secret before deploying.
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';

// ── Invite / secret code ──────────────────────────────────
// Change this to any secret string you want new admins to provide.
define('ADMIN_INVITE_CODE', 'EXAMHUB2025');

// Already logged in → go to dashboard
if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName   = trim($_POST['full_name']    ?? '');
    $username   = trim($_POST['username']     ?? '');
    $email      = trim($_POST['email']        ?? '');
    $password   = trim($_POST['password']     ?? '');
    $confirm    = trim($_POST['confirm']      ?? '');
    $inviteCode = trim($_POST['invite_code']  ?? '');

    /* ── Validation ─────────────────────────────────────── */
    if (!$fullName)                                       $errors[] = 'Full name is required.';
    if (strlen($username) < 3)                            $errors[] = 'Username must be at least 3 characters.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))     $errors[] = 'Username may only contain letters, numbers, and underscores.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = 'A valid email address is required.';
    if (strlen($password) < 6)                            $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                           $errors[] = 'Passwords do not match.';
    if ($inviteCode !== ADMIN_INVITE_CODE)                $errors[] = 'Invalid invite code. Contact the system owner.';

    if (empty($errors)) {
        $db = getDB();

        // Check uniqueness
        $chk = $db->prepare('SELECT admin_id FROM admins WHERE username = ? OR email = ?');
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $errors[] = 'That username or email is already registered as an admin.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare('
                INSERT INTO admins (username, email, full_name, password)
                VALUES (?, ?, ?, ?)
            ');
            $ins->execute([$username, $email, $fullName, $hash]);
            $success = 'Admin account created successfully! You can now log in.';
        }
    }
}

$pageTitle = 'Admin Registration';
$role      = 'public';
require_once ROOT . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box" style="max-width:500px;">
    <div class="auth-header">
      <div class="logo">🛠️ <span>Admin</span> Registration</div>
      <p>Create a new administrator account</p>
    </div>

    <div class="auth-body">

      <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-primary btn-full">Go to Admin Login →</a>

      <?php else: ?>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <div>
              <strong>Please fix the following:</strong>
              <ul style="margin:.5rem 0 0 1.2rem;">
                <?php foreach ($errors as $e): ?>
                  <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>

          <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" class="form-control"
              placeholder="e.g. John Smith"
              value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="username">Username *</label>
              <input type="text" id="username" name="username" class="form-control"
                placeholder="e.g. john_admin"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label for="email">Email Address *</label>
              <input type="email" id="email" name="email" class="form-control"
                placeholder="admin@school.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
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

          <!-- Invite code keeps registration restricted -->
          <div class="form-group">
            <label for="invite_code">
              Admin Invite Code *
              <span style="font-weight:400;color:var(--clr-muted);font-size:.8rem;">
                (provided by system owner)
              </span>
            </label>
            <input type="text" id="invite_code" name="invite_code" class="form-control"
              placeholder="Enter invite code" required
              style="letter-spacing:.12em;font-weight:600;">
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">
            🛠️ Create Admin Account
          </button>
        </form>

      <?php endif; ?>
    </div>

    <div class="auth-footer">
      Already have an account?
      <a href="<?= BASE_URL ?>/admin/login.php">Sign in here</a>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
