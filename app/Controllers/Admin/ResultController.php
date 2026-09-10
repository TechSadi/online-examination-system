<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Repositories\ResultRepository;
use App\Repositories\StudentRepository;

/**
 * System-wide results listing, filterable by student and exam.
 */
final class ResultController
{
    public function __construct(
        private readonly ResultRepository $results = new ResultRepository(),
        private readonly StudentRepository $students = new StudentRepository(),
        private readonly ExamRepository $exams = new ExamRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();

        // A filter that is present but malformed is refused rather than
        // treated as "no filter": silently showing every result for a request
        // that asked for one student's is the kind of quiet substitution that
        // hides a bug, and here it would widen what is on screen.
        if (Request::hasInvalidId('student_id') || Request::hasInvalidId('exam_id')) {
            Response::redirectWithError('/admin/results.php', 'That filter was not valid.');
        }

        $studentId = Request::id('student_id');
        $examId    = Request::id('exam_id');

        View::render('admin/results', [
            'pageTitle' => 'View Results',
            'role'      => 'admin',
            'results'   => $this->results->filtered($studentId, $examId),
            'students'  => $this->students->nameOptions(),
            'exams'     => $this->exams->titleOptions(),
            'studentId' => $studentId,
            'examId'    => $examId,
        ], 'layouts/admin');
    }
}
