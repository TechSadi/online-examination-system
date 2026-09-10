<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session lifecycle in one place.
 *
 * Previously session_start() was a side effect of including auth.php and ran
 * with PHP default cookie flags. Centralising it lets every entry point share
 * one hardened configuration.
 */
final class Session
{
    /** Marks the moment the session was created, for the absolute timeout. */
    private const CREATED = '_created_at';
    /** Last request time, for the idle timeout. */
    private const SEEN    = '_last_seen';
    /** Last time the session id was rotated. */
    private const ROTATED = '_rotated_at';

    /** How often an active session's id is rotated, in seconds. */
    private const ROTATE_EVERY = 900;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        session_name((string) Config::get('session.name', 'examhub_session'));

        // Refuse session ids this server never issued, so an attacker cannot
        // fix a victim's session by planting one in a cookie or a URL.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        // 32 characters over a 5-bit alphabet: 160 bits of entropy. Stated
        // explicitly rather than inherited, so the id cannot quietly shorten
        // on a differently configured host.
        ini_set('session.sid_length', '32');
        ini_set('session.sid_bits_per_character', '5');

        // The collector must not reclaim a session the application still
        // considers valid.
        ini_set('session.gc_maxlifetime', (string) self::absoluteTimeout());

        $path = Url::basePath();

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $path === '' ? '/' : $path,
            'httponly' => true,
            'secure'   => Security::isHttps() || (bool) Config::get('session.secure', false),
            'samesite' => 'Lax',
        ]);

        session_start();

        self::enforceTimeouts();
        self::rotatePeriodically();
    }

    private static function idleTimeout(): int
    {
        return max(60, (int) Config::get('session.idle_timeout', 1800));
    }

    private static function absoluteTimeout(): int
    {
        return max(300, (int) Config::get('session.absolute_timeout', 28800));
    }

    /**
     * Expire a session that has been idle too long, or that has simply lived
     * too long, whichever comes first.
     *
     * The data is cleared and the id rotated rather than the session being
     * destroyed outright, so there is somewhere to leave the explanation the
     * user then reads on the login page.
     */
    private static function enforceTimeouts(): void
    {
        $now     = time();
        $created = (int) ($_SESSION[self::CREATED] ?? 0);
        $seen    = (int) ($_SESSION[self::SEEN] ?? 0);

        if ($created === 0) {
            $_SESSION[self::CREATED] = $now;
            $_SESSION[self::SEEN]    = $now;
            $_SESSION[self::ROTATED] = $now;

            return;
        }

        $idleTooLong  = $seen > 0 && ($now - $seen) > self::idleTimeout();
        $aliveTooLong = ($now - $created) > self::absoluteTimeout();

        if ($idleTooLong || $aliveTooLong) {
            $_SESSION = [];
            session_regenerate_id(true);

            $_SESSION[self::CREATED] = $now;
            $_SESSION[self::ROTATED] = $now;
            Flash::info('Your session expired. Please sign in again.');
        }

        $_SESSION[self::SEEN] = $now;
    }

    /**
     * Rotate the id of a long-running session periodically, so a stolen id
     * has a bounded useful life even with no sign-in event to trigger it.
     */
    private static function rotatePeriodically(): void
    {
        $now     = time();
        $rotated = (int) ($_SESSION[self::ROTATED] ?? 0);

        if ($rotated === 0) {
            $_SESSION[self::ROTATED] = $now;

            return;
        }

        if (($now - $rotated) >= self::ROTATE_EVERY) {
            session_regenerate_id(true);
            $_SESSION[self::ROTATED] = $now;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Read a value and remove it in the same call. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION[self::ROTATED] = time();
        }
    }

    /** Clear the data, expire the cookie, then destroy the session. */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}
