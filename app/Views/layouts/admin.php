<?php
/**
 * Admin layout: navbar, sidebar, content column.
 *
 * Individual admin pages used to open .admin-wrapper, include the sidebar and
 * open <main> themselves. That chrome now lives here once.
 *
 * @var string $content
 * @var string $pageTitle
 * @var string $role
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php \App\Core\View::partial('partials/head', ['pageTitle' => $pageTitle]); ?>
</head>
<body>

<?php \App\Core\View::partial('partials/navbar', ['role' => $role]); ?>

<div class="admin-wrapper">
  <?php \App\Core\View::partial('partials/sidebar'); ?>

  <main class="admin-content">
    <?php \App\Core\View::partial('partials/alerts', [
        'flashes' => $flashes ?? [],
        'errors'  => $errors  ?? [],
    ]); ?>

    <?= $content ?>
  </main>
</div>

<?php \App\Core\View::partial('partials/footer', ['includeExamJS' => $includeExamJS ?? false]); ?>
</body>
</html>
