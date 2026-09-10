<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Flash;
use App\Core\Response;
use App\Core\Session;

/**
 * Authentication state and route guards.
 *
 * Guards are the only place that decides who may see a page. Each one
 * terminates the request on failure (Response::redirect returns never), so a
 * caller cannot accidentally fall through to the protected code.
 *
 * Students and admins are kept in separate session keys, exactly as before.
 */
final class Auth
{
    private const STUDENT_ID   = 'student_id';
    private const STUDENT_NAME = 'student_name';
    private const ADMIN_ID     = 'admin_id';
    private const ADMIN_USER   = 'admin_username';
    private const ADMIN_NAME   = 'admin_name';

    /* ── Student ─────────────────────────────────────────── */

    public static function isStudent(): bool
    {
        return Session::has(self::STUDENT_ID);
    }

    public static function studentId(): int
    {
        return (int) Session::get(self::STUDENT_ID, 0);
    }

    public static function studentName(): string
    {
        return (string) Session::get(self::STUDENT_NAME, '');
    }

    /** @param array{student_id:int|string,name:string} $student */
    public static function loginStudent(array $student): void
    {
        Session::regenerate();
        Session::put(self::STUDENT_ID, (int) $student['student_id']);
        Session::put(self::STUDENT_NAME, (string) $student['name']);
    }

    /* ── Admin ───────────────────────────────────────────── */

    public static function isAdmin(): bool
    {
        return Session::has(self::ADMIN_ID);
    }

    public static function adminId(): int
    {
        return (int) Session::get(self::ADMIN_ID, 0);
    }

    public static function adminName(): string
    {
        $name = (string) Session::get(self::ADMIN_NAME, '');

        return $name !== '' ? $name : (string) Session::get(self::ADMIN_USER, '');
    }

    /** @param array{admin_id:int|string,username:string,full_name:?string} $admin */
    public static function loginAdmin(array $admin): void
    {
        Session::regenerate();
        Session::put(self::ADMIN_ID, (int) $admin['admin_id']);
        Session::put(self::ADMIN_USER, (string) $admin['username']);
        Session::put(self::ADMIN_NAME, (string) ($admin['full_name'] ?: $admin['username']));
    }

    /* ── Guards ──────────────────────────────────────────── */

    /** Require a signed-in student, or send them to the student login. */
    public static function requireStudent(): void
    {
        if (!self::isStudent()) {
            Flash::error('Please sign in to continue.');
            Response::redirect('/student/login.php');
        }
    }

    /** Require a signed-in administrator, or send them to the admin login. */
    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            Flash::error('Please sign in to continue.');
            Response::redirect('/admin/login.php');
        }
    }

    /**
     * Require that nobody of the given role is signed in.
     * Used by login and registration pages so an authenticated user is sent
     * to their dashboard instead of being shown a login form.
     */
    public static function requireGuest(string $role = 'student'): void
    {
        if ($role === 'admin' && self::isAdmin()) {
            Response::redirect('/admin/dashboard.php');
        }

        if ($role === 'student' && self::isStudent()) {
            Response::redirect('/student/dashboard.php');
        }
    }

    /* ── Logout ──────────────────────────────────────────── */

    public static function logoutStudent(): void
    {
        Session::forget(self::STUDENT_ID);
        Session::forget(self::STUDENT_NAME);
        Session::destroy();
    }

    public static function logoutAdmin(): void
    {
        Session::forget(self::ADMIN_ID);
        Session::forget(self::ADMIN_USER);
        Session::forget(self::ADMIN_NAME);
        Session::destroy();
    }
}
