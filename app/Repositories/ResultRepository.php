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
     * The columns the results list may be ordered by.
     *
     * A percentage is sorted on the computed ratio rather than on `score`,
     * because 8/10 beats 9/20 and ordering by the raw score would say
     * otherwise.
     *
     * @return array<string,string>
     */
    public static function sortableColumns(): array
    {
        return [
            'student'    => 's.name',
            'exam'       => 'e.title',
            'percentage' => '(r.score / NULLIF(r.total, 0))',
            'date_taken' => 'r.date_taken',
        ];
    }

    /**
     * One page of the admin results listing.
     *
     * The WHERE clause is assembled from a fixed set of literal fragments and
     * $orderBy comes from Sorter's allowlist; only bound values come from the
     * request. Limit and offset are interpolated after an integer cast, since
     * a native prepared statement will not accept a string-bound LIMIT.
     *
     * @return list<array<string,mixed>>
     */
    public function paginateFiltered(
        int $studentId,
        int $examId,
        string $search,
        string $orderBy,
        int $limit,
        int $offset
    ): array {
        [$where, $params] = self::filterClause($studentId, $examId, $search);

        return Database::fetchAll(
            'SELECT r.result_id, r.score, r.total, r.date_taken,
                    s.student_id, s.name AS student_name, s.email,
                    e.exam_id, e.title AS exam_title
               FROM results r
               JOIN students s ON s.student_id = r.student_id
               JOIN exams    e ON e.exam_id    = r.exam_id'
            . $where
            . ' ORDER BY ' . $orderBy
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public function countFiltered(int $studentId, int $examId, string $search): int
    {
        [$where, $params] = self::filterClause($studentId, $examId, $search);

        return Database::count(
            'SELECT COUNT(*)
               FROM results r
               JOIN students s ON s.student_id = r.student_id
               JOIN exams    e ON e.exam_id    = r.exam_id' . $where,
            $params
        );
    }

    /**
     * @return array{0:string,1:list<mixed>}
     */
    private static function filterClause(int $studentId, int $examId, string $search): array
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

        if ($search !== '') {
            $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

            $conditions[] = '(s.name LIKE ? OR s.email LIKE ? OR e.title LIKE ?)';
            array_push($params, $term, $term, $term);
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $params,
        ];
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
