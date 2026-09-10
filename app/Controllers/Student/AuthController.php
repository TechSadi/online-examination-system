<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\StudentRepository;
use App\Services\AuthService;
use App\Validation\Validator;

/**
 * Student registration, sign-in and sign-out.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly StudentRepository $students = new StudentRepository()
    ) {
    }

    public function showLogin(): void
    {
        Auth::requireGuest('student');

        View::render('student/login', [
            'pageTitle' => 'Student Login',
            'role'      => 'public',
        ]);
    }

    public function login(): void
    {
        Auth::requireGuest('student');

        $email    = Request::post('email');
        $password = Request::raw('password');

        if ($email === '' || $password === '') {
            $this->renderLogin(['Please fill in all fields.'], $email);

            return;
        }

        $student = $this->auth->attemptStudent($email, $password);

        if ($student === null) {
            // One message for both causes: never reveal whether the address exists.
            $this->renderLogin(['Invalid email or password. Please try again.'], $email);

            return;
        }

        Auth::loginStudent($student);
        Response::redirect('/student/dashboard.php');
    }

    public function showRegister(): void
    {
        Auth::requireGuest('student');

        View::render('student/register', [
            'pageTitle' => 'Student Registration',
            'role'      => 'public',
        ]);
    }

    public function register(): void
    {
        Auth::requireGuest('student');

        $input = [
            'name'     => Request::post('name'),
            'email'    => Request::post('email'),
            'password' => Request::raw('password'),
            'confirm'  => Request::raw('confirm'),
        ];

        $validator = Validator::make($input)
            ->required('name', 'Full name')
            ->maxLength('name', 100, 'Full name')
            ->email('email', 'A valid email address')
            ->maxLength('email', 150, 'Email address')
            ->minLength('password', 6, 'Password')
            ->matches('password', 'confirm', 'Passwords do not match.');

        if ($validator->passes() && $this->students->emailExists($input['email'])) {
            $validator->add('That email address is already registered.');
        }

        if ($validator->fails()) {
            View::render('student/register', [
                'pageTitle' => 'Student Registration',
                'role'      => 'public',
                'errors'    => $validator->errors(),
                'old'       => ['name' => $input['name'], 'email' => $input['email']],
            ]);

            return;
        }

        $this->auth->registerStudent($input['name'], $input['email'], $input['password']);

        Response::redirectWithSuccess(
            '/student/login.php',
            'Account created! You can now log in.'
        );
    }

    public function logout(): void
    {
        Auth::logoutStudent();
        Response::redirect('/student/login.php');
    }

    /** @param list<string> $errors */
    private function renderLogin(array $errors, string $email): void
    {
        View::render('student/login', [
            'pageTitle' => 'Student Login',
            'role'      => 'public',
            'errors'    => $errors,
            'old'       => ['email' => $email],
        ]);
    }
}
