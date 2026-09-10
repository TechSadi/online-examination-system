<?php

declare(strict_types=1);

/**
 * Application bootstrap.
 *
 * Every entry point in admin/, student/ and index.php requires this file and
 * nothing else. It replaces the 6-10 line preamble that used to be copied
 * into all 22 pages.
 *
 * Order matters: config must exist before the error handler can decide how
 * verbose to be, and before the session can read its cookie settings.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Security;
use App\Core\Session;

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

define('APP_ROOT', dirname(__DIR__));

/* ── Autoloader ─────────────────────────────────────────────
   PSR-4 style, mapping the App\ namespace onto app/. The project has no
   Composer dependency, so this is deliberately about ten lines.          */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App' . chr(92);          // chr(92) is the namespace separator
    $length = strlen($prefix);

    if (strncmp($class, $prefix, $length) !== 0) {
        return;
    }

    $relative = substr($class, $length);
    $path     = __DIR__ . '/' . str_replace(chr(92), '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

/* ── Configuration ───────────────────────────────────────── */
Env::load(APP_ROOT . '/.env');
Config::set(require __DIR__ . '/Config/config.php');

/* ── Error handling ──────────────────────────────────────── */
ErrorHandler::register();
date_default_timezone_set('UTC');

/* ── Session ─────────────────────────────────────────────── */
Session::start();

/* ── Response headers ────────────────────────────────────────
   Sent before any controller runs, so every page carries them - including
   the error pages, which are rendered from the exception handler.       */
Security::sendHeaders();

/* ── CSRF ────────────────────────────────────────────────────
   Enforced here rather than per controller. Every entry point in admin/,
   student/ and index.php passes through this file, so a state-changing
   request cannot reach a controller without a valid token and a new form
   cannot forget to opt in.                                              */
Csrf::guard();

/* ── View helpers (e(), url(), asset(), csrf_field(), ...) ── */
require __DIR__ . '/Helpers/functions.php';
