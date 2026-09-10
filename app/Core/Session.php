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
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        session_name((string) Config::get('session.name', 'examhub_session'));

        // Refuse session IDs this server never issued.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $path = Url::basePath();

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $path === '' ? '/' : $path,
            'httponly' => true,
            'secure'   => self::isHttps() || (bool) Config::get('session.secure', false),
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
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
