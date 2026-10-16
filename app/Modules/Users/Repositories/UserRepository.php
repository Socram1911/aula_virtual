<?php

declare(strict_types=1);

namespace App\Modules\Users\Repositories;

use App\Core\Database\Connection;
use App\Modules\Users\Models\User;

final class UserRepository
{
    public function all(): array
    {
        $users = $this->rawAll();
        $roles = $this->rolesForUsers(array_column($users, 'id'));
        return array_map(function ($row) use ($roles) {
            $row['roles'] = $roles[$row['id']] ?? [];
            return User::fromArray($row);
        }, $users);
    }

    private function rawAll(): array
    {
        return Connection::fetchAll('SELECT id, first_name, last_name, email, status FROM users ORDER BY id DESC');
    }

    public function find(int $id): ?User
    {
        $row = Connection::fetch('SELECT id, first_name, last_name, email, status FROM users WHERE id = :id', [':id' => $id]);
        if ($row === null) {
            return null;
        }
        $row['roles'] = $this->rolesForUser($id);
        return User::fromArray($row);
    }

    public function existsEmail(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM users WHERE email = :email';
        $params = [':email' => $email];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        return (int) (Connection::fetch($sql, $params)['c'] ?? 0) > 0;
    }

    /** Usuarios con un rol específico (p. ej. TEACHER, STUDENT). */
    public function byRole(string $role): array
    {
        $rows = Connection::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id AND r.name = :role
             ORDER BY u.last_name ASC',
            [':role' => $role]
        );
        return array_map([User::class, 'fromArray'], $rows);
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (:first_name, :last_name, :email, :password_hash, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, status = :status WHERE id = :id',
            $data
        );
    }

    public function updatePassword(int $id, string $hash): void
    {
        Connection::run('UPDATE users SET password_hash = :h WHERE id = :id', [':h' => $hash, ':id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        Connection::run('UPDATE users SET status = :s WHERE id = :id', [':s' => $status, ':id' => $id]);
    }

    /** Hash de contraseña actual del usuario (para verificación de cambio de contraseña). */
    public function passwordHash(int $id): ?string
    {
        $row = Connection::fetch('SELECT password_hash FROM users WHERE id = :id', [':id' => $id]);
        return $row === null ? null : ($row['password_hash'] ?? null);
    }

    /** Usuarios pendientes de aprobación (registro propio). */
    public function pending(): array
    {
        $rows = Connection::fetchAll(
            'SELECT id, first_name, last_name, email, status FROM users WHERE status = :s ORDER BY id ASC',
            [':s' => 'PENDING']
        );
        return array_map([User::class, 'fromArray'], $rows);
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM users WHERE id = :id', [':id' => $id]);
    }

    public function syncRoles(int $userId, array $roleNames): void
    {
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            Connection::run('DELETE FROM user_roles WHERE user_id = :id', [':id' => $userId]);
            $find = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
            $insert = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            foreach ($roleNames as $name) {
                $find->execute([$name]);
                $roleId = $find->fetchColumn();
                if ($roleId !== false) {
                    $insert->execute([$userId, (int) $roleId]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function rolesForUser(int $userId): array
    {
        return $this->rolesForUsers([$userId])[$userId] ?? [];
    }

    private function rolesForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }
        $in = implode(',', array_map('intval', $userIds));
        $rows = Connection::fetchAll(
            "SELECT ur.user_id, r.name FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id IN ({$in})"
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['user_id']][] = $row['name'];
        }
        return $map;
    }
}
