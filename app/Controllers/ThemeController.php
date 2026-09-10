<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Theme;

/**
 * Stores the reader's light/dark preference.
 *
 * This is the path taken when JavaScript is unavailable, or when it has not
 * loaded yet. With it, app.js writes the same cookie and repaints in place, so
 * this endpoint is never reached - the form it belongs to is the fallback, not
 * the primary route.
 *
 * POST only, and CSRF-protected by the global guard like every other
 * state-changing request. There is nothing here worth attacking, but a rule
 * that has exceptions is a rule nobody can rely on.
 */
final class ThemeController
{
    public function store(): void
    {
        if (!Request::isPost()) {
            Response::redirect('/');
        }

        Theme::remember(Request::post('theme'));

        // Back to wherever the control was used. The path is checked rather
        // than trusted: an unchecked value here would turn a preference
        // toggle into an open redirect.
        Response::redirectToLocalPath(Request::post('redirect'), '/');
    }
}
