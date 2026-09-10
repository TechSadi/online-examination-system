<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

/**
 * Public landing page.
 */
final class HomeController
{
    public function index(): void
    {
        View::render('home', [
            'pageTitle' => 'Welcome',
            'role'      => 'public',
        ]);
    }
}
