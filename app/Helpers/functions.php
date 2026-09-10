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
    /** Build an asset URL: asset('css/style.css'). */
    function asset(string $path): string
    {
        return Url::asset($path);
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
