<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Validation\Validator;

/**
 * Exam CRUD.
 *
 * Create and update share one validation routine and one template, so the two
 * cannot drift apart - previously add_exam.php enforced a maximum duration
 * that edit_exam.php did not.
 */
final class ExamController
{
    public function __construct(
        private readonly ExamRepository $exams = new ExamRepository()
    ) {
    }

    /** List exams, and handle the delete action posted from that list. */
    public function index(): void
    {
        Auth::requireAdmin();

        if (Request::isPost() && Request::post('action') === 'delete') {
            $this->delete();
        }

        View::render('admin/exams', [
            'pageTitle' => 'Manage Exams',
            'role'      => 'admin',
            'exams'     => $this->exams->allWithCounts(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->renderForm(null);
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $input     = $this->input();
        $validator = $this->validate($input);

        if ($validator->fails()) {
            $this->renderForm(null, $validator->errors(), $input);

            return;
        }

        $examId = $this->exams->create($input['title'], $input['description'], (int) $input['duration']);

        Response::redirectWithSuccess(
            '/admin/questions.php?exam_id=' . $examId,
            'Exam created. Now add its questions.'
        );
    }

    public function edit(): void
    {
        Auth::requireAdmin();

        $exam = $this->requireExam();

        $this->renderForm($exam);
    }

    public function update(): void
    {
        Auth::requireAdmin();

        $exam      = $this->requireExam();
        $input     = $this->input();
        $validator = $this->validate($input);

        if ($validator->fails()) {
            $this->renderForm($exam, $validator->errors(), $input);

            return;
        }

        $this->exams->update(
            (int) $exam['exam_id'],
            $input['title'],
            $input['description'],
            (int) $input['duration']
        );

        Response::redirectWithSuccess('/admin/exams.php', 'Exam saved successfully.');
    }

    /**
     * Delete an exam. Cascades to its questions and recorded results, so the
     * confirmation in the list view states how many results will be lost.
     */
    private function delete(): void
    {
        $examId = Request::id('exam_id');

        if ($examId === 0 || !$this->exams->exists($examId)) {
            Response::redirectWithError('/admin/exams.php', 'That exam could not be found.');
        }

        $this->exams->delete($examId);

        Response::redirectWithSuccess('/admin/exams.php', 'Exam deleted successfully.');
    }

    /** @return array{title:string,description:string,duration:string} */
    private function input(): array
    {
        return [
            'title'       => Request::post('title'),
            'description' => Request::post('description'),
            'duration'    => Request::post('duration'),
        ];
    }

    /** @param array<string,mixed> $input */
    private function validate(array $input): Validator
    {
        return Validator::make($input)
            ->required('title', 'Exam title')
            ->maxLength('title', 200, 'Exam title')
            ->integerBetween('duration', 1, (int) Config::get('exam.max_duration', 300), 'Duration');
    }

    /** Load the exam named by the request, or bail out to the list. */
    private function requireExam(): array
    {
        $examId = Request::id('id') ?: Request::id('exam_id');
        $exam   = $examId > 0 ? $this->exams->find($examId) : null;

        if ($exam === null) {
            Response::redirect('/admin/exams.php');
        }

        return $exam;
    }

    /**
     * @param array<string,mixed>|null $exam
     * @param list<string>             $errors
     * @param array<string,mixed>      $old
     */
    private function renderForm(?array $exam, array $errors = [], array $old = []): void
    {
        View::render('admin/exam_form', [
            'pageTitle'   => $exam === null ? 'Add New Exam' : 'Edit Exam',
            'role'        => 'admin',
            'exam'        => $exam,
            'errors'      => $errors,
            'old'         => $old,
            'maxDuration' => (int) Config::get('exam.max_duration', 300),
        ], 'layouts/admin');
    }
}
