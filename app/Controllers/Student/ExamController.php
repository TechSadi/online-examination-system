<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\ResultRepository;
use App\Services\GradingService;

/**
 * Exam discovery, the exam interface, and submission.
 */
final class ExamController
{
    public function __construct(
        private readonly ExamRepository $exams = new ExamRepository(),
        private readonly QuestionRepository $questions = new QuestionRepository(),
        private readonly ResultRepository $results = new ResultRepository(),
        private readonly GradingService $grading = new GradingService()
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

    /** Render the exam interface. */
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

        // One attempt per exam: send a student who has already finished
        // straight to their result rather than letting them start again.
        if ($this->results->hasAttempted($studentId, $examId)) {
            Response::redirect('/student/result.php?exam_id=' . $examId);
        }

        $questions = $this->questions->forExamWithoutAnswers($examId);

        if ($questions === []) {
            Response::redirectWithError('/student/exams.php', 'That exam has no questions yet.');
        }

        View::render('student/take_exam', [
            'pageTitle'     => 'Taking: ' . $exam['title'],
            'role'          => 'student',
            'includeExamJS' => true,
            'exam'          => $exam,
            'questions'     => $questions,
        ]);
    }

    /**
     * Grade a submission and store the result.
     *
     * The score is computed from the answer key in the database; nothing the
     * browser sends is trusted beyond which option was chosen per question.
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

        if ($this->results->hasAttempted($studentId, $examId)) {
            Response::redirect('/student/result.php?exam_id=' . $examId);
        }

        $graded = $this->grading->grade($examId, Request::all());

        if ($graded['total'] === 0) {
            Response::redirectWithError('/student/exams.php', 'That exam has no questions yet.');
        }

        $this->results->record($studentId, $examId, $graded['score'], $graded['total']);

        Response::redirect('/student/result.php?exam_id=' . $examId);
    }
}
