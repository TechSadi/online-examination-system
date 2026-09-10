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
     * A non-negative integer from POST, then GET.
     * Anything non-numeric becomes the default, so "abc" never becomes 0
     * by accident and then matches a record.
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;

        if ($value === null || !is_scalar($value) || !is_numeric((string) $value)) {
            return $default;
        }

        return (int) $value;
    }

    /** A positive record identifier, or 0 when absent or invalid. */
    public static function id(string $key): int
    {
        $id = self::int($key, 0);

        return $id > 0 ? $id : 0;
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
}
