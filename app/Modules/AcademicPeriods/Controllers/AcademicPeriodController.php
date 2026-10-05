<?php

declare(strict_types=1);

namespace App\Modules\AcademicPeriods\Controllers;

use App\Core\Controller;
use App\Core\Session\Session;
use App\Modules\AcademicPeriods\Models\AcademicPeriod;
use App\Modules\AcademicPeriods\Services\AcademicPeriodService;

final class AcademicPeriodController extends Controller
{
    private AcademicPeriodService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AcademicPeriodService();
    }

    public function index(): void
    {
        $this->requireRole('ADMIN');
        $this->render('AcademicPeriods/index', [
            'title' => 'Períodos Académicos',
            'periods' => $this->service->all(),
            'statuses' => AcademicPeriod::STATUSES,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('AcademicPeriods/form', [
            'title' => 'Nuevo Período',
            'period' => new AcademicPeriod(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Período creado correctamente.');
        }, url('/academic-periods'));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('AcademicPeriods/form', [
            'title' => 'Editar Período',
            'period' => $this->service->find($id),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Período actualizado correctamente.');
        }, url('/academic-periods'));
    }

    public function transition(int $id): void
    {
        $this->requireRole('ADMIN');
        $target = (string) $this->request->input('status', '');
        $this->handle(function () use ($id, $target): void {
            $this->service->transition($id, $target);
            Session::flash('_success', 'Estado del período actualizado.');
        }, url('/academic-periods'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Período eliminado.');
        $this->redirect(url('/academic-periods'));
    }
}
