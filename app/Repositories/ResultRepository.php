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
                    e.title, e.description, e.duration
               FROM results r
               JOIN exams e ON e.exam_id = r.exam_id
              WHERE r.student_id = ? AND r.exam_id = ?',
            [$studentId, $examId]
        );
    }

    public function hasAttempted(int $studentId, int $examId): bool
    {
        return Database::fetch(
            'SELECT result_id FROM results WHERE student_id = ? AND exam_id = ?',
            [$studentId, $examId]
        ) !== null;
    }

    /**
     * Record a completed attempt.
     *
     * INSERT IGNORE relies on the unique (student_id, exam_id) key to make a
     * duplicate submission a no-op even when two requests race.
     *
     * @return bool true when this call created the row
     */
    public function record(int $studentId, int $examId, int $score, int $total): bool
    {
        return Database::execute(
            'INSERT IGNORE INTO results (student_id, exam_id, score, total) VALUES (?, ?, ?, ?)',
            [$studentId, $examId, $score, $total]
        ) > 0;
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
