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

use App\Middleware\Auth;
?>
<?php if ($showFooter ?? true): ?>
<footer class="app-footer">
  <div class="app-footer-inner">
    <p>&copy; <?= date('Y') ?> <strong>ExamHub</strong> &middot; Online Examination System</p>

    <?php /* The second route to the administration console, and the one that
             does not depend on the width of the top bar. A staff sign-in link
             in the footer is where people look for it, and this one is here
             because the top bar's used to be the only one in the application -
             so hiding it below 560px left administrators on a phone with no
             way in at all.

             Hidden once an administrator is signed in, where it would only
             point at a page that bounces them straight back. */ ?>
    <?php if (!Auth::isAdmin()): ?>
      <nav class="app-footer-nav" aria-label="Footer">
        <a href="<?= e(url('/admin/login.php')) ?>">Administrator sign in</a>
      </nav>
    <?php endif; ?>

    <p class="text-muted">Built by Sadibou Saidy</p>
  </div>
</footer>
<?php endif; ?>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if (!empty($includeExamJS)): ?>
  <script src="<?= e(asset('js/exam.js')) ?>" defer></script>
<?php endif; ?>
