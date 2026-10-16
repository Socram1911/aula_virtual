<?php

declare(strict_types=1);

namespace App\Modules\Users\Services;

use App\Core\Auth\PasswordHasher;
use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Users\Models\User;
use App\Modules\Users\Repositories\UserRepository;

final class UserService
{
    private UserRepository $repository;

    public const ROLES = ['ADMIN', 'TEACHER', 'STUDENT'];

    public function __construct()
    {
        $this->repository = new UserRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    /** Usuarios que se registraron solos y esperan aprobación. */
    public function pending(): array
    {
        return $this->repository->pending();
    }

    /**
     * Registro público: crea el usuario en estado PENDING (sin contraseña ni roles).
     * El administrador aprobará la cuenta y asignará el tipo (rol) correspondiente.
     */
    public function register(array $input): User
    {
        $data = $this->validateRegistration($input);
        $id = $this->repository->create($data + [':password_hash' => null, ':status' => 'PENDING']);
        AuditService::log('registrar Usuario (pendiente)', 'User', $id, null, $data);
        return $this->find($id);
    }

    /**
     * Aprobación por el administrador: asigna roles, genera contraseña aleatoria,
     * activa la cuenta y devuelve la contraseña en texto plano (solo para el correo).
     *
     * @param string[] $roles
     * @return array{user: User, password: string}
     */
    public function approve(int $id, array $roles): array
    {
        $user = $this->find($id);
        $roles = $this->normalizeRoles(['roles' => $roles]);
        if ($roles === []) {
            throw HttpException::unprocessable('Seleccione al menos un tipo de cuenta (rol).', ['Seleccione al menos un tipo de cuenta (rol).']);
        }

        $password = self::randomPassword();
        $this->repository->updatePassword($id, PasswordHasher::hash($password));
        $this->repository->updateStatus($id, 'ACTIVE');
        $this->repository->syncRoles($id, $roles);
        AuditService::log('aprobar Usuario', 'User', $id, ['status' => $user->status], ['status' => 'ACTIVE', 'roles' => $roles]);

        return ['user' => $this->find($id), 'password' => $password];
    }

    /** Cambia la contraseña del usuario autenticado, verificando la actual. */
    public function changePassword(int $userId, string $current, string $new): void
    {
        $hash = $this->repository->passwordHash($userId);
        if ($hash === null) {
            throw HttpException::notFound('Usuario no encontrado.');
        }
        if (! PasswordHasher::verify($current, $hash)) {
            throw HttpException::unprocessable('La contraseña actual es incorrecta.', ['La contraseña actual es incorrecta.']);
        }
        if (strlen($new) < 8) {
            throw HttpException::unprocessable('La nueva contraseña debe tener al menos 8 caracteres.', ['La nueva contraseña debe tener al menos 8 caracteres.']);
        }
        $this->repository->updatePassword($userId, PasswordHasher::hash($new));
        AuditService::log('cambiar Contraseña', 'User', $userId, null, null);
    }

    /** Genera una contraseña aleatoria legible (sin caracteres ambiguos). */
    public static function randomPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }
        return $password;
    }

    public function teachers(): array
    {
        return $this->repository->byRole('TEACHER');
    }

    public function students(): array
    {
        return $this->repository->byRole('STUDENT');
    }

    public function find(int $id): User
    {
        $user = $this->repository->find($id);
        if ($user === null) {
            throw HttpException::notFound('Usuario no encontrado.');
        }
        return $user;
    }

    public function create(array $input): User
    {
        $data = $this->validate($input);
        $password = (string) ($input['password'] ?? '');
        if ($password === '') {
            throw HttpException::unprocessable('La contraseña es obligatoria.', ['La contraseña es obligatoria.']);
        }
        $data[':password_hash'] = PasswordHasher::hash($password);
        $id = $this->repository->create($data);
        $this->repository->syncRoles($id, $this->normalizeRoles($input));
        AuditService::log('crear Usuario', 'User', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): User
    {
        $before = $this->find($id);
        $data = $this->validate($input, $id);
        $this->repository->update($id, $data);
        $this->repository->syncRoles($id, $this->normalizeRoles($input));
        $password = (string) ($input['password'] ?? '');
        if ($password !== '') {
            $this->repository->updatePassword($id, PasswordHasher::hash($password));
        }
        AuditService::log('modificar Usuario', 'User', $id, ['email' => $before->email], $data);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $user = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Usuario', 'User', $id, ['email' => $user->email], null);
    }

    private function normalizeRoles(array $input): array
    {
        $roles = $input['roles'] ?? [];
        if (! is_array($roles)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $roles), fn ($r) => in_array($r, self::ROLES, true)));
    }

    private function validateRegistration(array $input): array
    {
        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));

        $errors = [];
        if ($firstName === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($lastName === '') {
            $errors[] = 'El apellido es obligatorio.';
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico es inválido.';
        } elseif ($this->repository->existsEmail($email)) {
            $errors[] = 'El correo electrónico ya está registrado.';
        }
        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
        ];
    }

    private function validate(array $input, ?int $ignoreId = null): array
    {
        $firstName = trim((string) ($input['first_name'] ?? ''));
        $lastName = trim((string) ($input['last_name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $status = (string) ($input['status'] ?? 'ACTIVE');

        $errors = [];
        if ($firstName === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($lastName === '') {
            $errors[] = 'El apellido es obligatorio.';
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico es inválido.';
        } elseif ($this->repository->existsEmail($email, $ignoreId)) {
            $errors[] = 'El correo electrónico ya está registrado.';
        }
        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $errors[] = 'Estado inválido.';
        }
        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':status' => $status,
        ];
    }
}
