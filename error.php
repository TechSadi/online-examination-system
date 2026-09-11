<?php

declare(strict_types=1);

/**
 * The page the web server falls back to.
 *
 * It is reached two ways: the .htaccess rewrite, for a URL that names no
 * file, and the container vhost's ErrorDocument directives, for the statuses
 * Apache raises itself - a denied path, a PHP process that died before it
 * could answer. Without it those render as Apache's own page, which is
 * unstyled and signs itself "Apache/2.4.x (Unix) Server at ..." at the foot.
 *
 * Both sources are server-controlled. EXAMHUB_STATUS is set by the rewrite
 * rule; Apache prefixes it with REDIRECT_ as it passes through the internal
 * redirect, so both spellings are read. REDIRECT_STATUS is Apache's own, and
 * is only populated on an internal redirect - a client cannot set either.
 *
 * The value is still checked against the statuses actually wired up, so an
 * unexpected one renders a 500 rather than being echoed back into the page.
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\ErrorPage;

/** Statuses this handler is registered for. */
$handled = [400, 403, 404, 405, 500, 503];

foreach (['REDIRECT_EXAMHUB_STATUS', 'EXAMHUB_STATUS', 'REDIRECT_STATUS'] as $key) {
    $status = (int) ($_SERVER[$key] ?? 0);

    if (in_array($status, $handled, true)) {
        ErrorPage::send($status);
    }
}

ErrorPage::send(500);
