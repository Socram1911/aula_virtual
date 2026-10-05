<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Modules\Sabers\Services\AssignmentService;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $roles = Auth::roles();

        if (in_array('STUDENT', $roles, true)) {
            $this->redirect(url('/my-sabers'));
        }

        $firstName = (string) (Auth::user()['first_name'] ?? '');

        if (in_array('TEACHER', $roles, true)) {
            $assignments = (new AssignmentService())->forTeacher((int) Auth::id());
            $this->render('Dashboard/teacher', [
                'title' => 'Dashboard del docente',
                'assignments' => $assignments,
                'firstName' => $firstName,
            ]);
            return;
        }

        $this->render('Dashboard/home', [
            'title' => 'Dashboard',
            'roles' => $roles,
            'firstName' => $firstName,
        ]);
    }
}
