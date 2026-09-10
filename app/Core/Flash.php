<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One-request notification messages.
 *
 * Replaces the ad-hoc "?msg=deleted" query strings, each of which had to be
 * decoded by a bespoke if/elseif chain in the receiving page. Messages now
 * survive exactly one redirect and are rendered by a single partial.
 */
final class Flash
{
    private const KEY       = '_flash';
    private const OLD_INPUT = '_old_input';
    private const ERRORS    = '_errors';

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('danger', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function add(string $type, string $message): void
    {
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Take every pending message, clearing the queue.
     *
     * @return list<array{type:string,message:string}>
     */
    public static function take(): array
    {
        $messages = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);

        return is_array($messages) ? $messages : [];
    }

    /**
     * Stash validation errors for the page being redirected to.
     *
     * @param list<string> $errors
     */
    public static function errors(array $errors): void
    {
        $_SESSION[self::ERRORS] = $errors;
    }

    /** @return list<string> */
    public static function takeErrors(): array
    {
        $errors = $_SESSION[self::ERRORS] ?? [];
        unset($_SESSION[self::ERRORS]);

        return is_array($errors) ? $errors : [];
    }

    /**
     * Stash submitted values so the form can repopulate itself after a redirect.
     * Secrets are dropped rather than round-tripped through the session.
     *
     * @param array<string,mixed> $input
     */
    public static function old(array $input): void
    {
        unset($input['password'], $input['confirm'], $input['invite_code']);
        $_SESSION[self::OLD_INPUT] = $input;
    }

    /** @return array<string,mixed> */
    public static function takeOld(): array
    {
        $old = $_SESSION[self::OLD_INPUT] ?? [];
        unset($_SESSION[self::OLD_INPUT]);

        return is_array($old) ? $old : [];
    }
}
