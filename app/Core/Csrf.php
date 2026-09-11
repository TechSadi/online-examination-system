<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cross-site request forgery protection.
 *
 * One token per session, held in $_SESSION and never in a cookie, so a
 * cross-site request cannot read it and cannot guess it. Verification is
 * central: Csrf::guard() runs from the bootstrap for every state-changing
 * request, which means a new controller or form cannot forget to opt in.
 *
 * The token is rotated whenever the privilege level changes - sign-in and
 * sign-out - so a token captured before authentication is useless after it.
 */
final class Csrf
{
    private const KEY    = '_csrf_token';
    public  const FIELD  = '_token';
    private const HEADER = 'HTTP_X_CSRF_TOKEN';

    /** Methods that may not change state, and so need no token. */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * The token for this session, minted on first use.
     *
     * 32 random bytes from the CSPRNG; random_bytes() throws rather than
     * falling back to a weak source, so a token is never predictable.
     */
    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }

        return $token;
    }

    /** Issue a fresh token, discarding the old one. */
    public static function rotate(): void
    {
        Session::put(self::KEY, bin2hex(random_bytes(32)));
    }

    /**
     * Whether a submitted token matches the session's.
     *
     * hash_equals compares in constant time, so a caller cannot learn the
     * token a byte at a time by measuring how long a rejection takes.
     */
    public static function check(mixed $candidate): bool
    {
        $expected = Session::get(self::KEY);

        if (!is_string($expected) || $expected === '' || !is_string($candidate) || $candidate === '') {
            return false;
        }

        return hash_equals($expected, $candidate);
    }

    /**
     * Reject any state-changing request that does not carry a valid token.
     *
     * Called once from the bootstrap. Safe methods pass through untouched;
     * everything else must present the token in the form body or, for a
     * fetch/XHR caller, in the X-CSRF-Token header.
     */
    public static function guard(): void
    {
        if (in_array(Request::method(), self::SAFE_METHODS, true)) {
            return;
        }

        if (self::check(self::submittedToken())) {
            return;
        }

        self::reject();
    }

    /** The token presented by the request, from the body or the header. */
    private static function submittedToken(): ?string
    {
        $fromBody = $_POST[self::FIELD] ?? null;

        if (is_string($fromBody) && $fromBody !== '') {
            return $fromBody;
        }

        $fromHeader = $_SERVER[self::HEADER] ?? null;

        return is_string($fromHeader) && $fromHeader !== '' ? $fromHeader : null;
    }

    /**
     * Turn a rejected request away.
     *
     * The overwhelmingly common cause is a genuine user whose session
     * expired while a form sat open, so they are sent back to the page they
     * submitted with an explanation rather than shown a bare 403. The
     * request is never executed either way.
     */
    private static function reject(): never
    {
        error_log(sprintf(
            'CSRF rejection: %s %s from %s',
            Request::method(),
            Url::current(),
            Request::clientIp() ?: 'unknown'
        ));

        // A rejected POST must not be replayed by a refresh, so respond with
        // a redirect (which re-issues the form as a GET) rather than a body.
        Flash::error('Your session expired or the form was invalid. Please try again.');

        Response::redirectToCurrentPath();
    }
}
