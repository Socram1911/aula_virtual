<?php

declare(strict_types=1);

namespace App\Modules\Students\Controllers;

use App\Core\Controller;
use App\Core\Session\Session;
use App\Modules\Students\Services\EnrollmentService;

final class StudentsController extends Controller
{
    private EnrollmentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new EnrollmentService();
    }

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Students/index', [
            'title' => 'Estudiantes',
            'students' => $this->service->allStudents(),
        ]);
    }

    public function enroll(int $sectionId): void
    {
        $this->requireRole('ADMIN');
        $studentId = (int) $this->request->input('student_id', 0);
        $date = (string) $this->request->input('enrollment_date', '');
        if ($studentId <= 0) {
            $this->backWithErrors(url('/sections/' . $sectionId), ['Seleccione un estudiante.']);
            return;
        }
        $this->handle(function () use ($sectionId, $studentId, $date): void {
            $this->service->enroll($sectionId, $studentId, $date !== '' ? $date : null);
            Session::flash('_success', 'Estudiante inscrito correctamente.');
        }, url('/sections/' . $sectionId));
    }

    public function withdraw(int $sectionId): void
    {
        $this->requireRole('ADMIN');
        $studentId = (int) $this->request->input('student_id', 0);
        $this->handle(function () use ($sectionId, $studentId): void {
            $this->service->withdraw($sectionId, $studentId);
            Session::flash('_success', 'Estudiante retirado de la sección.');
        }, url('/sections/' . $sectionId));
    }
}
