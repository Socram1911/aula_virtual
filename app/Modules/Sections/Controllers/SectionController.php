<?php

declare(strict_types=1);

namespace App\Modules\Sections\Controllers;

use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Session\Session;
use App\Modules\Sections\Models\Section;
use App\Modules\Sections\Services\SectionService;
use App\Modules\Students\Services\EnrollmentService;

final class SectionController extends Controller
{
    private SectionService $service;
    private EnrollmentService $enrollments;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SectionService();
        $this->enrollments = new EnrollmentService();
    }

    private function periods(): array
    {
        return Connection::fetchAll('SELECT id, name FROM academic_periods ORDER BY id DESC');
    }

    private function tracks(): array
    {
        return Connection::fetchAll('SELECT id, name FROM tracks ORDER BY position ASC');
    }

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Sections/index', ['title' => 'Secciones', 'sections' => $this->service->all()]);
    }

    public function show(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $section = $this->service->find($id);
        $students = $this->enrollments->studentsForSection($id);
        $available = Connection::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.email
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id AND r.name = :role
             WHERE u.id NOT IN (SELECT student_id FROM section_students WHERE section_id = :sid AND status = :enrolled)
             ORDER BY u.last_name ASC',
            [':role' => 'STUDENT', ':sid' => $id, ':enrolled' => 'ENROLLED']
        );
        $this->render('Sections/show', [
            'title' => $section->label(),
            'section' => $section,
            'students' => $students,
            'available' => $available,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Sections/form', [
            'title' => 'Nueva Sección',
            'section' => new Section(),
            'periods' => $this->periods(),
            'tracks' => $this->tracks(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Sección creada correctamente.');
        }, url('/sections'));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('Sections/form', [
            'title' => 'Editar Sección',
            'section' => $this->service->find($id),
            'periods' => $this->periods(),
            'tracks' => $this->tracks(),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Sección actualizada correctamente.');
        }, url('/sections'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Sección eliminada.');
        $this->redirect(url('/sections'));
    }
}
