<?php
/**
 * Public and student layout.
 *
 * @var string $content   rendered page body
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

<?= $content ?>

<?php \App\Core\View::partial('partials/footer', ['includeExamJS' => $includeExamJS ?? false]); ?>
</body>
</html>
