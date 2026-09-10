<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The reader's light/dark preference.
 *
 * Three states, not two. "System" is the default and the one most people
 * should stay on, because it follows the schedule their operating system
 * already runs; light and dark are explicit overrides for when it does not
 * suit the room they are sitting in.
 *
 * The choice lives in a cookie rather than the session for two reasons: it
 * has to survive signing out, and it has to be readable on the very first
 * request of a visit, before anything else has run. That is what lets the
 * layout stamp the theme onto <html> server-side, so the page arrives already
 * in the right colours. Applying it from JavaScript instead would mean
 * painting the light theme first and correcting it a frame later, which is
 * the white flash every dark-mode implementation is judged by - and the
 * Content-Security-Policy rightly refuses the inline script that is the usual
 * way of dodging it.
 *
 * The cookie is deliberately not HttpOnly: the toggle updates it from
 * JavaScript so that switching is instant rather than a page load. It records
 * a colour preference and nothing else, so there is nothing in it to steal.
 */
final class Theme
{
    public const COOKIE = 'examhub_theme';

    public const SYSTEM = 'system';
    public const LIGHT  = 'light';
    public const DARK   = 'dark';

    /** A year. Long enough that a preference is not something you re-set. */
    private const LIFETIME = 31536000;

    /**
     * The choices offered, in the order they are shown.
     *
     * @return array<string,array{0:string,1:string}> value => [icon, label]
     */
    public static function options(): array
    {
        return [
            self::SYSTEM => ['monitor', 'Match system'],
            self::LIGHT  => ['sun', 'Light'],
            self::DARK   => ['moon', 'Dark'],
        ];
    }

    /**
     * The stored preference, or "system" when there is none.
     *
     * Anything unrecognised is treated as absent. A cookie is client-supplied
     * data like any other, and this value is written into an HTML attribute.
     */
    public static function current(): string
    {
        $value = $_COOKIE[self::COOKIE] ?? '';

        return is_string($value) && isset(self::options()[$value]) ? $value : self::SYSTEM;
    }

    /** Whether the reader has overridden their system setting. */
    public static function isExplicit(): bool
    {
        return self::current() !== self::SYSTEM;
    }

    /**
     * The attribute for the <html> element.
     *
     * Empty on "system", so no attribute is written and the stylesheet's
     * prefers-color-scheme query is left to decide. Anything else stamps the
     * choice, which the stylesheet gives precedence over the media query.
     */
    public static function attribute(): string
    {
        return self::isExplicit()
            ? sprintf(' data-theme="%s"', self::current())
            : '';
    }

    /**
     * What to advertise in <meta name="color-scheme">.
     *
     * On "system" the page supports both and the browser picks; on an
     * explicit choice it is told which one, so the scrollbars and form
     * controls it draws itself match the page around them.
     */
    public static function colorScheme(): string
    {
        return self::isExplicit() ? self::current() : 'light dark';
    }

    /**
     * Store a choice, or clear it when the reader goes back to "system".
     *
     * @param string $theme one of the option keys; anything else is ignored
     */
    public static function remember(string $theme): void
    {
        if (!isset(self::options()[$theme])) {
            return;
        }

        // Scoped to the application's own base path, so an ExamHub preference
        // does not follow the reader into the next project sharing localhost.
        $path = Url::basePath();
        $path = $path === '' ? '/' : $path . '/';

        $options = [
            'path'     => $path,
            'secure'   => Security::isHttps(),
            'httponly' => false,
            'samesite' => 'Lax',
        ];

        if ($theme === self::SYSTEM) {
            setcookie(self::COOKIE, '', ['expires' => 1] + $options);
            unset($_COOKIE[self::COOKIE]);

            return;
        }

        setcookie(self::COOKIE, $theme, ['expires' => time() + self::LIFETIME] + $options);
        $_COOKIE[self::COOKIE] = $theme;
    }
}
