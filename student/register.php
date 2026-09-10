<?php
/**
 * register.php – Student self-registration
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';

// Redirect if already logged in
if (isStudentLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    // Validation
    if (!$name)                          $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if (strlen($password) < 6)           $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)          $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();

        // Check email uniqueness
        $stmt = $db->prepare('SELECT student_id FROM students WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already registered.';
        } else {
            // Insert new student
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO students (name, email, password) VALUES (?, ?, ?)');
            $ins->execute([$name, $email, $hash]);
            $success = 'Account created! You can now log in.';
        }
    }
}

$pageTitle = 'Student Registration';
$role      = 'public';
require_once ROOT . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <div class="logo">📝 <span>Exam</span>Hub</div>
      <p>Create your student account</p>
    </div>
    <div class="auth-body">
      <?php if ($success): ?>
        <div class="alert alert-success" data-auto-dismiss>✅ <?= htmlspecialchars($success) ?></div>
        <div class="text-center">
          <a href="<?= BASE_URL ?>/student/login.php" class="btn btn-primary btn-full">Go to Login →</a>
        </div>
      <?php else: ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <div><strong>Please fix the following:</strong><ul style="margin:.5rem 0 0 1.2rem;">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul></div>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Jane Smith"
              value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="jane@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 chars" required>
            </div>
            <div class="form-group">
              <label for="confirm">Confirm Password</label>
              <input type="password" id="confirm" name="confirm" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">Create Account →</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="auth-footer">
      Already have an account? <a href="<?= BASE_URL ?>/student/login.php">Sign in here</a>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
