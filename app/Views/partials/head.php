<?php
/**
 * Document head.
 *
 * The stylesheets are split by responsibility rather than shipped as one
 * file, so a change to a token or a component is easy to find and hard to
 * duplicate. They are small, cached hard (each URL carries the file's
 * modification time) and requested in parallel, and the set below is the
 * whole of the application's CSS - there is no framework underneath it.
 *
 * @var string $pageTitle
 * @var bool   $includeExamCSS   the exam interface loads one extra sheet
 * @var string $role             'public' | 'auth' | 'student' | 'exam' | 'admin'
 * @var string $metaDescription  overrides the default, for public pages
 */
use App\Core\Theme;

$appName = (string) config('app.name', 'ExamHub');
$role    = $role ?? 'public';

$stylesheets = ['css/tokens.css', 'css/base.css', 'css/components.css', 'css/layout.css', 'css/pages.css'];

if (!empty($includeExamCSS)) {
    $stylesheets[] = 'css/exam.css';
}

// The favicon is a data URI. Its markup must be percent-encoded: an earlier
// version embedded a raw <svg> in the href, which terminated the attribute
// early and left a stray `">` visible on every page.
$favicon = 'data:image/svg+xml,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">'
    . '<rect width="32" height="32" rx="7" fill="#3350e0"/>'
    . '<path d="M9 16.5l4.5 4.5L23 11.5" fill="none" stroke="#fff" '
    . 'stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>'
);

// Only the marketing page and the sign-in screens are meant to be found in a
// search engine. Everything behind a sign-in is somebody's dashboard, paper
// or result: indexing those would be pointless, since a crawler is only ever
// shown the login redirect, and actively bad if a URL ever did leak.
$indexable = in_array($role, ['public', 'auth'], true);

$description = $metaDescription
    ?? $appName . ' - sit timed, automatically graded exams online. '
     . 'Educators set the paper; the server keeps the clock and marks it.';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="<?= e(Theme::colorScheme()) ?>">
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="<?= $indexable ? 'index, follow' : 'noindex, nofollow' ?>">
<title><?= e($pageTitle) ?> &middot; <?= e($appName) ?></title>
<?php if ($indexable): ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($appName) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?> &middot; <?= e($appName) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta name="twitter:card" content="summary">
<?php endif; ?>
<?php foreach ($stylesheets as $sheet): ?>
<link rel="stylesheet" href="<?= e(asset($sheet)) ?>">
<?php endforeach; ?>
<link rel="icon" href="<?= e($favicon) ?>">
