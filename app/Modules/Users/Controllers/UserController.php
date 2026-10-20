<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Core\Controller;
use App\Core\Mail\Mailer;
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
            'pending' => $this->service->pending(),
        ]);
    }

    public function approve(int $id): void
    {
        $this->requireRole('ADMIN');
        $this->render('Users/approve', [
            'title' => 'Aprobar cuenta',
            'user' => $this->service->find($id),
            'roles' => UserService::ROLES,
        ]);
    }

    public function approveStore(int $id): void
    {
        $this->requireRole('ADMIN');
        $input = $this->request->all();
        try {
            $result = $this->service->approve($id, $input['roles'] ?? []);
        } catch (\App\Core\Exceptions\HttpException $e) {
            $this->backWithErrors(url('/users/' . $id . '/approve'), $e->details() ?: [$e->getMessage()], $input);
            return;
        }

        $user = $result['user'];
        $mail = $this->notifyAccepted($user, $result['password']);

        if ($mail['ok']) {
            Session::flash('_success', "Cuenta de {$user->fullName()} aprobada y correo enviado con la contraseña.");
        } else {
            // La cuenta queda aprobada, pero se informa el motivo del fallo del correo.
            Session::flash('_success', "Cuenta de {$user->fullName()} aprobada. Correo NO enviado: {$mail['message']}");
        }
        $this->redirect(url('/users'));
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

    /** Envía el correo de aceptación con la contraseña generada. */
    private function notifyAccepted(User $user, string $password): array
    {
        $loginUrl = url('/login');
        $roles = implode(', ', $user->roles);
        $subject = 'Tu cuenta en Aula Virtual Académica ha sido aprobada';

        $html = '<div style="font-family:sans-serif;color:#22302A;max-width:560px">'
            . '<h2 style="color:#123631">¡Bienvenido, ' . e($user->first_name) . '!</h2>'
            . '<p>Tu cuenta en <strong>Aula Virtual Académica</strong> ha sido aprobada.</p>'
            . '<p>Tipo de cuenta: <strong>' . e($roles) . '</strong></p>'
            . '<p>Puedes iniciar sesión con las siguientes credenciales:</p>'
            . '<p><strong>Correo:</strong> ' . e($user->email) . '<br>'
            . '<strong>Contraseña temporal:</strong> <code style="font-size:16px">' . e($password) . '</code></p>'
            . '<p><a href="' . e($loginUrl) . '" style="background:#123631;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none">Iniciar sesión</a></p>'
            . '<p style="color:#6B726C;font-size:12px">Por seguridad, cambia tu contraseña después de iniciar sesión.</p>'
            . '</div>';

        return Mailer::send($user->email, $subject, $html);
    }
}
