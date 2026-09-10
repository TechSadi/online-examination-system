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

    /**
     * Redirect back to the path that was just requested, as a GET.
     *
     * Used to turn away a rejected POST without leaving it replayable by a
     * refresh. 303 See Other is what makes the browser re-request with GET.
     *
     * The path comes from REQUEST_URI, so it is already on this origin, but
     * it is still normalised before use: a request for "//evil.example/x"
     * would otherwise produce a protocol-relative Location header and turn
     * this into an open redirect.
     */
    public static function redirectToCurrentPath(): never
    {
        $path = Url::current();

        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_starts_with($path, '/' . chr(92))) {
            self::redirect('/');
        }

        header('Location: ' . $path, true, 303);
        exit;
    }

    /**
     * Redirect to a path supplied by the request, or to a fallback.
     *
     * Used where a form has to send the user back where they came from. The
     * value is client-controlled, so it is checked rather than trusted:
     * anything that is not a plain local path - an absolute URL, a
     * protocol-relative "//evil.example/x", a backslash the browser may
     * normalise into one - is discarded in favour of the fallback. Without
     * that check a "return to where you were" field is an open redirect, and
     * an open redirect on a real domain is what makes a phishing link
     * believable.
     *
     * 303 See Other, so a refresh of the destination does not replay the POST.
     */
    public static function redirectToLocalPath(string $path, string $fallback = '/'): never
    {
        $isLocal = $path !== ''
            && $path[0] === '/'
            && !str_starts_with($path, '//')
            && !str_starts_with($path, '/' . chr(92));

        if (!$isLocal) {
            self::redirect($fallback, 303);
        }

        header('Location: ' . $path, true, 303);
        exit;
    }

    /** Send a bare status code and stop. */
    public static function abort(int $status): never
    {
        http_response_code($status);
        exit;
    }
}
