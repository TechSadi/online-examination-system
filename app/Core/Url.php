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

    /**
     * Build a URL for a file under public/assets.
     *
     * The file's modification time is appended as a query string. Without it
     * a redesigned stylesheet reaches returning visitors only once their
     * cached copy expires, which is exactly the sort of half-updated page
     * that gets reported as a rendering bug. With it, the URL changes the
     * moment the file does, so the asset can be cached hard and still never
     * be served stale.
     */
    public static function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $url      = self::to('/public/assets/' . $relative);
        $file     = rtrim((string) Config::get('paths.root', ''), '/\\') . '/public/assets/' . $relative;

        $modified = is_file($file) ? filemtime($file) : false;

        return $modified === false ? $url : $url . '?v=' . $modified;
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

        $scriptName = self::slashes((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptFile = self::slashes((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $root       = self::slashes((string) Config::get('paths.root', ''));

        // dirname() is applied before the separators are normalised again
        // because on Windows it returns "\" for the root, whatever it was
        // given. Left alone that backslash survives as a path segment and
        // every link on the page comes out as "/\/student/login.php".
        $scriptDirUrl = rtrim(self::slashes(dirname($scriptName)), '/');
        $scriptDirFs  = rtrim(self::slashes(dirname($scriptFile)), '/');

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

    /** Normalise Windows separators to the forward slashes URLs use. */
    private static function slashes(string $path): string
    {
        return str_replace(chr(92), '/', $path);
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

    /**
     * The current request path including its query string.
     *
     * Used where a form has to return the user to exactly where they were.
     * Dropping the query would send someone who changed the theme on
     * "?q=jawara&sort=name&page=2" back to an unfiltered first page, which
     * reads as the control having done something it did not.
     *
     * Callers must still validate this as a redirect target - it comes from
     * REQUEST_URI, which is client-controlled.
     */
    public static function currentWithQuery(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
