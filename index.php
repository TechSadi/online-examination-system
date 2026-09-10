<?php

declare(strict_types=1);

/**
 * Public home page.
 */

require_once __DIR__ . '/app/bootstrap.php';

(new App\Controllers\HomeController())->index();
