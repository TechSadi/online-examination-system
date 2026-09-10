<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response security headers.
 *
 * The policy below is written against what this application actually does,
 * not copied from a template:
 *
 *   script-src 'self'   - every script is an external file under
 *                         public/assets/js. There is not one inline <script>
 *                         or on* attribute in the view layer, so scripts can
 *                         be locked down completely. This is the header that
 *                         turns a hypothetical HTML injection into a dead end.
 *   style-src  + inline - progress bars and score meters set their width with
 *                         a style attribute, and the error pages carry an
 *                         inline <style>. A nonce cannot cover style
 *                         attributes, so 'unsafe-inline' is required here and
 *                         is honest about it.
 *   img-src    + data:  - the favicon is a data: URI built in partials/head.
 *   frame-ancestors     - the exam interface must not be framed; that is what
 *                         makes a clickjacked submission possible.
 *   form-action 'self'  - a form injected into a page cannot post answers or
 *                         credentials off-origin.
 */
final class Security
{
    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Security-Policy: ' . self::policy());

        // Never let a browser second-guess a declared Content-Type.
        header('X-Content-Type-Options: nosniff');

        // Belt and braces alongside frame-ancestors, for older browsers.
        header('X-Frame-Options: DENY');

        // Send the origin to other sites, the full path only to ourselves,
        // so exam and result URLs do not leak in outbound Referer headers.
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // This application needs none of these device APIs.
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // Pages are user-specific and often carry results; keep them out of
        // shared caches and out of the back/forward cache after sign-out.
        header('Cache-Control: no-store, no-cache, must-revalidate, private');

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    private static function policy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
        ]);
    }

    /**
     * Whether the request arrived over TLS.
     *
     * X-Forwarded-Proto is only consulted when the deployment declares that
     * it sits behind a proxy. Trusting it unconditionally would let a client
     * assert its own connection was secure.
     */
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        return (bool) Config::get('app.trust_proxy', false)
            && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
