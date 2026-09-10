<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Standardised redirects.
 *
 * The old code repeated `header('Location: ' . BASE_URL . '/x.php'); exit;`
 * roughly forty times. Each helper here terminates the request, so a caller
 * can never accidentally continue executing after redirecting - which was a
 * real risk in the previous authorisation guards.
 */
final class Response
{
    /** Redirect to an application path and stop. */
    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . Url::to($path), true, $status);
        exit;
    }

    /** Redirect, queueing a success message for the destination page. */
    public static function redirectWithSuccess(string $path, string $message): never
    {
        Flash::success($message);
        self::redirect($path);
    }

    /** Redirect, queueing an error message for the destination page. */
    public static function redirectWithError(string $path, string $message): never
    {
        Flash::error($message);
        self::redirect($path);
    }

    /**
     * Redirect back to a form, preserving validation errors and input so the
     * user does not retype everything.
     *
     * @param list<string>        $errors
     * @param array<string,mixed> $input
     */
    public static function redirectWithErrors(string $path, array $errors, array $input = []): never
    {
        Flash::errors($errors);
        Flash::old($input);
        self::redirect($path);
    }

    /** Send a bare status code and stop. */
    public static function abort(int $status): never
    {
        http_response_code($status);
        exit;
    }
}
