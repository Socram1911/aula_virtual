<?php

declare(strict_types=1);

/**
 * Contrato: POST /api/assessment/{id}/attempt y .../attempt/{attemptId}/submit
 * Valida el inicio de intentos y la corrección automática en servidor.
 */

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Assessments\Services\AttemptService;

$pdo = Connection::pdo();
$service = new AttemptService();

// Fixture.
$studentId = Connection::insert(
    'INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (?, ?, ?, ?, ?)',
    ['Test', 'AssessStudent', 'test.assess@aula.local', password_hash('x', PASSWORD_DEFAULT), 'ACTIVE']
);
$roleId = (int) Connection::fetch('SELECT id FROM roles WHERE name = ?', ['STUDENT'])['id'];
Connection::run('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)', [$studentId, $roleId]);

$trackId = Connection::insert('INSERT INTO tracks (code, name, position, status) VALUES (?, ?, ?, ?)', ['T-TEST2', 'Trayecto Test 2', 98, 'ACTIVE']);
$saberId = Connection::insert('INSERT INTO sabers (code, name, track_id, status) VALUES (?, ?, ?, ?)', ['S-TEST2', 'Saber Test 2', $trackId, 'ACTIVE']);
$unitId = Connection::insert('INSERT INTO units (saber_id, title, position, status) VALUES (?, ?, ?, ?)', [$saberId, 'Unidad Test 2', 1, 'PUBLISHED']);

$assessmentId = Connection::insert(
    'INSERT INTO assessments (unit_id, title, max_score, passing_score, status) VALUES (?, ?, ?, ?, ?)',
    [$unitId, 'Evaluación Test', 10, 6, 'PUBLISHED']
);
$questionId = Connection::insert(
    'INSERT INTO questions (assessment_id, type, question_text, points, position) VALUES (?, ?, ?, ?, ?)',
    [$assessmentId, 'SINGLE_ANSWER', '¿2+2?', 10, 1]
);
Connection::run('INSERT INTO question_options (question_id, option_text, is_correct, position) VALUES (?, ?, ?, ?)', [$questionId, '4', 1, 0]);
Connection::run('INSERT INTO question_options (question_id, option_text, is_correct, position) VALUES (?, ?, ?, ?)', [$questionId, '3', 0, 1]);

$correctOptionId = (int) Connection::fetch('SELECT id FROM question_options WHERE question_id = ? AND is_correct = 1', [$questionId])['id'];

try {
    // 1. Iniciar intento.
    $start = $service->start($studentId, $assessmentId);
    assertTrue($start['attempt_id'] > 0, 'El intento debe devolver un id.');
    assertSame(1, count($start['questions']), 'Debe devolver las preguntas de la evaluación.');
    assertSame(2, count($start['questions'][0]['options']), 'Debe devolver las opciones (sin la marca de correcta).');
    assertTrue(! isset($start['questions'][0]['options'][0]['is_correct']), 'No debe revelar las respuestas correctas.');

    // 2. Enviar con respuesta correcta.
    $result = $service->submit($studentId, (int) $start['attempt_id'], [$questionId => [$correctOptionId]]);
    assertSame('COMPLETED', $result['status'], 'El intento debe quedar COMPLETED.');
    assertSame(100.0, $result['percentage'], 'La respuesta correcta debe dar 100%.');
    assertSame(true, $result['passed'], 'Con 100% debe aprobar.');

    // 3. Reenviar un intento finalizado → conflicto.
    assertThrows(
        fn () => $service->submit($studentId, (int) $start['attempt_id'], [$questionId => [$correctOptionId]]),
        HttpException::class,
        'Enviar un intento ya finalizado debe lanzar HttpException.'
    );

    // 4. Superar el límite de intentos (max_attempts = 1) → conflicto.
    Connection::run('UPDATE assessments SET max_attempts = 1 WHERE id = ?', [$assessmentId]);
    assertThrows(
        fn () => $service->start($studentId, $assessmentId),
        HttpException::class,
        'Superar el máximo de intentos debe lanzar HttpException.'
    );
} finally {
    Connection::run('DELETE FROM assessments WHERE id = ?', [$assessmentId]);
    Connection::run('DELETE FROM units WHERE id = ?', [$unitId]);
    Connection::run('DELETE FROM sabers WHERE id = ?', [$saberId]);
    Connection::run('DELETE FROM tracks WHERE id = ?', [$trackId]);
    Connection::run('DELETE FROM user_roles WHERE user_id = ?', [$studentId]);
    Connection::run('DELETE FROM users WHERE id = ?', [$studentId]);
}
