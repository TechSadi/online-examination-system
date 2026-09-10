<?php

declare(strict_types=1);

namespace App\Core;

/**
 * URL generation.
 *
 * Replaces the previous BASE_URL constant, which hardcoded the folder name
 * in a regex and interpolated the Host header into every link. This derives
 * the base path by comparing the running script against the project root,
 * so the app works under any directory name, at a virtual-host root, or
 * under the PHP built-in server.
 *
 * URLs are root-relative by default, keeping them independent of the Host
 * header. Set APP_URL to emit absolute URLs instead.
 */
final class Url
{
    private static ?string $basePath = null;

    /** Build a URL for an application-absolute path such as "/student/login.php". */
    public static function to(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $configured = (string) Config::get('app.url', '');

        if ($configured !== '') {
            return rtrim($configured, '/') . $path;
        }

        $base = self::basePath();

        return $base === '' ? $path : $base . $path;
    }

    /** Build a URL for a file under public/assets. */
    public static function asset(string $path): string
    {
        return self::to('/public/assets/' . ltrim($path, '/'));
    }

    /**
     * The URL path prefix the application is served from:
     * "/online-exam-system" under XAMPP, empty at a virtual-host root.
     */
    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $scriptName = str_replace(DIRECTORY_SEPARATOR, '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptFile = str_replace(DIRECTORY_SEPARATOR, '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $root       = str_replace(DIRECTORY_SEPARATOR, '/', (string) Config::get('paths.root', ''));

        $scriptDirUrl = rtrim(dirname($scriptName), '/');
        $scriptDirFs  = rtrim(dirname($scriptFile), '/');

        // How many directories deep is this entry point inside the project?
        $depth = 0;
        if ($root !== '' && str_starts_with($scriptDirFs . '/', $root . '/')) {
            $relative = trim(substr($scriptDirFs, strlen($root)), '/');
            $depth    = $relative === '' ? 0 : count(explode('/', $relative));
        }

        $segments = array_values(array_filter(explode('/', $scriptDirUrl), static function ($s) {
            return $s !== '';
        }));

        if ($depth > 0) {
            $segments = array_slice($segments, 0, max(0, count($segments) - $depth));
        }

        self::$basePath = $segments === [] ? '' : '/' . implode('/', $segments);

        return self::$basePath;
    }

    /** Reset the memoised base path. */
    public static function reset(): void
    {
        self::$basePath = null;
    }

    /** The current request path, without its query string. */
    public static function current(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $pos = strpos($uri, '?');

        return $pos === false ? $uri : substr($uri, 0, $pos);
    }
}
