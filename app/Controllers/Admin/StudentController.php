<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\Auth;
use App\Repositories\StudentRepository;

/**
 * Student account administration.
 */
final class StudentController
{
    public function __construct(
        private readonly StudentRepository $students = new StudentRepository()
    ) {
    }

    public function index(): void
    {
        Auth::requireAdmin();

        if (Request::isPost() && Request::post('action') === 'delete') {
            $this->delete();
        }

        View::render('admin/students', [
            'pageTitle' => 'Manage Students',
            'role'      => 'admin',
            'students'  => $this->students->allWithStats(),
        ], 'layouts/admin');
    }

    /** Deleting a student cascades to their recorded results. */
    private function delete(): void
    {
        $studentId = Request::id('student_id');

        if ($studentId === 0 || $this->students->findById($studentId) === null) {
            Response::redirectWithError('/admin/students.php', 'That student could not be found.');
        }

        $this->students->delete($studentId);

        Response::redirectWithSuccess('/admin/students.php', 'Student account deleted.');
    }
}
