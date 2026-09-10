<?php
/**
 * login.php – Student login
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';

if (isStudentLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT student_id, name, password FROM students WHERE email = ?');
        $stmt->execute([$email]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password'])) {
            // Regenerate session to prevent fixation
            session_regenerate_id(true);
            $_SESSION['student_id']   = $student['student_id'];
            $_SESSION['student_name'] = $student['name'];
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

$pageTitle = 'Student Login';
$role      = 'public';
require_once ROOT . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box">
    <div class="auth-header">
      <div class="logo">📝 <span>Exam</span>Hub</div>
      <p>Sign in to your student account</p>
    </div>
    <div class="auth-body">
      <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="jane@example.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Your password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg mt-1">Sign In →</button>
      </form>
    </div>
    <div class="auth-footer">
      Don't have an account? <a href="<?= BASE_URL ?>/student/register.php">Register here</a>
    </div>
  </div>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
