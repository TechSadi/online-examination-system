<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Centralised error handling.
 *
 * Development : message, file, line and trace rendered in the page.
 * Production  : a generic apology; the detail goes to storage/logs/app.log.
 *
 * Nothing here ever echoes SQL, credentials or configuration to the browser.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        ini_set('display_errors', Config::isDebug() ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /** Promote notices and warnings to exceptions so they cannot pass silently. */
    public static function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        self::log($e);

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo Config::isDebug() ? self::renderDebug($e) : self::renderGeneric();
        exit(1);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

        if ($error === null || !in_array($error['type'], $fatal, true)) {
            return;
        }

        self::handleException(new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    private static function log(Throwable $e): void
    {
        $dir = (string) Config::get('paths.logs', sys_get_temp_dir());

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $entry = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            str_repeat('-', 78)
        );

        @file_put_contents($dir . '/app.log', $entry, FILE_APPEND | LOCK_EX);
    }

    private static function renderGeneric(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Something went wrong</title><style>'
            . 'body{font-family:system-ui,-apple-system,sans-serif;background:#f4f6fb;color:#1e2640;margin:0;'
            . 'display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}'
            . '.box{background:#fff;padding:40px;border-radius:10px;max-width:460px;text-align:center;'
            . 'box-shadow:0 6px 24px rgba(26,60,110,.12)}h1{margin:0 0 12px;font-size:1.35rem}'
            . 'p{color:#6b7698;line-height:1.6;margin:0 0 20px}a{color:#1a3c6e;font-weight:600;text-decoration:none}'
            . '</style></head><body><div class="box"><h1>Something went wrong</h1>'
            . '<p>This page could not be displayed. The problem has been logged and will be looked into.</p>'
            . '<a href="' . htmlspecialchars(Url::to('/'), ENT_QUOTES, 'UTF-8') . '">&larr; Back to safety</a>'
            . '</div></body></html>';
    }

    private static function renderDebug(Throwable $e): string
    {
        $h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<title>Application error</title><style>'
            . 'body{font-family:ui-monospace,Consolas,monospace;background:#1e2640;color:#e6e9f5;padding:32px;margin:0}'
            . 'h1{color:#ff7b6b;font-size:1.15rem;margin:0 0 10px}'
            . 'h2{font-size:.78rem;color:#8f9ac2;margin:22px 0 6px;text-transform:uppercase;letter-spacing:.09em}'
            . 'pre{background:#141a2e;padding:16px;border-radius:8px;overflow:auto;font-size:.8rem;line-height:1.55;margin:0}'
            . 'code{color:#ffd479}</style></head><body>'
            . '<h1>' . $h($e::class) . '</h1>'
            . '<pre>' . $h($e->getMessage()) . '</pre>'
            . '<h2>Location</h2><pre><code>' . $h($e->getFile()) . ':' . $e->getLine() . '</code></pre>'
            . '<h2>Stack trace</h2><pre>' . $h($e->getTraceAsString()) . '</pre>'
            . '<h2>Why am I seeing this?</h2>'
            . '<pre>APP_DEBUG=true and APP_ENV is not "production". Set APP_ENV=production to hide it.</pre>'
            . '</body></html>';
    }
}
