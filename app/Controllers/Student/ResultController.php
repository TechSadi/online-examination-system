<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ResultRepository;
use App\Services\GradingService;

/**
 * Result viewing.
 *
 * Both actions scope their query to the signed-in student, so changing the
 * exam_id in the URL can only ever return that student own result.
 */
final class ResultController
{
    public function __construct(
        private readonly ResultRepository $results = new ResultRepository()
    ) {
    }

    public function show(): void
    {
        Auth::requireStudent();

        $examId = Request::id('exam_id');

        if ($examId === 0) {
            Response::redirect('/student/results.php');
        }

        $result = $this->results->findForStudentAndExam(Auth::studentId(), $examId);

        if ($result === null) {
            Response::redirect('/student/exams.php');
        }

        $percentage = GradingService::percentage((int) $result['score'], (int) $result['total']);

        View::render('student/result', [
            'pageTitle'  => 'Result – ' . $result['title'],
            'role'       => 'student',
            'result'     => $result,
            'percentage' => $percentage,
            'passed'     => GradingService::isPass($percentage),
            'passMark'   => GradingService::passMark(),
        ]);
    }

    public function index(): void
    {
        Auth::requireStudent();

        $all = $this->results->historyForStudent(Auth::studentId());

        // The filter narrows a list the student already has in hand, so it is
        // applied here rather than as a second query. An unrecognised value
        // falls back to showing everything: a mistyped URL should not look
        // like an empty result history.
        $filter = Request::query('filter');

        if (!in_array($filter, ['passed', 'failed'], true)) {
            $filter = 'all';
        }

        $visible = $filter === 'all' ? $all : array_values(array_filter(
            $all,
            static function (array $row) use ($filter): bool {
                $passed = GradingService::isPass(
                    GradingService::percentage((int) $row['score'], (int) $row['total'])
                );

                return $filter === 'passed' ? $passed : !$passed;
            }
        ));

        View::render('student/results', [
            'pageTitle'  => 'My Results',
            'role'       => 'student',
            'results'    => $visible,
            'allResults' => $all,
            'filter'     => $filter,
            'passMark'   => GradingService::passMark(),
        ]);
    }
}
