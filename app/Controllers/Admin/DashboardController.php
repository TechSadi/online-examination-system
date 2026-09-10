<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\AdminRepository;
use App\Repositories\ExamRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\ResultRepository;
use App\Repositories\StudentRepository;

/**
 * Admin dashboard: system-wide counters and recent activity.
 */
final class DashboardController
{
    private const RECENT_LIMIT = 8;

    public function __construct(
        private readonly StudentRepository $students = new StudentRepository(),
        private readonly ExamRepository $exams = new ExamRepository(),
        private readonly QuestionRepository $questions = new QuestionRepository(),
        private readonly ResultRepository $results = new ResultRepository(),
        private readonly AdminRepository $admins = new AdminRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();

        View::render('admin/dashboard', [
            'pageTitle'      => 'Admin Dashboard',
            'role'           => 'admin',
            'adminName'      => Auth::adminName(),
            'stats'          => [
                'students'  => $this->students->countAll(),
                'exams'     => $this->exams->countAll(),
                'questions' => $this->questions->countAll(),
                'attempts'  => $this->results->countAll(),
                'admins'    => $this->admins->countAll(),
            ],
            'recentAttempts' => $this->results->recent(self::RECENT_LIMIT),
        ], 'layouts/admin');
    }
}
