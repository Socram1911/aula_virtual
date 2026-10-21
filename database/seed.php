<?php

declare(strict_types=1);

/**
 * Sembrado inicial: roles (ADMIN, TEACHER, STUDENT) y un usuario administrador.
 * Uso: php database/seed.php
 *
 * Credenciales por defecto:
 *   email:    admin@aula.local
 *   password: admin123
 */

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/config.php';

use App\Core\Auth\PasswordHasher;
use App\Core\Database\Connection;

$pdo = Connection::pdo();

$roles = ['ADMIN', 'TEACHER', 'STUDENT'];
foreach ($roles as $role) {
    Connection::insertIgnore('roles', 'name', $role);
}
echo "Roles sembrados: " . implode(', ', $roles) . "\n";

// Usuario administrador por defecto (solo si no existe).
$email = 'admin@aula.local';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
if (! $stmt->fetch()) {
    $userId = Connection::insert(
        'INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (?, ?, ?, ?, ?)',
        ['Administrador', 'Sistema', $email, PasswordHasher::hash('admin123'), 'ACTIVE']
    );

    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
    $roleStmt->execute(['ADMIN']);
    $roleId = (int) $roleStmt->fetchColumn();

    $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
    echo "Administrador creado: {$email} / admin123\n";
} else {
    echo "El administrador {$email} ya existe.\n";
}

echo "Sembrado completado.\n";
