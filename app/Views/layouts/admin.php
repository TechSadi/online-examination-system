<?php
/**
 * Admin layout: top bar, sidebar, content column.
 *
 * Individual admin pages used to open .admin-wrapper, include the sidebar and
 * open <main> themselves. That chrome lives here once.
 *
 * @var string $content
 * @var string $pageTitle
 * @var string $role
 */

use App\Core\Icons;
use App\Core\Theme;
?>
<!DOCTYPE html>
<html lang="en"<?= Theme::attribute() ?>>
<head>
  <?php \App\Core\View::partial('partials/head', ['pageTitle' => $pageTitle]); ?>
</head>
<body>
<?= Icons::sprite() ?>

<a class="skip-link" href="#main">Skip to main content</a>

<?php \App\Core\View::partial('partials/topbar', ['role' => $role]); ?>

<div class="app-shell">
  <?php \App\Core\View::partial('partials/sidebar'); ?>

  <main id="main" class="app-main" tabindex="-1">
    <div class="page-messages">
      <?php \App\Core\View::partial('partials/alerts', [
          'flashes' => $flashes ?? [],
          'errors'  => $errors  ?? [],
      ]); ?>
    </div>

    <?= $content ?>
  </main>
</div>

<?php \App\Core\View::partial('partials/confirm_dialog'); ?>

<?php \App\Core\View::partial('partials/footer', ['includeExamJS' => $includeExamJS ?? false]); ?>
</body>
</html>
