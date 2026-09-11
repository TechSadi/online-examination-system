<?php

declare(strict_types=1);

/**
 * Router for PHP's built-in server.
 *
 *   php -S localhost:8000 bin/router.php
 *
 * The built-in server reads no .htaccess, and .htaccess is the whole of this
 * application's path protection. Started without a router it will happily
 * serve .env - database password and all - to anything that asks for it, run
 * any file under app/ as a script, and hand out the migrations. It also
 * answers a URL matching no file with the home page and a 200, so a broken
 * link looks like it worked.
 *
 * That matters because the README offers `php -S` as the way to run the
 * project without Apache, and a developer's laptop on a conference wifi is
 * exactly where a leaked .env does damage.
 *
 * So the rules below mirror .htaccess rather than inventing their own. This
 * file exists only for local development; in production Apache enforces the
 * real ones.
 */

$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = '/' . ltrim(rawurldecode($path), '/');

/** Directories that are not web-accessible, mirroring .htaccess. */
$privateDirs = ['app', 'bin', 'database', 'docker', 'storage'];

/** File types that must never be downloadable. */
$privatePattern = '#\.(sql|log|md|ini|conf|sh|ya?ml|lock|dist)$#i';

$isPrivate = preg_match('#^/(' . implode('|', $privateDirs) . ')(/|$)#i', $path) === 1
    || preg_match($privatePattern, $path) === 1
    // Any dotfile: .env, .git, .htaccess, .gitattributes, .dockerignore.
    || preg_match('#(^|/)\.[^/]#', $path) === 1
    // The Dockerfile has no extension to match on.
    || preg_match('#(^|/)Dockerfile$#i', $path) === 1;

if ($isPrivate) {
    // 404 rather than 403, so the answer does not confirm the file is there.
    $_SERVER['EXAMHUB_STATUS'] = '404';
    require __DIR__ . '/../error.php';

    return true;
}

$file = __DIR__ . '/../' . ltrim($path, '/');

// An existing static file: let the server deliver it with its own MIME
// handling rather than reimplementing that here.
if ($path !== '/' && is_file($file) && !str_ends_with($path, '.php')) {
    return false;
}

// An existing PHP entry point, or a directory with one.
if (is_file($file) && str_ends_with($path, '.php')) {
    return false;
}

if ($path === '/' || is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';

    if (is_file($index)) {
        return false;
    }
}

$_SERVER['EXAMHUB_STATUS'] = '404';
require __DIR__ . '/../error.php';

return true;
