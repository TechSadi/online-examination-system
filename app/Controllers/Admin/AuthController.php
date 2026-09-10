<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\AdminRepository;
use App\Services\AuthService;
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
    public function __construct(
        private readonly AuthService $auth = new AuthService(),
        private readonly AdminRepository $admins = new AdminRepository()
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

        $admin = $this->auth->attemptAdmin($identifier, $password);

        if ($admin === null) {
            $this->renderLogin(['Invalid username / email or password.'], $identifier);

            return;
        }

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
            ->minLength('password', 6, 'Password')
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

    public function logout(): void
    {
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
