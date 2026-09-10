<?php
/**
 * results.php – Admin: view all exam results (optionally filtered by student)
 */
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/auth.php';
requireAdmin();

$db        = getDB();
$studentId = (int)($_GET['student_id'] ?? 0);
$examId    = (int)($_GET['exam_id']    ?? 0);

// Build dynamic WHERE clause
$where  = [];
$params = [];
if ($studentId) { $where[] = 'r.student_id = ?'; $params[] = $studentId; }
if ($examId)    { $where[] = 'r.exam_id = ?';    $params[] = $examId; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$results = $db->prepare("
    SELECT r.score, r.total, r.date_taken, r.result_id,
           s.name AS student_name, s.email,
           e.title AS exam_title, e.exam_id
    FROM results r
    JOIN students s ON s.student_id = r.student_id
    JOIN exams    e ON e.exam_id    = r.exam_id
    $whereSQL
    ORDER BY r.date_taken DESC
");
$results->execute($params);
$rows = $results->fetchAll();

// Dropdowns
$allStudents = $db->query('SELECT student_id, name FROM students ORDER BY name')->fetchAll();
$allExams    = $db->query('SELECT exam_id, title FROM exams ORDER BY title')->fetchAll();

$pageTitle = 'View Results';
$role      = 'admin';
require_once ROOT . '/includes/header.php';
?>

<div class="admin-wrapper">
  <?php require_once ROOT . '/admin/admin_sidebar.php'; ?>

  <main class="admin-content">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;margin-bottom:4px;">Exam Results</h2>
    <p class="text-muted mb-2">Total records: <strong><?= count($rows) ?></strong></p>

    <!-- Filter Form -->
    <div class="card mb-2" style="margin-bottom:20px;">
      <div class="card-body" style="padding:16px;">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label style="margin-bottom:4px;">Filter by Student</label>
            <select name="student_id" class="form-control">
              <option value="">All Students</option>
              <?php foreach ($allStudents as $s): ?>
                <option value="<?= $s['student_id'] ?>" <?= $studentId == $s['student_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;flex:1;min-width:200px;">
            <label style="margin-bottom:4px;">Filter by Exam</label>
            <select name="exam_id" class="form-control">
              <option value="">All Exams</option>
              <?php foreach ($allExams as $e): ?>
                <option value="<?= $e['exam_id'] ?>" <?= $examId == $e['exam_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
            <a href="<?= BASE_URL ?>/admin/results.php" class="btn btn-outline btn-sm">✕ Clear</a>
          </div>
        </form>
      </div>
    </div>

    <?php if (empty($rows)): ?>
      <div class="empty-state">
        <div class="icon">📊</div>
        <p>No results found<?= $studentId || $examId ? ' for this filter' : '' ?>.</p>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Email</th>
              <th>Exam</th>
              <th>Score</th>
              <th>%</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $r):
              $pct    = $r['total'] > 0 ? round(($r['score'] / $r['total']) * 100) : 0;
              $passed = $pct >= 60;
            ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
              <td class="text-muted"><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['exam_title']) ?></td>
              <td><?= $r['score'] ?>/<?= $r['total'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="progress-bar" style="width:60px;margin:0;">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $passed ? 'var(--clr-success)' : 'var(--clr-danger)' ?>;"></div>
                  </div>
                  <span style="font-size:.85rem;font-weight:600;"><?= $pct ?>%</span>
                </div>
              </td>
              <td>
                <span class="badge <?= $passed ? 'badge-success' : 'badge-danger' ?>">
                  <?= $passed ? 'PASS' : 'FAIL' ?>
                </span>
              </td>
              <td class="text-muted"><?= date('M j, Y', strtotime($r['date_taken'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php require_once ROOT . '/includes/footer.php'; ?>
