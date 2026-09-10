<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching the students table.
 */
final class StudentRepository
{
    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch(
            'SELECT student_id, name, email, created_at FROM students WHERE student_id = ?',
            [$id]
        );
    }

    /** Includes the password hash; used only by the login flow. */
    public function findByEmailForAuth(string $email): ?array
    {
        return Database::fetch(
            'SELECT student_id, name, email, password FROM students WHERE email = ?',
            [$email]
        );
    }

    public function emailExists(string $email): bool
    {
        return Database::fetch('SELECT student_id FROM students WHERE email = ?', [$email]) !== null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        return Database::insert(
            'INSERT INTO students (name, email, password) VALUES (?, ?, ?)',
            [$name, $email, $passwordHash]
        );
    }

    /** Replace a stored hash, used when a sign-in triggers a rehash. */
    public function updatePassword(int $id, string $passwordHash): void
    {
        Database::execute('UPDATE students SET password = ? WHERE student_id = ?', [$passwordHash, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM students WHERE student_id = ?', [$id]) > 0;
    }

    public function countAll(): int
    {
        return Database::count('SELECT COUNT(*) FROM students');
    }

    /**
     * The columns the student list may be ordered by.
     *
     * Held here, next to the query, so the allowlist and the SQL it guards
     * cannot drift apart.
     *
     * @return array<string,string>
     */
    public static function sortableColumns(): array
    {
        return [
            'name'       => 's.name',
            'email'      => 's.email',
            'attempts'   => 'attempts',
            'avg_score'  => 'avg_score',
            'created_at' => 's.created_at',
        ];
    }

    /**
     * One page of students with their attempt count and average percentage.
     *
     * $orderBy is a literal assembled by Sorter from the allowlist above; the
     * search term is bound. Limit and offset are interpolated only after an
     * integer cast, because MySQL's native prepared statements will not take
     * a string-bound parameter in a LIMIT clause.
     *
     * @return list<array<string,mixed>>
     */
    public function paginateWithStats(string $search, string $orderBy, int $limit, int $offset): array
    {
        [$where, $params] = self::searchClause($search);

        return Database::fetchAll(
            'SELECT s.student_id,
                    s.name,
                    s.email,
                    s.created_at,
                    (SELECT COUNT(*) FROM results r WHERE r.student_id = s.student_id) AS attempts,
                    (SELECT ROUND(AVG((r.score / r.total) * 100), 1)
                       FROM results r
                      WHERE r.student_id = s.student_id AND r.total > 0)               AS avg_score
               FROM students s'
            . $where
            . ' ORDER BY ' . $orderBy
            . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public function countMatching(string $search): int
    {
        [$where, $params] = self::searchClause($search);

        return Database::count('SELECT COUNT(*) FROM students s' . $where, $params);
    }

    /**
     * The WHERE fragment for a name-or-email search, and its bound values.
     *
     * @return array{0:string,1:list<string>}
     */
    private static function searchClause(string $search): array
    {
        if ($search === '') {
            return ['', []];
        }

        // The wildcards are added here rather than being typed by the user,
        // and the characters LIKE treats specially are escaped, so a search
        // for "100%" looks for that text instead of matching everything.
        $term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

        return [" WHERE (s.name LIKE ? OR s.email LIKE ?)", [$term, $term]];
    }

    /** id => name, for filter dropdowns. */
    public function nameOptions(): array
    {
        return Database::fetchAll('SELECT student_id, name FROM students ORDER BY name');
    }
}
