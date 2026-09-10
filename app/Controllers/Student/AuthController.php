<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\StudentRepository;
use App\Services\AuthService;
use App\Services\LoginThrottle;
use App\Validation\Validator;

/**
 * Student registration, sign-in and sign-out.
 */
final class AuthController
{
    private const ROLE = 'student';

    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly StudentRepository $students = new StudentRepository(),
        private readonly LoginThrottle $throttle = new LoginThrottle()
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

        $ip   = LoginThrottle::clientIp();
        $wait = $this->throttle->secondsUntilRetry(self::ROLE, $email, $ip);

        // Checked before the password is verified, so a locked-out attacker
        // gets no signal at all - not even the timing of a hash comparison.
        if ($wait > 0) {
            $this->renderLogin([sprintf(
                'Too many failed sign-in attempts. Please try again in %s.',
                LoginThrottle::describeWait($wait)
            )], $email);

            return;
        }

        $student = $this->auth->attemptStudent($email, $password);

        if ($student === null) {
            $this->throttle->recordFailure(self::ROLE, $email, $ip);

            // One message for both causes: never reveal whether the address exists.
            $this->renderLogin(['Invalid email or password. Please try again.'], $email);

            return;
        }

        $this->throttle->clear(self::ROLE, $email);

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
            ->password('password')
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

    /**
     * Sign out.
     *
     * POST only. The CSRF guard lets safe methods through untouched, so a
     * GET here would still sign the user out - and any third-party page
     * could trigger it with an image tag.
     */
    public function logout(): void
    {
        if (!Request::isPost()) {
            Response::redirect('/student/login.php');
        }

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
