<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\Config;
use App\Services\AuthService;

/**
 * Small fluent validator.
 *
 * Validation rules were previously written inline in each page, which is how
 * add_exam.php came to enforce a 300-minute ceiling that edit_exam.php did
 * not. Rules now live in one place and both controllers call the same set.
 *
 * Every rule records a human-readable message; the caller decides whether to
 * re-render the form or redirect back with the errors.
 */
final class Validator
{
    /** @var list<string> */
    private array $errors = [];

    /** @param array<string,mixed> $data */
    public function __construct(private array $data = [])
    {
    }

    /** @param array<string,mixed> $data */
    public static function make(array $data = []): self
    {
        return new self($data);
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->errors[] = sprintf('%s is required.', $label);
        }

        return $this;
    }

    public function email(string $field, string $label = 'A valid email address'): self
    {
        if (filter_var($this->value($field), FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = sprintf('%s is required.', $label);
        }

        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        if (mb_strlen($this->value($field)) < $min) {
            $this->errors[] = sprintf('%s must be at least %d characters.', $label, $min);
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        if (mb_strlen($this->value($field)) > $max) {
            $this->errors[] = sprintf('%s cannot exceed %d characters.', $label, $max);
        }

        return $this;
    }

    /** Raw (untrimmed) comparison, so passwords are compared exactly as typed. */
    public function matches(string $field, string $otherField, string $message): self
    {
        $a = (string) ($this->data[$field] ?? '');
        $b = (string) ($this->data[$otherField] ?? '');

        if ($a !== $b) {
            $this->errors[] = $message;
        }

        return $this;
    }

    /**
     * Password policy, applied to the raw value.
     *
     * value() trims, which is right for a name and wrong for a password: a
     * password is whatever was typed, spaces included, so the length checked
     * here must be the length that will actually be hashed.
     *
     * The upper bound is not arbitrary. bcrypt reads only the first 72 bytes
     * and silently ignores the rest, so accepting a longer passphrase would
     * mean quietly hashing something other than what the user chose - and two
     * different passphrases sharing a prefix would open the same account.
     * Measured in bytes, not characters, since a multi-byte character can
     * cost four of the 72.
     */
    public function password(string $field, string $label = 'Password'): self
    {
        $value  = (string) ($this->data[$field] ?? '');
        $min    = max(1, (int) Config::get('security.password_min_length', 8));
        $length = strlen($value);

        if (mb_strlen($value) < $min) {
            $this->errors[] = sprintf('%s must be at least %d characters.', $label, $min);

            return $this;
        }

        if ($length > AuthService::MAX_PASSWORD_BYTES) {
            $this->errors[] = sprintf(
                '%s cannot exceed %d bytes. Please choose a shorter one.',
                $label,
                AuthService::MAX_PASSWORD_BYTES
            );
        }

        return $this;
    }

    public function username(string $field, string $label = 'Username'): self
    {
        $value = $this->value($field);

        if ($value !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $value) !== 1) {
            $this->errors[] = sprintf('%s may only contain letters, numbers, and underscores.', $label);
        }

        return $this;
    }

    public function integerBetween(string $field, int $min, int $max, string $label): self
    {
        $raw = $this->data[$field] ?? '';

        if (!is_numeric(is_scalar($raw) ? (string) $raw : '')) {
            $this->errors[] = sprintf('%s must be a number.', $label);

            return $this;
        }

        $value = (int) $raw;

        if ($value < $min || $value > $max) {
            $this->errors[] = sprintf('%s must be between %d and %d.', $label, $min, $max);
        }

        return $this;
    }

    public function inList(string $field, array $allowed, string $message): self
    {
        if (!in_array($this->value($field), array_map('strval', $allowed), true)) {
            $this->errors[] = $message;
        }

        return $this;
    }

    /** Record a failure decided elsewhere, e.g. a uniqueness check. */
    public function add(string $message): self
    {
        $this->errors[] = $message;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
