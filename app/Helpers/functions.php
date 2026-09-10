<?php

declare(strict_types=1);

/**
 * Global view helpers.
 *
 * Templates read far better with short function names than with fully
 * qualified static calls, so these few are declared globally. Business rules
 * live in Services; only presentation concerns belong here.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Icons;
use App\Core\Url;
use App\Services\GradingService;

if (!function_exists('e')) {
    /**
     * Escape a value for HTML output.
     *
     * ENT_QUOTES is explicit rather than relying on the PHP 8.1 default, so
     * behaviour cannot silently weaken on an older target.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Build an application URL: url('/student/login.php'). */
    function url(string $path = '/'): string
    {
        return Url::to($path);
    }
}

if (!function_exists('asset')) {
    /** Build an asset URL: asset('css/app.css'). */
    function asset(string $path): string
    {
        return Url::asset($path);
    }
}

if (!function_exists('csrf_token')) {
    /** The current session's CSRF token. */
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * The hidden input every state-changing form needs.
     *
     * Rendering it through a helper rather than by hand means the field name
     * and the token source cannot drift apart across the twenty-odd forms in
     * this application.
     */
    function csrf_field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            e(Csrf::FIELD),
            e(Csrf::token())
        );
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('percentage')) {
    /**
     * Score as a whole-number percentage.
     * Previously recomputed inline in six different files.
     */
    function percentage(int $score, int $total): int
    {
        return GradingService::percentage($score, $total);
    }
}

if (!function_exists('is_pass')) {
    /** Whether a percentage meets the configured pass mark. */
    function is_pass(int $percentage): bool
    {
        return GradingService::isPass($percentage);
    }
}

if (!function_exists('score_badge')) {
    /** CSS badge class for a percentage. */
    function score_badge(int $percentage): string
    {
        return is_pass($percentage) ? 'badge-success' : 'badge-danger';
    }
}

if (!function_exists('format_date')) {
    /** Short date, e.g. "Mar 19, 2026". */
    function format_date(?string $timestamp): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '-';
        }

        $time = strtotime($timestamp);

        return $time === false ? '-' : date('M j, Y', $time);
    }
}

if (!function_exists('format_datetime')) {
    /** Long date and time, e.g. "March 19, 2026 at 7:20 pm". */
    function format_datetime(?string $timestamp): string
    {
        if ($timestamp === null || $timestamp === '') {
            return '-';
        }

        $time = strtotime($timestamp);

        return $time === false ? '-' : date('F j, Y \a\t g:i a', $time);
    }
}

if (!function_exists('old')) {
    /**
     * Repopulate a form field after a failed submission.
     *
     * @param array<string,mixed> $old
     */
    function old(array $old, string $key, mixed $default = ''): string
    {
        return (string) ($old[$key] ?? $default);
    }
}

if (!function_exists('icon')) {
    /**
     * An icon from the application set: icon('trash', 'icon-lg').
     *
     * Decorative by default. Pass $label only when the icon is the control's
     * only description, as on an icon-only button.
     */
    function icon(string $name, string $class = '', ?string $label = null): string
    {
        return Icons::render($name, $class, $label);
    }
}

if (!function_exists('initials')) {
    /**
     * One or two letters standing in for a person in an avatar.
     *
     * Falls back to a neutral glyph rather than rendering an empty circle
     * when a name is blank.
     */
    function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = mb_substr($words[0], 0, 1);
        $last  = count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}

if (!function_exists('pluralise')) {
    /**
     * "1 question" / "12 questions", so counts read as sentences instead of
     * as "1 question(s)".
     */
    function pluralise(int $count, string $singular, ?string $plural = null): string
    {
        $word = $count === 1 ? $singular : ($plural ?? $singular . 's');

        return number_format($count) . ' ' . $word;
    }
}

if (!function_exists('query_url')) {
    /**
     * The current page with some query parameters changed.
     *
     * Sorting a filtered, paginated table must not silently drop the filter,
     * and paging must not reset the sort. Building every such link by hand is
     * how one of them ends up forgetting a parameter, so they are all built
     * here from the request that is already in flight. A null value removes
     * the parameter rather than sending it empty.
     *
     * @param array<string,int|string|null> $changes
     */
    function query_url(string $path, array $changes): string
    {
        $query = array_merge($_GET, $changes);

        $query = array_filter(
            $query,
            static fn ($value) => $value !== null && $value !== '' && is_scalar($value)
        );

        return url($path) . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
