<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Repositories\ResultRepository;

/**
 * Student dashboard: headline statistics and recent activity.
 */
final class DashboardController
{
    private const RECENT_LIMIT = 5;

    public function __construct(
        private readonly ExamRepository $exams = new ExamRepository(),
        private readonly ResultRepository $results = new ResultRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireStudent();

        $studentId  = Auth::studentId();
        $totalExams = $this->exams->countAll();
        $attempted  = $this->results->countForStudent($studentId);
        $name       = Auth::studentName();

        View::render('student/dashboard', [
            'pageTitle'     => 'Student Dashboard',
            'role'          => 'student',
            'firstName'     => explode(' ', trim($name))[0] ?: $name,
            'totalExams'    => $totalExams,
            'attempted'     => $attempted,
            'remaining'     => max(0, $totalExams - $attempted),
            'avgScore'      => $this->results->averagePercentForStudent($studentId),
            'recentResults' => $this->results->historyForStudent($studentId, self::RECENT_LIMIT),
        ]);
    }
}
