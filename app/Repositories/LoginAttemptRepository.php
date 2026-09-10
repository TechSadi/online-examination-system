<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL touching login_attempts.
 *
 * One row per failed sign-in. Rows are counted over a moving window rather
 * than kept as a running total on the account, so a lockout lapses on its own
 * and an attacker cannot lock a real user out indefinitely by guessing at them
 * forever.
 *
 * Identifiers are stored lower-cased so "Admin" and "admin" share a counter.
 */
final class LoginAttemptRepository
{
    public function record(string $role, string $identifier, string $ip, string $at): void
    {
        Database::execute(
            'INSERT INTO login_attempts (role, identifier, ip_address, attempted_at) VALUES (?, ?, ?, ?)',
            [$role, self::normalise($identifier), $ip, $at]
        );
    }

    public function countForIdentifier(string $role, string $identifier, string $since): int
    {
        return Database::count(
            'SELECT COUNT(*) FROM login_attempts
              WHERE role = ? AND identifier = ? AND attempted_at > ?',
            [$role, self::normalise($identifier), $since]
        );
    }

    /**
     * Failures from one address across every account.
     *
     * Counted separately from the per-identifier total so that spraying one
     * guess across many accounts is still slowed down, even though no single
     * account ever reaches its own threshold.
     */
    public function countForIp(string $ip, string $since): int
    {
        return Database::count(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?',
            [$ip, $since]
        );
    }

    /** The most recent failure for an identifier, used to time the lockout. */
    public function lastAttemptAt(string $role, string $identifier, string $since): ?string
    {
        $value = Database::fetchColumn(
            'SELECT MAX(attempted_at) FROM login_attempts
              WHERE role = ? AND identifier = ? AND attempted_at > ?',
            [$role, self::normalise($identifier), $since]
        );

        return is_string($value) ? $value : null;
    }

    /** Clear an identifier's failures; called when a sign-in succeeds. */
    public function clear(string $role, string $identifier): void
    {
        Database::execute(
            'DELETE FROM login_attempts WHERE role = ? AND identifier = ?',
            [$role, self::normalise($identifier)]
        );
    }

    /** Drop rows too old to affect any decision, keeping the table small. */
    public function purgeOlderThan(string $cutoff): void
    {
        Database::execute('DELETE FROM login_attempts WHERE attempted_at < ?', [$cutoff]);
    }

    private static function normalise(string $identifier): string
    {
        return mb_strtolower(mb_substr(trim($identifier), 0, 190));
    }
}
