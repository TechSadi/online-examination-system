<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching the exams table.
 */
final class ExamRepository
{
    /** @return array<string,mixed>|null */
    public function find(int $examId): ?array
    {
        return Database::fetch(
            'SELECT exam_id, title, description, duration, created_at FROM exams WHERE exam_id = ?',
            [$examId]
        );
    }

    public function exists(int $examId): bool
    {
        return $this->find($examId) !== null;
    }

    public function countAll(): int
    {
        return Database::count('SELECT COUNT(*) FROM exams');
    }

    /**
     * Exams with question and attempt counts, for the admin list.
     *
     * @return list<array<string,mixed>>
     */
    public function allWithCounts(): array
    {
        return Database::fetchAll(
            'SELECT e.exam_id,
                    e.title,
                    e.description,
                    e.duration,
                    e.created_at,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) AS question_count,
                    (SELECT COUNT(*) FROM results   r WHERE r.exam_id = e.exam_id) AS attempt_count
               FROM exams e
           ORDER BY e.created_at DESC'
        );
    }

    /**
     * Exams annotated with whether this student has already completed each.
     *
     * @return list<array<string,mixed>>
     */
    public function allForStudent(int $studentId): array
    {
        return Database::fetchAll(
            'SELECT e.exam_id,
                    e.title,
                    e.description,
                    e.duration,
                    e.created_at,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) AS question_count,
                    (SELECT r.result_id
                       FROM results r
                      WHERE r.student_id = ? AND r.exam_id = e.exam_id
                      LIMIT 1)                                                     AS already_taken
               FROM exams e
           ORDER BY e.created_at DESC',
            [$studentId]
        );
    }

    /**
     * The exam a student should sit next: the oldest one they have not
     * completed that actually has questions in it.
     *
     * Oldest rather than newest, so a queue of outstanding exams is worked
     * through in the order it was set rather than in reverse.
     *
     * @return array<string,mixed>|null
     */
    public function nextForStudent(int $studentId): ?array
    {
        return Database::fetch(
            'SELECT e.exam_id,
                    e.title,
                    e.duration,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.exam_id) AS question_count
               FROM exams e
              WHERE EXISTS (SELECT 1 FROM questions q WHERE q.exam_id = e.exam_id)
                AND NOT EXISTS (SELECT 1 FROM results r
                                 WHERE r.exam_id = e.exam_id AND r.student_id = ?)
           ORDER BY e.created_at ASC, e.exam_id ASC
              LIMIT 1',
            [$studentId]
        );
    }

    /** Number of exams that actually have at least one question. */
    public function countTakeable(): int
    {
        return Database::count(
            'SELECT COUNT(*) FROM exams e WHERE EXISTS (SELECT 1 FROM questions q WHERE q.exam_id = e.exam_id)'
        );
    }

    public function create(string $title, string $description, int $duration): int
    {
        return Database::insert(
            'INSERT INTO exams (title, description, duration) VALUES (?, ?, ?)',
            [$title, $description, $duration]
        );
    }

    public function update(int $examId, string $title, string $description, int $duration): bool
    {
        return Database::execute(
            'UPDATE exams SET title = ?, description = ?, duration = ? WHERE exam_id = ?',
            [$title, $description, $duration, $examId]
        ) >= 0;
    }

    /** Cascades to questions and results via the schema foreign keys. */
    public function delete(int $examId): bool
    {
        return Database::execute('DELETE FROM exams WHERE exam_id = ?', [$examId]) > 0;
    }

    /** id => title, for filter dropdowns. */
    public function titleOptions(): array
    {
        return Database::fetchAll('SELECT exam_id, title FROM exams ORDER BY title');
    }
}
