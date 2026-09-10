<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminRepository;
use App\Repositories\StudentRepository;

/**
 * Credential verification and account creation.
 *
 * Both roles share one hashing policy here rather than each login page
 * choosing its own. Passwords are never trimmed: the previous code called
 * trim() before hashing, silently altering any password with leading or
 * trailing whitespace.
 */
final class AuthService
{
    public function __construct(
        private readonly StudentRepository $students = new StudentRepository(),
        private readonly AdminRepository $admins = new AdminRepository()
    ) {
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify student credentials.
     *
     * @return array<string,mixed>|null the student row, or null on failure
     */
    public function attemptStudent(string $email, string $password): ?array
    {
        $student = $this->students->findByEmailForAuth($email);

        if ($student === null) {
            // Hash a dummy value so a missing account costs roughly the same
            // as a wrong password, narrowing the enumeration timing gap.
            password_verify($password, self::DUMMY_HASH);

            return null;
        }

        if (!password_verify($password, (string) $student['password'])) {
            return null;
        }

        unset($student['password']);

        return $student;
    }

    /**
     * Verify admin credentials by username or email.
     *
     * @return array<string,mixed>|null the admin row, or null on failure
     */
    public function attemptAdmin(string $identifier, string $password): ?array
    {
        $admin = $this->admins->findByIdentifierForAuth($identifier);

        if ($admin === null) {
            password_verify($password, self::DUMMY_HASH);

            return null;
        }

        if (!password_verify($password, (string) $admin['password'])) {
            return null;
        }

        unset($admin['password']);

        return $admin;
    }

    public function registerStudent(string $name, string $email, string $password): int
    {
        return $this->students->create($name, $email, $this->hash($password));
    }

    public function registerAdmin(string $username, string $email, string $fullName, string $password): int
    {
        return $this->admins->create($username, $email, $fullName, $this->hash($password));
    }

    /**
     * A valid bcrypt hash of a value no user can supply, used to equalise
     * timing when an account does not exist.
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
}
