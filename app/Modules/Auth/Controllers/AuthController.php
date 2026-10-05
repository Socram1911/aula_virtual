<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Auth\Auth;
use App\Core\Controller;

final class AuthController extends Controller
{
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
            $this->backWithErrors(url('/login'), ['Credenciales inválidas o cuenta inactiva.'], ['email' => $email]);
        }

        $this->redirect(url('/dashboard'));
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('/login'));
    }
}
