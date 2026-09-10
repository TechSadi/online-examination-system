<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * The application icon set.
 *
 * Every icon in the interface used to be an emoji written into the markup
 * (&#128221; for an exam, &#9999; for edit). Emoji are drawn by the operating
 * system, so the same page looked different on Windows, macOS and Android;
 * they ignore currentColor, so they could not take on a button's colour or
 * dim with disabled text; and several of them are announced by screen
 * readers as their unicode name in the middle of a sentence.
 *
 * These are line icons on a 24x24 grid, stroked with currentColor. They are
 * emitted once per document as an inline <symbol> sprite and referenced with
 * <use>, so twenty icons on a page cost one copy of each path and no extra
 * request.
 *
 * Only the icons this application actually uses are defined. An unknown name
 * throws rather than rendering an empty box, so a typo is caught on the page
 * that has it instead of shipping a hole in the UI.
 */
final class Icons
{
    /**
     * name => inner SVG markup, drawn on a 24x24 viewBox.
     *
     * @var array<string,string>
     */
    private const PATHS = [
        // Navigation
        'dashboard'   => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'exams'       => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
        'results'     => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
        'students'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'admins'      => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'menu'        => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'user'        => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'logo'        => '<path d="M22 10v6"/><path d="m2 10 10-5 10 5-10 5z"/><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"/>',

        // Actions
        'plus'        => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'edit'        => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
        'trash'       => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
        'search'      => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'filter'      => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'save'        => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'eye'         => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'flag'        => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        'reset'       => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',

        // Direction
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
        'arrow-left'    => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'arrow-right'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-up'      => '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
        'arrow-down'    => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
        'sort'          => '<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>',

        // Feedback
        'check'        => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'x'            => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'x-circle'     => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'warning'      => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'info'         => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'help'         => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'inbox'        => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',

        // Domain
        'clock'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'award'    => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11"/>',
        'question' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'checklist' => '<path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><line x1="13" y1="6" x2="21" y2="6"/><line x1="13" y1="12" x2="21" y2="12"/><line x1="13" y1="18" x2="21" y2="18"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'mail'     => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'zap'      => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'trend'    => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'book'     => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
    ];

    /**
     * An <svg> element referencing the sprite.
     *
     * Icons are decorative by default: they sit beside a text label that
     * already says what the control does, so they are hidden from assistive
     * technology to avoid announcing the same thing twice. Pass $label only
     * for an icon that carries meaning no nearby text repeats.
     */
    public static function render(string $name, string $class = '', ?string $label = null): string
    {
        if (!isset(self::PATHS[$name])) {
            throw new RuntimeException(sprintf('Unknown icon "%s".', $name));
        }

        $classes = trim('icon ' . $class);

        $accessibility = $label === null
            ? ' aria-hidden="true"'
            : ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

        return sprintf(
            '<svg class="%s"%s><use href="#i-%s"></use></svg>',
            htmlspecialchars($classes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $accessibility,
            $name
        );
    }

    /**
     * The sprite itself, emitted once at the top of <body>.
     *
     * Inline rather than an external file because an external sprite
     * referenced through <use> cannot inherit currentColor from the host
     * document in any current browser, which would cost the whole point of
     * the set.
     */
    public static function sprite(): string
    {
        $symbols = '';

        foreach (self::PATHS as $name => $path) {
            $symbols .= sprintf('<symbol id="i-%s" viewBox="0 0 24 24">%s</symbol>', $name, $path);
        }

        return '<svg class="icon-sprite" aria-hidden="true" focusable="false">' . $symbols . '</svg>';
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::PATHS);
    }
}
