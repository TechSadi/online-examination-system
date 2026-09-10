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
     * Students with their attempt count and average percentage.
     *
     * @return list<array<string,mixed>>
     */
    public function allWithStats(): array
    {
        return Database::fetchAll(
            'SELECT s.student_id,
                    s.name,
                    s.email,
                    s.created_at,
                    (SELECT COUNT(*) FROM results r WHERE r.student_id = s.student_id) AS attempts,
                    (SELECT ROUND(AVG((r.score / r.total) * 100), 1)
                       FROM results r
                      WHERE r.student_id = s.student_id AND r.total > 0)               AS avg_score
               FROM students s
           ORDER BY s.created_at DESC'
        );
    }

    /** id => name, for filter dropdowns. */
    public function nameOptions(): array
    {
        return Database::fetchAll('SELECT student_id, name FROM students ORDER BY name');
    }
}
