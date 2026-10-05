<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Core\Session\Session;
use App\Modules\Sabers\Models\Saber;
use App\Modules\Sabers\Models\Unit;
use App\Modules\Sabers\Services\AssignmentService;
use App\Modules\Sabers\Services\SaberService;

final class SaberController extends Controller
{
    private SaberService $service;
    private AssignmentService $assignments;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SaberService();
        $this->assignments = new AssignmentService();
    }

    /** Un docente solo puede gestionar Saberes que le han sido asignados. */
    private function guardSaber(int $saberId): void
    {
        if (Auth::hasRole('ADMIN')) {
            return;
        }
        if (! $this->assignments->teacherHasSaber((int) Auth::id(), $saberId)) {
            throw HttpException::forbidden('No tiene asignado este Saber.');
        }
    }

    private function guardUnit(int $unitId): void
    {
        if (Auth::hasRole('ADMIN')) {
            return;
        }
        $unit = $this->service->findUnit($unitId);
        $this->guardSaber($unit->saber_id);
    }

    private function tracks(): array
    {
        return Connection::fetchAll('SELECT id, name FROM tracks ORDER BY position ASC');
    }

    // ===== Saberes =====

    public function index(): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->render('Sabers/index', ['title' => 'Saberes', 'sabers' => $this->service->all()]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Sabers/form', ['title' => 'Nuevo Saber', 'saber' => new Saber(), 'tracks' => $this->tracks()]);
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Saber creado correctamente.');
        }, url('/sabers'));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('Sabers/form', ['title' => 'Editar Saber', 'saber' => $this->service->find($id), 'tracks' => $this->tracks()]);
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Saber actualizado correctamente.');
        }, url('/sabers'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Saber eliminado.');
        $this->redirect(url('/sabers'));
    }

    // ===== Unidades =====

    public function units(int $saberId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardSaber($saberId);
        $saber = $this->service->find($saberId);
        $this->render('Sabers/units', [
            'title' => 'Unidades de ' . $saber->name,
            'saber' => $saber,
            'units' => $this->service->units($saberId),
        ]);
    }

    public function createUnit(int $saberId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardSaber($saberId);
        $this->render('Sabers/unit_form', [
            'title' => 'Nueva Unidad',
            'saber' => $this->service->find($saberId),
            'unit' => new Unit(),
        ]);
    }

    public function storeUnit(int $saberId): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardSaber($saberId);
        $this->handle(function () use ($saberId): void {
            $this->service->createUnit($saberId, $this->request->all());
            Session::flash('_success', 'Unidad creada correctamente.');
        }, url('/sabers/' . $saberId . '/units'));
    }

    public function editUnit(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($id);
        $unit = $this->service->findUnit($id);
        $this->render('Sabers/unit_form', [
            'title' => 'Editar Unidad',
            'saber' => $this->service->find($unit->saber_id),
            'unit' => $unit,
        ]);
    }

    public function updateUnit(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($id);
        $unit = $this->service->findUnit($id);
        $this->handle(function () use ($id, $unit): void {
            $this->service->updateUnit($id, $this->request->all());
            Session::flash('_success', 'Unidad actualizada correctamente.');
        }, url('/sabers/' . $unit->saber_id . '/units'));
    }

    public function publishUnit(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($id);
        $unit = $this->service->findUnit($id);
        $target = $unit->status === 'PUBLISHED' ? 'UNPUBLISHED' : 'PUBLISHED';
        $this->service->setUnitStatus($id, $target);
        Session::flash('_success', $target === 'PUBLISHED' ? 'Unidad publicada.' : 'Unidad despublicada.');
        $this->redirect(url('/sabers/' . $unit->saber_id . '/units'));
    }

    public function deleteUnit(int $id): void
    {
        $this->requireRole(['ADMIN', 'TEACHER']);
        $this->guardUnit($id);
        $unit = $this->service->findUnit($id);
        $this->service->deleteUnit($id);
        Session::flash('_success', 'Unidad eliminada.');
        $this->redirect(url('/sabers/' . $unit->saber_id . '/units'));
    }
}
