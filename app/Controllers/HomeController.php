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
            'pageTitle' => 'Online Examination System',
            'role'      => 'public',
            // The one page a search engine will actually show, so it
            // gets its own sentence rather than the site-wide default.
            'metaDescription' => 'Sit timed, automatically graded multiple-choice exams online. '
                . 'ExamHub keeps the clock on the server, marks each paper the moment it is '
                . 'handed in, and gives educators a place to set and manage them.',
        ]);
    }
}
