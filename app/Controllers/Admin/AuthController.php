<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\AdminRepository;
use App\Services\AuthService;
use App\Services\LoginThrottle;
use App\Validation\Validator;

/**
 * Administrator sign-in, sign-out, and account creation.
 *
 * Account creation requires an authenticated administrator. It previously sat
 * behind a shared invite code hardcoded in the source and printed in the UI,
 * which meant anyone who could reach the page could grant themselves full
 * administrative access.
 */
final class AuthController
{
    private const ROLE = 'admin';

    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly AdminRepository $admins = new AdminRepository(),
        private readonly LoginThrottle $throttle = new LoginThrottle()
    ) {
    }

    public function showLogin(): void
    {
        Auth::requireGuest('admin');

        View::render('admin/login', [
            'pageTitle' => 'Admin Login',
            'role'      => 'public',
        ]);
    }

    public function login(): void
    {
        Auth::requireGuest('admin');

        $identifier = Request::post('identifier');
        $password   = Request::raw('password');

        if ($identifier === '' || $password === '') {
            $this->renderLogin(['Please fill in all fields.'], $identifier);

            return;
        }

        $ip   = LoginThrottle::clientIp();
        $wait = $this->throttle->secondsUntilRetry(self::ROLE, $identifier, $ip);

        // Checked before the password is verified, so a locked-out attacker
        // gets no signal at all - not even the timing of a hash comparison.
        if ($wait > 0) {
            $this->renderLogin([sprintf(
                'Too many failed sign-in attempts. Please try again in %s.',
                LoginThrottle::describeWait($wait)
            )], $identifier);

            return;
        }

        $admin = $this->auth->attemptAdmin($identifier, $password);

        if ($admin === null) {
            $this->throttle->recordFailure(self::ROLE, $identifier, $ip);
            $this->renderLogin(['Invalid username / email or password.'], $identifier);

            return;
        }

        $this->throttle->clear(self::ROLE, $identifier);

        Auth::loginAdmin($admin);
        Response::redirect('/admin/dashboard.php');
    }

    public function showRegister(): void
    {
        Auth::requireAdmin();

        View::render('admin/register', [
            'pageTitle' => 'Add Administrator',
            'role'      => 'admin',
        ], 'layouts/admin');
    }

    public function register(): void
    {
        Auth::requireAdmin();

        $input = [
            'full_name' => Request::post('full_name'),
            'username'  => Request::post('username'),
            'email'     => Request::post('email'),
            'password'  => Request::raw('password'),
            'confirm'   => Request::raw('confirm'),
        ];

        $validator = Validator::make($input)
            ->required('full_name', 'Full name')
            ->maxLength('full_name', 100, 'Full name')
            ->minLength('username', 3, 'Username')
            ->maxLength('username', 60, 'Username')
            ->username('username')
            ->email('email', 'A valid email address')
            ->maxLength('email', 150, 'Email address')
            ->password('password')
            ->matches('password', 'confirm', 'Passwords do not match.');

        if ($validator->passes() && $this->admins->identifierExists($input['username'], $input['email'])) {
            $validator->add('That username or email is already registered as an admin.');
        }

        if ($validator->fails()) {
            View::render('admin/register', [
                'pageTitle' => 'Add Administrator',
                'role'      => 'admin',
                'errors'    => $validator->errors(),
                'old'       => [
                    'full_name' => $input['full_name'],
                    'username'  => $input['username'],
                    'email'     => $input['email'],
                ],
            ], 'layouts/admin');

            return;
        }

        $this->auth->registerAdmin(
            $input['username'],
            $input['email'],
            $input['full_name'],
            $input['password']
        );

        Response::redirectWithSuccess(
            '/admin/admins.php',
            sprintf('Administrator "%s" created successfully.', $input['username'])
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
            Response::redirect('/admin/login.php');
        }

        Auth::logoutAdmin();
        Response::redirect('/admin/login.php');
    }

    /** @param list<string> $errors */
    private function renderLogin(array $errors, string $identifier): void
    {
        View::render('admin/login', [
            'pageTitle' => 'Admin Login',
            'role'      => 'public',
            'errors'    => $errors,
            'old'       => ['identifier' => $identifier],
        ]);
    }
}
