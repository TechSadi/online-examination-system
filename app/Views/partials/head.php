<?php
/**
 * Document head.
 *
 * @var string $pageTitle
 */
$appName = (string) config('app.name', 'ExamHub');
// The favicon is a data URI. Its markup must be percent-encoded: the previous
// version embedded a raw <svg> inside the href, which terminated the attribute
// early and left a stray `">` visible on every page.
$favicon = 'data:image/svg+xml,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
    . '<text y=".9em" font-size="90">&#128221;</text></svg>'
);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> &ndash; <?= e($appName) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<link rel="icon" href="<?= e($favicon) ?>">
