<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Repositories\QuestionRepository;
use App\Services\ExamService;

/**
 * Exam discovery, the exam interface, and submission.
 *
 * The controller decides nothing about the exam itself. It resolves who is
 * asking and which exam they named, hands both to ExamService, and turns the
 * outcome into a page or a redirect. Every rule that could be cheated -
 * eligibility, the deadline, the score, whether a submission is a duplicate -
 * is settled in the service, against the database.
 */
final class ExamController
{
    public function __construct(
        private readonly ExamRepository $exams = new ExamRepository(),
        private readonly QuestionRepository $questions = new QuestionRepository(),
        private readonly ExamService $engine = new ExamService()
    ) {
    }

    /** List every exam, flagged with whether this student has completed it. */
    public function index(): void
    {
        Auth::requireStudent();

        View::render('student/exams', [
            'pageTitle' => 'Available Exams',
            'role'      => 'student',
            'exams'     => $this->exams->allForStudent(Auth::studentId()),
        ]);
    }

    /**
     * Render the exam interface.
     *
     * Opening the page is what starts the attempt, and therefore the clock.
     * Reloading resumes the same attempt with the time that genuinely
     * remains, rather than issuing a fresh countdown.
     */
    public function take(): void
    {
        Auth::requireStudent();

        $studentId = Auth::studentId();
        $examId    = Request::id('exam_id');

        if ($examId === 0) {
            Response::redirect('/student/exams.php');
        }

        $exam = $this->exams->find($examId);

        if ($exam === null) {
            Response::redirectWithError('/student/exams.php', 'That exam could not be found.');
        }

        $questions = $this->questions->forExamWithoutAnswers($examId);

        if ($questions === []) {
            Response::redirectWithError('/student/exams.php', 'That exam has no questions yet.');
        }

        $session = $this->engine->open($studentId, $exam);

        // Already handed in, or the deadline passed while they were away.
        if ($session['state'] === ExamService::COMPLETED) {
            Response::redirect('/student/result.php?exam_id=' . $examId);
        }

        View::render('student/take_exam', [
            'pageTitle'        => 'Taking: ' . $exam['title'],
            'role'             => 'student',
            'includeExamJS'    => true,
            'exam'             => $exam,
            'questions'        => $questions,
            'secondsRemaining' => $session['seconds_remaining'],
            'resumed'          => $session['state'] === ExamService::RESUMED,
        ]);
    }

    /**
     * Grade a submission and store the result.
     *
     * Nothing here inspects the submitted answers; it only reports what the
     * engine decided. A POST with no attempt behind it, one that arrives
     * after the deadline, and a second POST for an attempt already closed are
     * all distinct outcomes, and none of them can produce a score the student
     * chose.
     */
    public function submit(): void
    {
        Auth::requireStudent();

        if (!Request::isPost()) {
            Response::redirect('/student/exams.php');
        }

        $studentId = Auth::studentId();
        $examId    = Request::id('exam_id');

        if ($examId === 0 || !$this->exams->exists($examId)) {
            Response::redirect('/student/exams.php');
        }

        $outcome = $this->engine->submit($studentId, $examId, Request::all());

        match ($outcome) {
            ExamService::SUBMITTED => Response::redirectWithSuccess(
                '/student/result.php?exam_id=' . $examId,
                'Exam submitted. Here is how you did.'
            ),
            ExamService::EXPIRED => Response::redirectWithError(
                '/student/result.php?exam_id=' . $examId,
                'Your time had already run out, so this attempt was recorded as expired.'
            ),
            // A double-click, a refreshed POST, or a second tab. The first
            // submission stands; this one simply shows its result.
            ExamService::DUPLICATE => Response::redirect('/student/result.php?exam_id=' . $examId),
            ExamService::NO_ATTEMPT => Response::redirectWithError(
                '/student/exams.php',
                'You have not started that exam, so there was nothing to submit.'
            ),
            default => Response::redirectWithError(
                '/student/exams.php',
                'That exam has no questions yet.'
            ),
        };
    }
}
