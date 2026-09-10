<?php
/**
 * Page footer and script tags.
 *
 * The sign-in and examination screens opt out of the footer. On a sign-in
 * page it would push a scrollbar onto a composition that otherwise fits
 * exactly; on the exam screen it is one more thing between the candidate and
 * the paper. The scripts are emitted either way.
 *
 * @var bool $includeExamJS
 * @var bool $showFooter
 */
?>
<?php if ($showFooter ?? true): ?>
<footer class="app-footer">
  <div class="app-footer-inner">
    <p>&copy; <?= date('Y') ?> <strong>ExamHub</strong> &middot; Online Examination System</p>
    <p class="text-muted">Built by Sadibou Saidy</p>
  </div>
</footer>
<?php endif; ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if (!empty($includeExamJS)): ?>
  <script src="<?= e(asset('js/exam.js')) ?>" defer></script>
<?php endif; ?>
