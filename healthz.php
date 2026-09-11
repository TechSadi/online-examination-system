<?php

declare(strict_types=1);

/**
 * Health check.
 *
 * Render polls this to decide whether an instance is fit to receive traffic
 * and whether a new deploy succeeded. It has to answer two different
 * questions honestly:
 *
 *   200  PHP is serving and the database answers a query.
 *   503  Something a request would need is not working.
 *
 * The database is included deliberately. A container that boots but cannot
 * reach its database serves an error on every page, and a check that only
 * proved PHP was running would call that healthy and let a broken deploy
 * replace a working one.
 *
 * What it must not do is leak. The failure body names no host, no user and no
 * driver message - only that the dependency is down. The detail goes to the
 * log, which is where an operator is looking anyway.
 *
 * Stateless: no session is started, so a probe every few seconds does not
 * leave the container accumulating session files for a caller with no
 * identity that never comes back.
 */

define('APP_STATELESS', true);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

$checks = ['app' => 'ok'];
$status = 200;

try {
    // Cheapest statement that proves a round trip actually completed: a
    // connection can look established and still fail on first use.
    Database::fetchColumn('SELECT 1');
    $checks['database'] = 'ok';
} catch (Throwable $e) {
    $checks['database'] = 'unavailable';
    $status = 503;

    error_log('Health check failed: ' . $e->getMessage());
}

if (!headers_sent()) {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    // Never let a proxy or a browser answer a later probe from cache; the
    // whole value of the check is that it reflects this moment.
    header('Cache-Control: no-store');
}

echo json_encode([
    'status'  => $status === 200 ? 'ok' : 'degraded',
    'checks'  => $checks,
    'env'     => (string) Config::get('app.env', 'local'),
    'time'    => gmdate('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
