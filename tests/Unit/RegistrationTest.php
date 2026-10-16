<?php

declare(strict_types=1);

/**
 * Registro público, aprobación por administrador y cambio de contraseña.
 */

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Users\Services\UserService;

$service = new UserService();
$email = 'registro.test@aula.local';

try {
    // 1. Registro crea el usuario en estado PENDING, sin contraseña.
    $user = $service->register([
        'first_name' => 'Nuevo',
        'last_name' => 'Usuario',
        'email' => $email,
    ]);
    assertSame(true, $user->id > 0, 'El registro debe crear el usuario.');
    assertSame('PENDING', $user->status, 'El usuario registrado debe quedar PENDING.');
    assertSame([], $user->roles, 'El usuario registrado no debe tener roles todavía.');

    // 2. No puede registrarse dos veces con el mismo correo.
    assertThrows(
        fn () => $service->register(['first_name' => 'Otro', 'last_name' => 'Usuario', 'email' => $email]),
        HttpException::class,
        'Registrar un correo duplicado debe lanzar HttpException.'
    );

    // 3. Correo inválido → rechazado.
    assertThrows(
        fn () => $service->register(['first_name' => 'A', 'last_name' => 'B', 'email' => 'no-valido']),
        HttpException::class,
        'Registrar un correo inválido debe lanzar HttpException.'
    );

    // 4. Aprobación: asigna rol, activa y devuelve contraseña aleatoria.
    $approved = $service->approve((int) $user->id, ['STUDENT']);
    assertSame('ACTIVE', $approved['user']->status, 'La cuenta aprobada debe quedar ACTIVA.');
    assertSame(['STUDENT'], $approved['user']->roles, 'La cuenta aprobada debe tener el rol asignado.');
    assertTrue(strlen($approved['password']) >= 12, 'La contraseña generada debe tener al menos 12 caracteres.');

    // 5. Aprobar sin rol → rechazado.
    $user2 = $service->register(['first_name' => 'Sin', 'last_name' => 'Rol', 'email' => 'sin.rol@aula.local']);
    assertThrows(
        fn () => $service->approve((int) $user2->id, []),
        HttpException::class,
        'Aprobar sin seleccionar un rol debe lanzar HttpException.'
    );

    // 6. Cambio de contraseña con contraseña actual incorrecta → rechazado.
    assertThrows(
        fn () => $service->changePassword((int) $user->id, 'incorrecta', 'nueva12345'),
        HttpException::class,
        'Cambiar contraseña con la actual incorrecta debe lanzar HttpException.'
    );

    // 7. Cambio de contraseña correcto.
    $service->changePassword((int) $user->id, $approved['password'], 'nueva12345');
    assertThrows(
        fn () => $service->changePassword((int) $user->id, $approved['password'], 'otra12345'),
        HttpException::class,
        'La contraseña anterior debe dejar de ser válida tras el cambio.'
    );
} finally {
    foreach ([$email, 'sin.rol@aula.local'] as $em) {
        Connection::run('DELETE FROM users WHERE email = :e', [':e' => $em]);
    }
}
