<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Repositories;

use App\Core\Database\Connection;

final class AttemptRepository
{
    public function countAttempts(int $assessmentId, int $studentId): int
    {
        return (int) (Connection::fetch(
            'SELECT COUNT(*) AS c FROM assessment_attempts WHERE assessment_id = :a AND student_id = :s',
            [':a' => $assessmentId, ':s' => $studentId]
        )['c'] ?? 0);
    }

    public function create(int $assessmentId, int $studentId, int $number): int
    {
        return Connection::insert(
            'INSERT INTO assessment_attempts (assessment_id, student_id, attempt_number, started_at, status)
             VALUES (:a, :s, :n, :at, :status)',
            [':a' => $assessmentId, ':s' => $studentId, ':n' => $number, ':at' => date('Y-m-d H:i:s'), ':status' => 'IN_PROGRESS']
        );
    }

    public function find(int $id): ?array
    {
        return Connection::fetch('SELECT * FROM assessment_attempts WHERE id = :id', [':id' => $id]);
    }

    public function complete(int $id, float $score, float $percentage): void
    {
        Connection::run(
            'UPDATE assessment_attempts SET completed_at = :at, score = :score, percentage = :pct, status = :status WHERE id = :id',
            [':at' => date('Y-m-d H:i:s'), ':score' => $score, ':pct' => $percentage, ':status' => 'COMPLETED', ':id' => $id]
        );
    }

    public function expire(int $id): void
    {
        Connection::run('UPDATE assessment_attempts SET status = :status WHERE id = :id', [':status' => 'EXPIRED', ':id' => $id]);
    }

    public function saveAnswer(int $attemptId, int $questionId, ?string $value, bool $correct, float $score): void
    {
        Connection::run(
            'INSERT INTO attempt_answers (attempt_id, question_id, answer_value, is_correct, score)
             VALUES (:a, :q, :v, :c, :s)
             ON DUPLICATE KEY UPDATE answer_value = :v, is_correct = :c, score = :s',
            [':a' => $attemptId, ':q' => $questionId, ':v' => $value, ':c' => $correct ? 1 : 0, ':s' => $score]
        );
    }

    /** Último intento (cualquier estado) del estudiante en la evaluación. */
    public function lastAttempt(int $assessmentId, int $studentId): ?array
    {
        return Connection::fetch(
            'SELECT * FROM assessment_attempts WHERE assessment_id = :a AND student_id = :s ORDER BY attempt_number DESC LIMIT 1',
            [':a' => $assessmentId, ':s' => $studentId]
        );
    }
}
