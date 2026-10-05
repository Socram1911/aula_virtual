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
