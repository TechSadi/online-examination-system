<?php
/**
 * Layout for the 4xx and 5xx pages.
 *
 * Deliberately not layouts/app. The reason a page failed may be that the
 * session, the database or the configuration is unavailable, and the top bar
 * asks all three what to draw. This layout reads nothing but the theme
 * cookie, so the only thing that can break it is the stylesheet failing to
 * load - and ErrorPage catches even that, falling back to a document with no
 * external dependency at all.
 *
 * @var int    $status
 * @var string $heading
 * @var string $message
 * @var string $pageTitle
 * @var string $homeUrl
 */

use App\Core\Icons;
use App\Core\Theme;
use App\Core\View;

$icon = match (true) {
    $status === 404 => 'search',
    $status === 403 => 'lock',
    default         => 'warning',
};
?>
<!DOCTYPE html>
<html lang="en"<?= Theme::attribute() ?>>
<head>
  <?php View::partial('partials/head', [
      'pageTitle' => $pageTitle,
      'role'      => 'error',
  ]); ?>
</head>
<body class="error-body">
<?= Icons::sprite() ?>

<main class="error-page" id="main">
  <a class="app-brand" href="<?= e($homeUrl) ?>">
    <span class="app-brand-mark"><?= icon('logo') ?></span>
    <span class="app-brand-text">ExamHub</span>
  </a>

  <div class="error-card">
    <div class="empty-icon"><?= icon($icon) ?></div>

    <p class="error-status">Error <?= e((string) $status) ?></p>
    <h1 class="error-title"><?= e($heading) ?></h1>
    <p class="error-text"><?= e($message) ?></p>

    <div class="error-actions">
      <a href="<?= e($homeUrl) ?>" class="btn btn-primary">
        <?= icon('arrow-left') ?> Back to safety
      </a>
    </div>
  </div>
</main>
</body>
</html>
