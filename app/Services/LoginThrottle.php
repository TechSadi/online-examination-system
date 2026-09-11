<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Request;
use App\Repositories\LoginAttemptRepository;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Rate limiting for sign-in.
 *
 * Without this, nothing stopped an attacker from working through a password
 * list against a known email address as fast as the server would answer. The
 * hashing cost slows each guess down; only a throttle bounds how many there
 * can be.
 *
 * Counting happens in the database rather than the session, because an
 * attacker simply discards the session cookie between attempts.
 *
 * Two counters are kept:
 *   - per identifier, which protects one account from a focused attack
 *   - per address, which slows one guess sprayed across many accounts, a
 *     pattern that never trips any single account's threshold
 *
 * Both use a moving window, so a lockout lapses on its own and an attacker
 * cannot keep a real user locked out forever by guessing at them.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly LoginAttemptRepository $attempts = new LoginAttemptRepository()
    ) {
    }

    private function maxAttempts(): int
    {
        return max(1, (int) Config::get('security.login_max_attempts', 5));
    }

    private function decay(): int
    {
        return max(60, (int) Config::get('security.login_decay', 900));
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function windowStart(): string
    {
        return $this->now()
            ->modify(sprintf('-%d seconds', $this->decay()))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Seconds the caller must wait, or 0 when they may try now.
     *
     * The wait runs from the most recent recorded failure. Attempts made
     * while locked out are refused before the password is checked and are
     * not recorded, so the log cannot be grown without bound by hammering a
     * locked account - the lockout simply lapses when the window does.
     */
    public function secondsUntilRetry(string $role, string $identifier, string $ip): int
    {
        $since = $this->windowStart();

        $byIdentifier = $this->attempts->countForIdentifier($role, $identifier, $since);
        $byIp         = $this->attempts->countForIp($ip, $since);

        // An address gets more headroom than a single account: a shared NAT or
        // a computer lab must not be locked out because one person mistyped.
        if ($byIdentifier < $this->maxAttempts() && $byIp < $this->maxAttempts() * 5) {
            return 0;
        }

        $last = $this->attempts->lastAttemptAt($role, $identifier, $since);

        if ($last === null) {
            return $this->decay();
        }

        $retryAt = (new DateTimeImmutable($last, new DateTimeZone('UTC')))
            ->modify(sprintf('+%d seconds', $this->decay()));

        return max(1, $retryAt->getTimestamp() - $this->now()->getTimestamp());
    }

    public function recordFailure(string $role, string $identifier, string $ip): void
    {
        $this->attempts->record($role, $identifier, $ip, $this->now()->format('Y-m-d H:i:s'));

        // Opportunistic tidy-up: rows older than a day can no longer affect
        // any decision. Doing it here avoids needing a scheduled job.
        if (random_int(1, 100) === 1) {
            $this->attempts->purgeOlderThan(
                $this->now()->modify('-1 day')->format('Y-m-d H:i:s')
            );
        }
    }

    public function clear(string $role, string $identifier): void
    {
        $this->attempts->clear($role, $identifier);
    }

    /** A human-readable wait, for the message shown to the user. */
    public static function describeWait(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%d second%s', $seconds, $seconds === 1 ? '' : 's');
        }

        $minutes = (int) ceil($seconds / 60);

        return sprintf('%d minute%s', $minutes, $minutes === 1 ? '' : 's');
    }

    /**
     * The client address, packed for the VARBINARY(16) column.
     *
     * Request::clientIp() decides which address that is. It reads
     * X-Forwarded-For only where the deployment has declared it sits behind
     * a proxy, so a directly exposed server still cannot be told by a client
     * what address to count it under.
     */
    public static function clientIp(): string
    {
        $address = Request::clientIp();
        $packed  = $address === '' ? false : @inet_pton($address);

        return $packed === false ? str_repeat("\0", 4) : $packed;
    }
}
