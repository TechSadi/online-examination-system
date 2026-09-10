<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\AdminRepository;

/**
 * Administrator account listing and removal.
 */
final class AdminController
{
    public function __construct(
        private readonly AdminRepository $admins = new AdminRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();

        if (Request::isPost() && Request::post('action') === 'delete') {
            $this->delete();
        }

        View::render('admin/admins', [
            'pageTitle'      => 'Manage Admins',
            'role'           => 'admin',
            'admins'         => $this->admins->all(),
            'currentAdminId' => Auth::adminId(),
        ], 'layouts/admin');
    }

    /**
     * Remove another administrator.
     *
     * Two rules are preserved from the original: an admin cannot delete their
     * own account while signed in, and the last remaining account cannot be
     * removed - otherwise nobody could ever sign in again.
     */
    private function delete(): void
    {
        $targetId = Request::id('admin_id');
        $current  = Auth::adminId();

        if ($targetId === 0 || $this->admins->findById($targetId) === null) {
            Response::redirectWithError('/admin/admins.php', 'That administrator could not be found.');
        }

        if ($targetId === $current) {
            Response::redirectWithError(
                '/admin/admins.php',
                'You cannot delete your own account while logged in.'
            );
        }

        if ($this->admins->countAll() <= 1) {
            Response::redirectWithError(
                '/admin/admins.php',
                'Cannot delete the only remaining admin account.'
            );
        }

        $this->admins->delete($targetId);

        Response::redirectWithSuccess('/admin/admins.php', 'Admin account deleted.');
    }
}
