<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Session\Session;
use App\Modules\Assessments\Models\Assessment;
use App\Modules\Assessments\Services\AssessmentService;
use App\Modules\Sabers\Services\AssignmentService;

final class AssessmentController extends Controller
{
    private AssessmentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AssessmentService();
    }

    /** Unidades y asignaciones disponibles: todas para ADMIN; solo las propias para TEACHER. */
    private function options(): array
    {
        if (Auth::hasRole('ADMIN')) {
            return [
                'units' => Connection::fetchAll('SELECT id, title FROM units ORDER BY id DESC'),
                'assignments' => Connection::fetchAll(
                    'SELECT sa.id, CONCAT(s.name, " - Sección ", sec.number) AS label
                     FROM saber_assignments sa
                     JOIN sabers s ON s.id = sa.saber_id
                     JOIN sections sec ON sec.id = sa.section_id
                     ORDER BY sa.id DESC'
                ),
            ];
        }

        $assignments = (new AssignmentService())->forTeacher((int) Auth::id());
        $saberIds = array_unique(array_map(fn ($a) => (int) $a->saber_id, $assignments));
        $units = $saberIds === []
            ? []
            : Connection::fetchAll(
                'SELECT id, title FROM units WHERE saber_id IN (' . implode(',', $saberIds) . ') ORDER BY id DESC'
            );

        return [
            'units' => $units,
            'assignments' => array_map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->saber_name . ' - Sección ' . $a->section_number,
            ], $assignments),
        ];
    }

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $assessments = Auth::hasRole('ADMIN')
            ? $this->service->all()
            : $this->service->forTeacher((int) Auth::id());
        $this->render('Assessments/index', ['title' => 'Evaluaciones', 'assessments' => $assessments]);
    }

    public function create(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Assessments/form', [
            'title' => 'Nueva Evaluación',
            'assessment' => new Assessment(),
        ] + $this->options());
    }

    public function store(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Evaluación creada. Agregue preguntas.');
        }, url('/assessments'));
    }

    public function edit(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Assessments/form', [
            'title' => 'Editar Evaluación',
            'assessment' => $this->service->find($id),
        ] + $this->options());
    }

    public function update(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Evaluación actualizada.');
        }, url('/assessments'));
    }

    public function setStatus(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $target = (string) $this->request->input('status', '');
        $this->handle(function () use ($id, $target): void {
            $this->service->setStatus($id, $target);
            Session::flash('_success', 'Estado actualizado.');
        }, url('/assessments'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->service->delete($id);
        Session::flash('_success', 'Evaluación eliminada.');
        $this->redirect(url('/assessments'));
    }

    public function questions(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Assessments/questions', [
            'title' => 'Preguntas',
            'assessment' => $this->service->find($id),
            'questions' => $this->service->questions($id),
            'types' => AssessmentService::TYPES,
        ]);
    }

    public function storeQuestion(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->handle(function () use ($id): void {
            $this->service->addQuestion($id, $this->request->all());
            Session::flash('_success', 'Pregunta agregada.');
        }, url('/assessments/' . $id . '/questions'));
    }

    public function deleteQuestion(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        // id aquí es el id de la pregunta.
        $row = Connection::fetch('SELECT assessment_id FROM questions WHERE id = :id', [':id' => $id]);
        $this->service->deleteQuestion($id);
        Session::flash('_success', 'Pregunta eliminada.');
        $this->redirect(url('/assessments/' . (int) ($row['assessment_id'] ?? 0) . '/questions'));
    }
}
