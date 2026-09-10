<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\AdminRepository;
use App\Repositories\StudentRepository;

/**
 * Credential verification and account creation.
 *
 * Both roles share one hashing policy here rather than each login page
 * choosing its own. Passwords are never trimmed: the original code called
 * trim() before hashing, silently altering any password with leading or
 * trailing whitespace.
 */
final class AuthService
{
    /**
     * bcrypt only considers the first 72 bytes of a password and silently
     * discards the rest, so two different long passphrases can hash to the
     * same value. Registration rejects anything longer rather than accepting
     * a password whose tail does nothing - see Validator::password().
     */
    public const MAX_PASSWORD_BYTES = 72;

    public function __construct(
        private readonly StudentRepository $students = new StudentRepository(),
        private readonly AdminRepository $admins = new AdminRepository()
    ) {
    }

    /** @return array{cost:int} */
    private static function options(): array
    {
        // Clamped to the range bcrypt actually accepts, so a mistyped
        // configuration value cannot make hashing trivially cheap.
        return ['cost' => max(10, min(15, (int) Config::get('security.bcrypt_cost', 12)))];
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, self::options());
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

        $this->rehashIfNeeded(
            (string) $student['password'],
            $password,
            fn (string $hash) => $this->students->updatePassword((int) $student['student_id'], $hash)
        );

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

        $this->rehashIfNeeded(
            (string) $admin['password'],
            $password,
            fn (string $hash) => $this->admins->updatePassword((int) $admin['admin_id'], $hash)
        );

        unset($admin['password']);

        return $admin;
    }

    /**
     * Upgrade a stored hash that no longer meets current policy.
     *
     * A successful sign-in is the only moment the plaintext is available, so
     * it is the only chance to re-hash. This is what lets the work factor be
     * raised later without invalidating existing accounts, and it quietly
     * migrates the seeded administrator off whatever cost it was created with.
     *
     * @param callable(string):void $store
     */
    private function rehashIfNeeded(string $existingHash, string $password, callable $store): void
    {
        if (!password_needs_rehash($existingHash, PASSWORD_BCRYPT, self::options())) {
            return;
        }

        $store($this->hash($password));
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
     * timing when an account does not exist. Its cost matches the default
     * policy, so the dummy verification takes about as long as a real one.
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
}
