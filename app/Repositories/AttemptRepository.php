<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * All SQL touching exam_attempts and attempt_answers.
 *
 * An attempt is the server's own record of a student sitting an exam: when
 * it began, when it must end, and whether it has been handed in. Nothing the
 * browser sends can create, extend or re-open one.
 *
 * Times are stored and compared as UTC strings written by PHP. The database
 * clock is never consulted for a deadline, because the MySQL server's
 * timezone is not guaranteed to match the application's.
 */
final class AttemptRepository
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED   = 'submitted';
    public const STATUS_EXPIRED     = 'expired';

    /** @return array<string,mixed>|null */
    public function findForStudentAndExam(int $studentId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT attempt_id, student_id, exam_id, started_at, expires_at,
                    submitted_at, status, score, total
               FROM exam_attempts
              WHERE student_id = ? AND exam_id = ?',
            [$studentId, $examId]
        );
    }

    /**
     * Begin an attempt.
     *
     * The UNIQUE (student_id, exam_id) key is what actually prevents a second
     * attempt: two concurrent requests both passing an application-level
     * "has this student started?" check would still leave only one row here.
     * A duplicate is therefore an expected outcome, not an error, and is
     * reported by returning null so the caller can load the existing attempt.
     *
     * @return int|null the new attempt id, or null when one already existed
     */
    public function start(int $studentId, int $examId, string $startedAt, string $expiresAt): ?int
    {
        try {
            return Database::insert(
                'INSERT INTO exam_attempts (student_id, exam_id, started_at, expires_at, status)
                      VALUES (?, ?, ?, ?, ?)',
                [$studentId, $examId, $startedAt, $expiresAt, self::STATUS_IN_PROGRESS]
            );
        } catch (PDOException $e) {
            // 23000 is an integrity constraint violation: the unique key.
            if ($e->getCode() === '23000') {
                return null;
            }

            throw $e;
        }
    }

    /**
     * Close an attempt, but only if it is still open.
     *
     * The status predicate in the WHERE clause is the concurrency gate for
     * the whole submission path: of any number of simultaneous submissions
     * for one attempt, exactly one UPDATE matches a row. Everything else -
     * a double-clicked button, a refreshed POST, two tabs racing - sees
     * false here and is turned away without a second result being written.
     */
    public function close(int $attemptId, string $status, int $score, int $total, string $submittedAt): bool
    {
        return Database::execute(
            'UPDATE exam_attempts
                SET status = ?, score = ?, total = ?, submitted_at = ?
              WHERE attempt_id = ? AND status = ?',
            [$status, $score, $total, $submittedAt, $attemptId, self::STATUS_IN_PROGRESS]
        ) === 1;
    }

    /**
     * Record what was chosen for each question.
     *
     * @param list<array{question_id:int,selected:int|null,is_correct:bool}> $answers
     */
    public function saveAnswers(int $attemptId, array $answers): void
    {
        if ($answers === []) {
            return;
        }

        $sql    = 'INSERT INTO attempt_answers (attempt_id, question_id, selected, is_correct) VALUES ';
        $rows   = [];
        $params = [];

        foreach ($answers as $answer) {
            $rows[]   = '(?, ?, ?, ?)';
            $params[] = $attemptId;
            $params[] = $answer['question_id'];
            $params[] = $answer['selected'];
            $params[] = $answer['is_correct'] ? 1 : 0;
        }

        // Placeholders only; every value is bound.
        Database::execute($sql . implode(', ', $rows), $params);
    }

    /** @return list<array<string,mixed>> */
    public function answersFor(int $attemptId): array
    {
        return Database::fetchAll(
            'SELECT question_id, selected, is_correct FROM attempt_answers WHERE attempt_id = ?',
            [$attemptId]
        );
    }
}
