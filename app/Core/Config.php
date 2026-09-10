<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Read-only application configuration, addressed with dot notation.
 *
 *     Config::get('db.host');
 *     Config::get('exam.pass_mark');
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    /** @param array<string,mixed> $items */
    public static function set(array $items): void
    {
        self::$items = $items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function isProduction(): bool
    {
        return self::get('app.env') === 'production';
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('app.debug', false) && !self::isProduction();
    }
}
