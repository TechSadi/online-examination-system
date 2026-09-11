<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * The pages shown when a request cannot be answered.
 *
 * Three requirements pull against each other here.
 *
 * An error page should look like the rest of the application, so it renders
 * through the ordinary view layer and picks up the same stylesheets, theme
 * and top bar.
 *
 * An error page must never fail. The reason for a 500 may well be that the
 * database is unreachable, the configuration is wrong, or a view is missing -
 * exactly the conditions under which the view layer itself stops working.
 * So every rendered page is attempted inside a try/catch and falls back to a
 * self-contained document with its CSS inline, which depends on nothing at
 * all.
 *
 * An error page must give nothing away. The copy below names no file, no
 * query and no component. The detail goes to the log, where the operator can
 * read it and the visitor cannot.
 */
final class ErrorPage
{
    /**
     * Title and explanation per status.
     *
     * Written for the person who hit it, not for the specification: "404"
     * means nothing to a student who followed a stale bookmark.
     *
     * @var array<int,array{0:string,1:string}>
     */
    private const MESSAGES = [
        400 => [
            'That request could not be understood',
            'Something about the request was malformed, so it was not carried out. '
            . 'Going back and trying again usually clears it.',
        ],
        403 => [
            'You do not have access to this',
            'This page belongs to someone else, or to an account with different '
            . 'permissions. If you think it should be yours, sign in again.',
        ],
        404 => [
            'This page does not exist',
            'The link may be out of date, or the exam or result it pointed at may '
            . 'have been removed.',
        ],
        405 => [
            'That is not how this page works',
            'The page was asked for in a way it does not accept. Going back and '
            . 'using the form on the page will work.',
        ],
        500 => [
            'Something went wrong',
            'This page could not be displayed. The problem has been recorded and '
            . 'will be looked into. Nothing you did caused it.',
        ],
        503 => [
            'The service is temporarily unavailable',
            'ExamHub is briefly unable to answer requests. This is usually short '
            . 'lived - trying again in a minute is worth doing before anything else.',
        ],
    ];

    /** Send a status page and stop. */
    public static function send(int $status): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo self::render($status);
        exit;
    }

    /**
     * The page body for a status.
     *
     * Never throws: a failure inside the view layer falls through to the
     * self-contained document, because the alternative is a blank page or a
     * PHP error where the explanation should be.
     */
    public static function render(int $status): string
    {
        [$heading, $message] = self::message($status);

        try {
            return View::capture('layouts/error', [
                'status'    => $status,
                'heading'   => $heading,
                'message'   => $message,
                'pageTitle' => $heading,
                'homeUrl'   => Url::to('/'),
            ]);
        } catch (Throwable) {
            // The view layer is part of what may be broken. Say nothing about
            // why - this is still a page a visitor sees.
            return self::fallback($status, $heading, $message, self::safeHomeUrl());
        }
    }

    /** @return array{0:string,1:string} */
    private static function message(int $status): array
    {
        return self::MESSAGES[$status] ?? self::MESSAGES[500];
    }

    /**
     * A link home that works even when configuration does not.
     *
     * Url::to() reads Config, which may be exactly what failed, so a throw
     * here is answered with the root rather than allowed to escape.
     */
    private static function safeHomeUrl(): string
    {
        try {
            return Url::to('/');
        } catch (Throwable) {
            return '/';
        }
    }

    /**
     * A complete document with no external dependency of any kind.
     *
     * No stylesheet, no font, no script, no image - so it renders correctly
     * whatever else is unavailable. It honours the visitor's colour scheme
     * through a media query rather than by reading their stored preference,
     * which would mean touching the session.
     */
    private static function fallback(int $status, string $heading, string $message, string $home): string
    {
        $h = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex">'
            . '<title>' . $h($heading) . '</title><style>'
            . ':root{color-scheme:light dark;--bg:#f4f6fb;--card:#fff;--ink:#1e2640;--muted:#6b7698;--accent:#3350e0}'
            . '@media(prefers-color-scheme:dark){:root{--bg:#11162a;--card:#1a2138;--ink:#e8ebf7;--muted:#9aa4c4;--accent:#8fa3ff}}'
            . '*{box-sizing:border-box}'
            . 'body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink);'
            . 'margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}'
            . '.box{background:var(--card);padding:40px;border-radius:14px;max-width:32rem;text-align:center;'
            . 'box-shadow:0 6px 28px rgba(16,24,56,.14)}'
            . '.code{font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:0 0 10px}'
            . 'h1{margin:0 0 12px;font-size:1.4rem;line-height:1.3}'
            . 'p{color:var(--muted);line-height:1.65;margin:0 0 24px}'
            . 'a{display:inline-block;background:var(--accent);color:#fff;text-decoration:none;font-weight:600;'
            . 'padding:11px 22px;border-radius:8px}'
            . '</style></head><body><div class="box">'
            . '<p class="code">Error ' . $status . '</p>'
            . '<h1>' . $h($heading) . '</h1>'
            . '<p>' . $h($message) . '</p>'
            . '<a href="' . $h($home) . '">Back to safety</a>'
            . '</div></body></html>';
    }
}
