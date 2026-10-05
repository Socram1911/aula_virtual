<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Core\Response\Response;
use App\Core\Session\Session;

/**
 * Autenticación y autorización basada en sesión.
 */
final class Auth
{
    private static ?array $user = null;

    /** Intenta autenticar con email y contraseña. Devuelve true si fue exitoso. */
    public static function attempt(string $email, string $password): bool
    {
        $user = Connection::fetch(
            'SELECT id, email, password_hash, status FROM users WHERE email = :email LIMIT 1',
            [':email' => strtolower(trim($email))]
        );

        if (! $user || $user['status'] !== 'ACTIVE') {
            return false;
        }

        if (! PasswordHasher::verify($password, $user['password_hash'])) {
            return false;
        }

        if (PasswordHasher::needsRehash($user['password_hash'])) {
            Connection::run(
                'UPDATE users SET password_hash = :hash WHERE id = :id',
                [':hash' => PasswordHasher::hash($password), ':id' => $user['id']]
            );
        }

        Session::regenerate();
        Session::put('user_id', (int) $user['id']);

        return true;
    }

    public static function loginById(int $id): void
    {
        Session::regenerate();
        Session::put('user_id', $id);
        self::$user = null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function guest(): bool
    {
        return ! self::check();
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id === null ? null : (int) $id;
    }

    /** Devuelve el usuario actual (con roles) o null. */
    public static function user(): ?array
    {
        if (self::$user === null && self::check()) {
            $user = Connection::fetch(
                'SELECT id, first_name, last_name, email, status FROM users WHERE id = :id LIMIT 1',
                [':id' => self::id()]
            );
            if ($user) {
                $user['roles'] = array_column(Connection::fetchAll(
                    'SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :id',
                    [':id' => self::id()]
                ), 'name');
                self::$user = $user;
            }
        }
        return self::$user;
    }

    public static function roles(): array
    {
        return self::user()['roles'] ?? [];
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    /** Exige autenticación: redirige al login (páginas) o lanza 401 (API). */
    public static function requireAuth(): void
    {
        if (! self::check()) {
            if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                throw HttpException::unauthorized('Debe iniciar sesión para continuar.');
            }
            Response::redirect(url('/login'));
        }
    }

    /** Lanza 403 si el usuario no tiene ninguno de los roles indicados. */
    public static function requireRole(string|array $roles): void
    {
        self::requireAuth();
        $roles = (array) $roles;
        if (! array_intersect($roles, self::roles())) {
            throw HttpException::forbidden('No tiene permiso para realizar esta acción.');
        }
    }
}
