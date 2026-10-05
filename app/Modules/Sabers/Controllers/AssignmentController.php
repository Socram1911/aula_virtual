<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Session\Session;
use App\Modules\Grades\Services\GradeService;
use App\Modules\Sabers\Services\AssignmentService;

final class AssignmentController extends Controller
{
    private AssignmentService $service;
    private GradeService $grades;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AssignmentService();
        $this->grades = new GradeService();
    }

    private function options(): array
    {
        return [
            'sabers' => Connection::fetchAll('SELECT id, name, track_id FROM sabers ORDER BY name ASC'),
            'sections' => Connection::fetchAll('SELECT id, number, track_id FROM sections ORDER BY number ASC'),
            'periods' => Connection::fetchAll('SELECT id, name FROM academic_periods ORDER BY id DESC'),
            'teachers' => Connection::fetchAll(
                'SELECT u.id, u.first_name, u.last_name FROM users u
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id AND r.name = :role
                 ORDER BY u.last_name ASC',
                [':role' => 'TEACHER']
            ),
        ];
    }

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        // El docente ve únicamente sus asignaciones; el administrador ve todas.
        $assignments = Auth::hasRole('ADMIN')
            ? $this->service->all()
            : $this->service->forTeacher((int) Auth::id());
        $this->render('Sabers/Assignments/index', ['title' => 'Asignaciones', 'assignments' => $assignments]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Sabers/Assignments/form', [
            'title' => 'Nueva Asignación',
            'assignment' => null,
            'selectedTeachers' => [],
            'primaryId' => 0,
        ] + $this->options());
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Asignación creada correctamente.');
        }, url('/saber-assignments'));
    }

    public function show(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Sabers/Assignments/show', [
            'title' => 'Asignación',
            'assignment' => $this->service->find($id),
            'teachers' => $this->service->teachers($id),
            'components' => $this->grades->components($id),
            'students' => $this->grades->studentsForAssignment($id),
        ]);
    }

    /** Configura los componentes (pesos) de calificación de una asignación. */
    public function storeComponents(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $components = $this->request->input('components', []);
        $this->grades->saveComponents($id, is_array($components) ? $components : []);
        Session::flash('_success', 'Configuración de calificaciones guardada.');
        $this->redirect(url('/saber-assignments/' . $id));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $assignment = $this->service->find($id);
        $teachers = $this->service->teachers($id);
        $primaryId = 0;
        foreach ($teachers as $t) {
            if (! empty($t['is_primary'])) {
                $primaryId = (int) $t['teacher_id'];
                break;
            }
        }
        $this->render('Sabers/Assignments/form', [
            'title' => 'Editar Asignación',
            'assignment' => $assignment,
            'selectedTeachers' => array_map('intval', array_column($teachers, 'teacher_id')),
            'primaryId' => $primaryId,
        ] + $this->options());
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Asignación actualizada correctamente.');
        }, url('/saber-assignments'));
    }

    public function setStatus(int $id): void
    {
        $this->requireRole('ADMIN');
        $target = (string) $this->request->input('status', '');
        $this->handle(function () use ($id, $target): void {
            $this->service->setStatus($id, $target);
            Session::flash('_success', 'Estado de la asignación actualizado.');
        }, url('/saber-assignments'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Asignación eliminada.');
        $this->redirect(url('/saber-assignments'));
    }
}
