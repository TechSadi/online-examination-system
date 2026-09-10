<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching the questions table.
 *
 * Every lookup that can be scoped to an exam is scoped to an exam, so a
 * question id from one exam can never be edited or deleted through another.
 */
final class QuestionRepository
{
    /** Question rows as shown to a student: no correct_answer column. */
    public function forExamWithoutAnswers(int $examId): array
    {
        return Database::fetchAll(
            'SELECT question_id, exam_id, question_text, option1, option2, option3, option4
               FROM questions
              WHERE exam_id = ?
           ORDER BY question_id',
            [$examId]
        );
    }

    /** Full rows including the answer key; admin and grading only. */
    public function forExam(int $examId): array
    {
        return Database::fetchAll(
            'SELECT question_id, exam_id, question_text, option1, option2, option3, option4, correct_answer
               FROM questions
              WHERE exam_id = ?
           ORDER BY question_id',
            [$examId]
        );
    }

    /** The answer key alone, used by the grader. */
    public function answerKey(int $examId): array
    {
        return Database::fetchAll(
            'SELECT question_id, correct_answer FROM questions WHERE exam_id = ? ORDER BY question_id',
            [$examId]
        );
    }

    /** @return array<string,mixed>|null */
    public function findInExam(int $questionId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT question_id, exam_id, question_text, option1, option2, option3, option4, correct_answer
               FROM questions
              WHERE question_id = ? AND exam_id = ?',
            [$questionId, $examId]
        );
    }

    public function countForExam(int $examId): int
    {
        return Database::count('SELECT COUNT(*) FROM questions WHERE exam_id = ?', [$examId]);
    }

    public function countAll(): int
    {
        return Database::count('SELECT COUNT(*) FROM questions');
    }

    /** @param array{text:string,option1:string,option2:string,option3:string,option4:string,correct:int} $data */
    public function create(int $examId, array $data): int
    {
        return Database::insert(
            'INSERT INTO questions
                 (exam_id, question_text, option1, option2, option3, option4, correct_answer)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $examId,
                $data['text'],
                $data['option1'],
                $data['option2'],
                $data['option3'],
                $data['option4'],
                $data['correct'],
            ]
        );
    }

    /** @param array{text:string,option1:string,option2:string,option3:string,option4:string,correct:int} $data */
    public function update(int $questionId, int $examId, array $data): bool
    {
        return Database::execute(
            'UPDATE questions
                SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_answer = ?
              WHERE question_id = ? AND exam_id = ?',
            [
                $data['text'],
                $data['option1'],
                $data['option2'],
                $data['option3'],
                $data['option4'],
                $data['correct'],
                $questionId,
                $examId,
            ]
        ) >= 0;
    }

    public function delete(int $questionId, int $examId): bool
    {
        return Database::execute(
            'DELETE FROM questions WHERE question_id = ? AND exam_id = ?',
            [$questionId, $examId]
        ) > 0;
    }
}
