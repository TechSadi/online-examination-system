<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Template rendering with layouts.
 *
 * Pages used to open with a hand-rolled preamble and close by including
 * footer.php, so the document structure was spread across every file and a
 * page that returned early emitted a half-written document. A view now
 * renders into a buffer and the layout wraps it once.
 */
final class View
{
    /**
     * Render a template inside a layout.
     *
     * @param string              $template e.g. "student/dashboard"
     * @param array<string,mixed> $data     variables extracted into the template
     * @param string              $layout   e.g. "layouts/app"
     */
    public static function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        $data['flashes']    = Flash::take();
        $data['errors']     = $data['errors'] ?? Flash::takeErrors();
        $data['old']        = $data['old'] ?? Flash::takeOld();
        $data['pageTitle']  = $data['pageTitle'] ?? Config::get('app.name', 'ExamHub');
        $data['role']       = $data['role'] ?? 'public';

        $content = self::capture($template, $data);

        echo self::capture($layout, $data + ['content' => $content]);
    }

    /** Render a template and return its output instead of echoing it. */
    public static function capture(string $template, array $data = []): string
    {
        $path = self::path($template);

        ob_start();
        try {
            (static function (string $__path, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__path;
            })($path, $data);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    /**
     * Render a partial straight to the output buffer.
     * Used from inside layouts and templates.
     *
     * @param array<string,mixed> $data
     */
    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    private static function path(string $template): string
    {
        // Template names are internal constants, never user input. The
        // allowlist keeps it that way and rules out traversal outright.
        if (preg_match('#^[A-Za-z0-9_/-]+$#', $template) !== 1) {
            throw new RuntimeException(sprintf('Invalid view name "%s".', $template));
        }

        $path     = __DIR__ . '/../Views/' . ltrim($template, '/') . '.php';

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('View "%s" not found.', $template));
        }

        return $path;
    }
}
