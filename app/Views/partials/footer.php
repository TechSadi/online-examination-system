<?php
/**
 * Page footer and script tags.
 *
 * @var bool $includeExamJS
 */
?>
<footer>
  <p>&copy; <?= date('Y') ?> <strong>ExamHub</strong> &ndash; Online Examination System.
     Built with love &#128151; by Sadibou Saidy.</p>
</footer>

<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php if (!empty($includeExamJS)): ?>
  <script src="<?= e(asset('js/exam.js')) ?>"></script>
<?php endif; ?>
