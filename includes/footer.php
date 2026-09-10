<?php
/**
 * footer.php – Shared footer + closing tags
 */
?>
<footer>
  <p>&copy; <?= date('Y') ?> <strong>ExamHub</strong> – Online Examination System. Built with love 💗 by Sadibou Saidy.</p>
</footer>

<script src="<?= BASE_URL ?>/js/app.js"></script>
<?php if (!empty($includeExamJS)): ?>
<script src="<?= BASE_URL ?>/js/exam.js"></script>
<?php endif; ?>
</body>
</html>
