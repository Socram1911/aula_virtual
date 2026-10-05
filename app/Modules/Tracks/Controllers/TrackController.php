<?php

declare(strict_types=1);

namespace App\Modules\Tracks\Controllers;

use App\Core\Controller;
use App\Core\Database\Connection;
use App\Core\Session\Session;
use App\Modules\Tracks\Models\Track;
use App\Modules\Tracks\Services\TrackService;

final class TrackController extends Controller
{
    private TrackService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new TrackService();
    }

    private function periods(): array
    {
        return Connection::fetchAll('SELECT id, name FROM academic_periods ORDER BY id DESC');
    }

    public function index(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Tracks/index', ['title' => 'Trayectos', 'tracks' => $this->service->all()]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Tracks/form', [
            'title' => 'Nuevo Trayecto',
            'track' => new Track(),
            'periods' => $this->periods(),
            'selected' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Trayecto creado correctamente.');
        }, url('/tracks'));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('Tracks/form', [
            'title' => 'Editar Trayecto',
            'track' => $this->service->find($id),
            'periods' => $this->periods(),
            'selected' => $this->service->periodIds($id),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Trayecto actualizado correctamente.');
        }, url('/tracks'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Trayecto eliminado.');
        $this->redirect(url('/tracks'));
    }
}
