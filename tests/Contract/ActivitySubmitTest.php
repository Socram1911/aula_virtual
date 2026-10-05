<?php

declare(strict_types=1);

/**
 * Contrato: POST /api/activity/{id}/submit
 * Valida la corrección en servidor y el manejo de errores del servicio.
 */

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Activities\Services\ActivityService;

$pdo = Connection::pdo();
$service = new ActivityService();

// Fixture: usuario estudiante + bloque de actividad.
$studentId = Connection::insert(
    'INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (?, ?, ?, ?, ?)',
    ['Test', 'Student', 'test.student@aula.local', password_hash('x', PASSWORD_DEFAULT), 'ACTIVE']
);
$roleId = (int) Connection::fetch('SELECT id FROM roles WHERE name = ?', ['STUDENT'])['id'];
Connection::run('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$studentId, $roleId]);

$trackId = Connection::insert('INSERT INTO tracks (code, name, position, status) VALUES (?, ?, ?, ?)', ['T-TEST', 'Trayecto Test', 99, 'ACTIVE']);
$saberId = Connection::insert('INSERT INTO sabers (code, name, track_id, status) VALUES (?, ?, ?, ?)', ['S-TEST', 'Saber Test', $trackId, 'ACTIVE']);
$unitId = Connection::insert('INSERT INTO units (saber_id, title, position, status) VALUES (?, ?, ?, ?)', [$saberId, 'Unidad Test', 1, 'PUBLISHED']);

$settings = json_encode([
    'type' => 'SINGLE_ANSWER',
    'question' => '¿Cuánto es 2+2?',
    'options' => [['text' => '4', 'correct' => true], ['text' => '3', 'correct' => false]],
    'classification' => 'FORMATIVE',
], JSON_UNESCAPED_UNICODE);

$blockId = Connection::insert(
    'INSERT INTO content_blocks (unit_id, type, position, title, content, settings, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$unitId, 'ACTIVITY', 1, 'Actividad Test', '', $settings, 'ACTIVE']
);

try {
    // 1. Respuesta correcta.
    $result = $service->submit($studentId, $blockId, 0);
    assertSame(true, $result['is_correct'], 'La respuesta correcta debe marcarse como correcta.');
    assertSame(1, $result['score'], 'El puntaje de la respuesta correcta debe ser 1.');
    assertTrue(isset($result['feedback']), 'La respuesta debe incluir retroalimentación.');

    // 2. Reintento: debe lanzar conflicto (409).
    assertThrows(
        fn () => $service->submit($studentId, $blockId, 0),
        HttpException::class,
        'Reintentar una actividad completada debe lanzar HttpException.'
    );

    // 3. Actividad inexistente (404).
    assertThrows(
        fn () => $service->submit($studentId, 999999, 0),
        HttpException::class,
        'Una actividad inexistente debe lanzar HttpException.'
    );

    // 4. Validación de definiciones (respuesta única requiere exactamente una correcta).
    assertThrows(
        fn () => $service->validateSettings(['type' => 'SINGLE_ANSWER', 'question' => 'x', 'options' => [['text' => 'a', 'correct' => true], ['text' => 'b', 'correct' => true]], 'classification' => 'FORMATIVE']),
        HttpException::class,
        'Una actividad de respuesta única con dos correctas debe rechazarse.'
    );
} finally {
    // Limpieza (en orden inverso de dependencias).
    Connection::run('DELETE FROM content_blocks WHERE unit_id = ?', [$unitId]);
    Connection::run('DELETE FROM student_block_progress WHERE block_id = ?', [$blockId]);
    Connection::run('DELETE FROM units WHERE id = ?', [$unitId]);
    Connection::run('DELETE FROM sabers WHERE id = ?', [$saberId]);
    Connection::run('DELETE FROM tracks WHERE id = ?', [$trackId]);
    Connection::run('DELETE FROM user_roles WHERE user_id = ?', [$studentId]);
    Connection::run('DELETE FROM users WHERE id = ?', [$studentId]);
}
