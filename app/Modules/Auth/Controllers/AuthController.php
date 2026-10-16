<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;
use App\Core\Session\Session;
use App\Modules\Users\Services\UserService;

final class AuthController extends Controller
{
    private UserService $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserService();
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(url('/dashboard'));
        }
        $this->render('Auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(): void
    {
        $email = (string) $this->request->input('email', '');
        $password = (string) $this->request->input('password', '');

        if (! Auth::attempt($email, $password)) {
            $this->backWithErrors(url('/login'), ['Credenciales inválidas o cuenta pendiente de aprobación.'], ['email' => $email]);
        }

        $this->redirect(url('/dashboard'));
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('/login'));
    }

    // ===== Registro público (autogestión) =====

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect(url('/dashboard'));
        }
        $this->render('Auth/register', ['title' => 'Registrarse']);
    }

    public function register(): void
    {
        $this->handle(function (): void {
            $this->users->register($this->request->all());
            Session::flash('_success', 'Solicitud enviada. El administrador aprobará tu cuenta y recibirás tu contraseña por correo.');
        }, url('/login'));
    }

    // ===== Cambio de contraseña =====

    public function showChangePassword(): void
    {
        $this->requireAuth();
        $this->render('Auth/change_password', ['title' => 'Cambiar contraseña']);
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $current = (string) $this->request->input('current_password', '');
        $new = (string) $this->request->input('new_password', '');
        $confirm = (string) $this->request->input('confirm_password', '');

        if ($new !== $confirm) {
            $this->backWithErrors(url('/account/password'), ['La nueva contraseña y su confirmación no coinciden.']);
            return;
        }

        $this->handle(function () use ($current, $new): void {
            $this->users->changePassword((int) Auth::id(), $current, $new);
            Session::flash('_success', 'Contraseña actualizada correctamente.');
        }, url('/account/password'));
    }
}
