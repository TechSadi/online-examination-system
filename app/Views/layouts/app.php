<?php
/**
 * Public and student layout.
 *
 * @var string $content   rendered page body
 * @var string $pageTitle
 * @var string $role
 */

use App\Core\Icons;
use App\Core\Theme;
?>
<!DOCTYPE html>
<html lang="en"<?= Theme::attribute() ?>>
<head>
  <?php \App\Core\View::partial('partials/head', [
      'pageTitle'      => $pageTitle,
      'includeExamCSS' => $includeExamCSS ?? false,
  ]); ?>
</head>
<body>
<?= Icons::sprite() ?>

<a class="skip-link" href="#main">Skip to main content</a>

<?php \App\Core\View::partial('partials/topbar', ['role' => $role]); ?>

<main id="main" class="app-main" tabindex="-1">
  <?= $content ?>
</main>

<?php \App\Core\View::partial('partials/confirm_dialog'); ?>

<?php \App\Core\View::partial('partials/footer', [
    'includeExamJS' => $includeExamJS ?? false,
    'showFooter'    => !in_array($role, ['auth', 'exam'], true),
]); ?>
</body>
</html>
