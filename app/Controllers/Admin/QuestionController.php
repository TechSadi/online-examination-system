<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\ExamRepository;
use App\Repositories\QuestionRepository;
use App\Validation\Validator;

/**
 * Question CRUD for one exam.
 *
 * Every question lookup is scoped to the exam in the request, so a question
 * id belonging to a different exam cannot be edited or deleted through it.
 */
final class QuestionController
{
    /** Matches the option columns, which are VARCHAR(255). */
    private const OPTION_MAX = 255;

    public function __construct(
        private readonly ExamRepository $exams = new ExamRepository(),
        private readonly QuestionRepository $questions = new QuestionRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();

        $exam = $this->requireExam();

        if (Request::isPost()) {
            Request::post('action') === 'delete'
                ? $this->delete($exam)
                : $this->save($exam);
        }

        $this->render($exam);
    }

    /** @param array<string,mixed> $exam */
    private function save(array $exam): void
    {
        $examId     = (int) $exam['exam_id'];
        $questionId = Request::id('question_id');

        $input = [
            'question_text'  => Request::post('question_text'),
            'option1'        => Request::post('option1'),
            'option2'        => Request::post('option2'),
            'option3'        => Request::post('option3'),
            'option4'        => Request::post('option4'),
            'correct_answer' => Request::post('correct_answer'),
        ];

        $validator = Validator::make($input)
            ->required('question_text', 'Question text')
            ->maxLength('question_text', 5000, 'Question text');

        foreach (['A' => 'option1', 'B' => 'option2', 'C' => 'option3', 'D' => 'option4'] as $letter => $field) {
            $validator
                ->required($field, 'Option ' . $letter)
                ->maxLength($field, self::OPTION_MAX, 'Option ' . $letter);
        }

        $validator->inList('correct_answer', [1, 2, 3, 4], 'Select a correct answer (A-D).');

        if ($validator->fails()) {
            $this->render($exam, $validator->errors(), $input, $questionId);

            return;
        }

        $data = [
            'text'    => $input['question_text'],
            'option1' => $input['option1'],
            'option2' => $input['option2'],
            'option3' => $input['option3'],
            'option4' => $input['option4'],
            'correct' => (int) $input['correct_answer'],
        ];

        if ($questionId > 0) {
            if ($this->questions->findInExam($questionId, $examId) === null) {
                Response::redirectWithError(
                    '/admin/questions.php?exam_id=' . $examId,
                    'That question could not be found in this exam.'
                );
            }

            $this->questions->update($questionId, $examId, $data);
            $message = 'Question updated successfully.';
        } else {
            $this->questions->create($examId, $data);
            $message = 'Question added successfully.';
        }

        Response::redirectWithSuccess('/admin/questions.php?exam_id=' . $examId, $message);
    }

    /** @param array<string,mixed> $exam */
    private function delete(array $exam): void
    {
        $examId     = (int) $exam['exam_id'];
        $questionId = Request::id('question_id');

        if ($questionId === 0 || !$this->questions->delete($questionId, $examId)) {
            Response::redirectWithError(
                '/admin/questions.php?exam_id=' . $examId,
                'That question could not be found in this exam.'
            );
        }

        Response::redirectWithSuccess('/admin/questions.php?exam_id=' . $examId, 'Question deleted.');
    }

    /**
     * @param array<string,mixed> $exam
     * @param list<string>        $errors
     * @param array<string,mixed> $old
     */
    private function render(array $exam, array $errors = [], array $old = [], int $questionId = 0): void
    {
        $examId = (int) $exam['exam_id'];

        // Editing is requested with ?edit_q=; after a failed POST the id comes
        // from the submitted form instead.
        $editId       = $questionId > 0 ? $questionId : Request::id('edit_q');
        $editQuestion = $editId > 0 ? $this->questions->findInExam($editId, $examId) : null;

        if ($editQuestion === null && $questionId > 0) {
            $editQuestion = ['question_id' => $questionId];
        }

        View::render('admin/questions', [
            'pageTitle'    => 'Manage Questions',
            'role'         => 'admin',
            'exam'         => $exam,
            'questions'    => $this->questions->forExam($examId),
            'editQuestion' => $editQuestion,
            'errors'       => $errors,
            'old'          => $old,
        ], 'layouts/admin');
    }

    /** @return array<string,mixed> */
    private function requireExam(): array
    {
        $examId = Request::id('exam_id');
        $exam   = $examId > 0 ? $this->exams->find($examId) : null;

        if ($exam === null) {
            Response::redirect('/admin/exams.php');
        }

        return $exam;
    }
}
