<?php

declare(strict_types=1);

namespace App\Modules\Grades\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Modules\Grades\Services\GradeService;

final class GradeController extends Controller
{
    private GradeService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new GradeService();
    }

    /** Calificaciones según el rol: estudiante ve las propias; docente/admin ve por asignación. */
    public function index(): void
    {
        $this->requireAuth();
        if (Auth::hasRole('STUDENT')) {
            $grades = $this->service->gradesForStudent((int) Auth::id());
            $this->render('Grades/student', ['title' => 'Calificaciones', 'grades' => $grades]);
            return;
        }
        // Docente/admin: listado simple de calificaciones compuestas por asignación.
        $this->render('Grades/teacher', [
            'title' => 'Calificaciones',
            'rows' => $this->service->allComposite(),
        ]);
    }

    /** API GET /api/student/grades */
    public function apiStudentGrades(): void
    {
        Auth::requireRole('STUDENT');
        $this->json(['data' => ['grades' => $this->service->gradesForStudent((int) Auth::id())]]);
    }

    /** API GET /api/teacher/assignment/{id}/students */
    public function apiAssignmentStudents(int $assignmentId): void
    {
        Auth::requireRole(['TEACHER', 'ADMIN']);
        $this->json(['data' => ['students' => $this->service->studentsForAssignment($assignmentId)]]);
    }
}
