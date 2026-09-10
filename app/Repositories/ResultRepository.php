<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching the results table.
 *
 * Every student-facing lookup takes the student id as a parameter and filters
 * on it, so a result belonging to another student cannot be returned by
 * changing an id in the URL.
 */
final class ResultRepository
{
    /** @return array<string,mixed>|null */
    public function findForStudentAndExam(int $studentId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT r.result_id, r.student_id, r.exam_id, r.score, r.total, r.date_taken,
                    e.title, e.description, e.duration,
                    a.status, a.started_at, a.submitted_at
               FROM results r
               JOIN exams e ON e.exam_id = r.exam_id
          LEFT JOIN exam_attempts a ON a.attempt_id = r.attempt_id
              WHERE r.student_id = ? AND r.exam_id = ?',
            [$studentId, $examId]
        );
    }

    /**
     * Record a completed attempt.
     *
     * A plain INSERT, not INSERT IGNORE. The gate against a duplicate
     * submission is now ExamService closing the attempt row, and this call
     * runs inside that same transaction; a unique-key violation here means
     * the two disagree, which must roll the whole submission back loudly
     * rather than leave a closed attempt with no result behind it.
     */
    public function record(int $attemptId, int $studentId, int $examId, int $score, int $total): void
    {
        Database::execute(
            'INSERT INTO results (attempt_id, student_id, exam_id, score, total) VALUES (?, ?, ?, ?, ?)',
            [$attemptId, $studentId, $examId, $score, $total]
        );
    }

    /** @return list<array<string,mixed>> */
    public function historyForStudent(int $studentId, ?int $limit = null): array
    {
        $sql = 'SELECT r.result_id, r.exam_id, r.score, r.total, r.date_taken, e.title, e.duration
                  FROM results r
                  JOIN exams e ON e.exam_id = r.exam_id
                 WHERE r.student_id = ?
              ORDER BY r.date_taken DESC';

        if ($limit !== null) {
            // Interpolated only after an integer cast; never a request value.
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        return Database::fetchAll($sql, [$studentId]);
    }

    public function countForStudent(int $studentId): int
    {
        return Database::count('SELECT COUNT(*) FROM results WHERE student_id = ?', [$studentId]);
    }

    public function averagePercentForStudent(int $studentId): int
    {
        $average = Database::fetchColumn(
            'SELECT AVG((score / total) * 100) FROM results WHERE student_id = ? AND total > 0',
            [$studentId]
        );

        return (int) round((float) ($average ?? 0));
    }

    public function countAll(): int
    {
        return Database::count('SELECT COUNT(*) FROM results');
    }

    /**
     * Admin listing, optionally filtered by student and/or exam.
     *
     * The WHERE clause is assembled from a fixed set of literal fragments;
     * only bound values come from the request.
     *
     * @return list<array<string,mixed>>
     */
    public function filtered(int $studentId = 0, int $examId = 0): array
    {
        $conditions = [];
        $params     = [];

        if ($studentId > 0) {
            $conditions[] = 'r.student_id = ?';
            $params[]     = $studentId;
        }

        if ($examId > 0) {
            $conditions[] = 'r.exam_id = ?';
            $params[]     = $examId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return Database::fetchAll(
            'SELECT r.result_id, r.score, r.total, r.date_taken,
                    s.student_id, s.name AS student_name, s.email,
                    e.exam_id, e.title AS exam_title
               FROM results r
               JOIN students s ON s.student_id = r.student_id
               JOIN exams    e ON e.exam_id    = r.exam_id'
            . $where .
            ' ORDER BY r.date_taken DESC',
            $params
        );
    }

    /** Most recent attempts across all students, for the admin dashboard. */
    public function recent(int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT r.score, r.total, r.date_taken,
                    s.name  AS student_name,
                    e.title AS exam_title
               FROM results r
               JOIN students s ON s.student_id = r.student_id
               JOIN exams    e ON e.exam_id    = r.exam_id
           ORDER BY r.date_taken DESC
              LIMIT ' . max(1, $limit)
        );
    }
}
