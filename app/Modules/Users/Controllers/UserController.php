<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Core\Controller;
use App\Core\Session\Session;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\UserService;

final class UserController extends Controller
{
    private UserService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new UserService();
    }

    public function index(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Users/index', [
            'title' => 'Usuarios',
            'users' => $this->service->all(),
        ]);
    }

    public function teachers(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Users/index', [
            'title' => 'Docentes',
            'users' => $this->service->teachers(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('ADMIN');
        $this->render('Users/form', [
            'title' => 'Nuevo Usuario',
            'user' => new User(),
            'roles' => UserService::ROLES,
        ]);
    }

    public function store(): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function (): void {
            $this->service->create($this->request->all());
            Session::flash('_success', 'Usuario creado correctamente.');
        }, url('/users'));
    }

    public function edit(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('Users/form', [
            'title' => 'Editar Usuario',
            'user' => $this->service->find($id),
            'roles' => UserService::ROLES,
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->handle(function () use ($id): void {
            $this->service->update($id, $this->request->all());
            Session::flash('_success', 'Usuario actualizado correctamente.');
        }, url('/users'));
    }

    public function destroy(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->service->delete($id);
        Session::flash('_success', 'Usuario eliminado.');
        $this->redirect(url('/users'));
    }
}
