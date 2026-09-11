<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Typed access to request input.
 *
 * Every page previously repeated casts such as (int)($_GET['exam_id'] ?? 0)
 * and trim($_POST['email'] ?? ''). Centralising them keeps the coercion rules
 * identical everywhere and stops raw superglobals leaking into controllers.
 */
final class Request
{
    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    /** A trimmed string from POST, then GET. */
    public static function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** A trimmed string from POST only. */
    public static function post(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * A raw POST string with only surrounding whitespace removed from the
     * ends of lines preserved. Used for passwords, which must not be altered.
     */
    public static function raw(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /** A trimmed string from GET only. */
    public static function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * An integer from POST, then GET.
     *
     * Strict: only an optional sign followed by digits is accepted. The
     * previous is_numeric() test let through values PHP would happily cast
     * but nobody meant - "12.9" became 12, "1e3" became 1000, " 12" became
     * 12 - which is how a malformed id quietly turns into a valid one.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;

        if ($value === null || !is_scalar($value)) {
            return $default;
        }

        $raw = trim((string) $value);

        return preg_match('/^-?[0-9]{1,18}$/', $raw) === 1 ? (int) $raw : $default;
    }

    /** A positive record identifier, or 0 when absent or invalid. */
    public static function id(string $key): int
    {
        $id = self::int($key, 0);

        return $id > 0 ? $id : 0;
    }

    /**
     * Whether a key was supplied but does not name a usable identifier.
     *
     * Lets a caller tell "no filter" apart from "a filter that is nonsense",
     * so a malformed id can be rejected instead of silently behaving as
     * though nothing was asked for.
     */
    public static function hasInvalidId(string $key): bool
    {
        if (!self::has($key)) {
            return false;
        }

        $value = $_POST[$key] ?? $_GET[$key] ?? null;

        if (!is_scalar($value) || trim((string) $value) === '') {
            return false;
        }

        return self::id($key) === 0;
    }

    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * All POST values, for repopulating a form after a failed submission.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return $_POST;
    }

    /**
     * The address the request came from.
     *
     * REMOTE_ADDR is the truth on a directly exposed server, and a client
     * cannot forge it. Behind a reverse proxy it is the proxy, which is a
     * problem rather than a detail: the login throttle counts failures per
     * address, so every user in the world sharing one proxy address would
     * share one counter and a handful of failures anywhere would lock out
     * everybody.
     *
     * X-Forwarded-For fixes that, but only where the deployment declares it
     * sits behind a proxy that sets the header itself. Trusting it otherwise
     * would be worse than the problem it solves: an attacker could send a
     * different value on every request and give themselves an unlimited
     * supply of fresh throttle counters.
     *
     * The leftmost entry is the original client. Entries appended by
     * intermediaries follow it, and anything the client sent itself is
     * inside the leftmost position, so the value is validated as an address
     * before it is used and the raw header is never trusted as-is.
     */
    public static function clientIp(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        if (!(bool) Config::get('app.trust_proxy', false)) {
            return $remote;
        }

        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');

        if ($forwarded === '') {
            return $remote;
        }

        foreach (explode(',', $forwarded) as $candidate) {
            $candidate = trim($candidate);

            // Strip the port from "203.0.113.4:51234" and the brackets from
            // the "[2001:db8::1]:443" form IPv6 uses.
            if (str_starts_with($candidate, '[')) {
                $close     = strpos($candidate, ']');
                $candidate = $close === false ? $candidate : substr($candidate, 1, $close - 1);
            } elseif (substr_count($candidate, ':') === 1) {
                $candidate = strstr($candidate, ':', true) ?: $candidate;
            }

            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }

        return $remote;
    }
}
