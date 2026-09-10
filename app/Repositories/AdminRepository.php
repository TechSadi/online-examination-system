<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching the admins table.
 */
final class AdminRepository
{
    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        return Database::fetch(
            'SELECT admin_id, username, email, full_name, created_at FROM admins WHERE admin_id = ?',
            [$id]
        );
    }

    /**
     * Look up by username OR email. Includes the password hash; used only by
     * the login flow.
     */
    public function findByIdentifierForAuth(string $identifier): ?array
    {
        return Database::fetch(
            'SELECT admin_id, username, full_name, password
               FROM admins
              WHERE username = ? OR email = ?
              LIMIT 1',
            [$identifier, $identifier]
        );
    }

    public function identifierExists(string $username, string $email): bool
    {
        return Database::fetch(
            'SELECT admin_id FROM admins WHERE username = ? OR email = ?',
            [$username, $email]
        ) !== null;
    }

    public function create(string $username, string $email, string $fullName, string $passwordHash): int
    {
        return Database::insert(
            'INSERT INTO admins (username, email, full_name, password) VALUES (?, ?, ?, ?)',
            [$username, $email, $fullName, $passwordHash]
        );
    }

    /** Replace a stored hash, used when a sign-in triggers a rehash. */
    public function updatePassword(int $id, string $passwordHash): void
    {
        Database::execute('UPDATE admins SET password = ? WHERE admin_id = ?', [$passwordHash, $id]);
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM admins WHERE admin_id = ?', [$id]) > 0;
    }

    public function countAll(): int
    {
        return Database::count('SELECT COUNT(*) FROM admins');
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return Database::fetchAll(
            'SELECT admin_id, username, email, full_name, created_at
               FROM admins
           ORDER BY created_at ASC'
        );
    }
}
