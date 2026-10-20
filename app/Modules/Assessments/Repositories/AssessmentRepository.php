<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Repositories;

use App\Core\Database\Connection;
use App\Modules\Assessments\Models\Assessment;

final class AssessmentRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll('SELECT * FROM assessments ORDER BY id DESC');
        return array_map([Assessment::class, 'fromArray'], $rows);
    }

    /** Evaluaciones visibles para un docente (las de sus Saberes asignados). */
    public function forTeacher(int $teacherId): array
    {
        $rows = Connection::fetchAll(
            'SELECT DISTINCT a.*
             FROM assessments a
             LEFT JOIN saber_assignments sa ON sa.id = a.saber_assignment_id
             LEFT JOIN units u ON u.id = a.unit_id
             WHERE sa.id IN (SELECT saber_assignment_id FROM assignment_teachers WHERE teacher_id = :t)
                OR u.saber_id IN (
                    SELECT sa2.saber_id FROM saber_assignments sa2
                    JOIN assignment_teachers at2 ON at2.saber_assignment_id = sa2.id
                    WHERE at2.teacher_id = :t
                )
             ORDER BY a.id DESC',
            [':t' => $teacherId]
        );
        return array_map([Assessment::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?Assessment
    {
        $row = Connection::fetch('SELECT * FROM assessments WHERE id = :id', [':id' => $id]);
        return $row === null ? null : Assessment::fromArray($row);
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO assessments (unit_id, saber_assignment_id, title, description, max_score, passing_score, max_attempts, time_limit, available_from, available_until, status)
             VALUES (:unit_id, :saber_assignment_id, :title, :description, :max_score, :passing_score, :max_attempts, :time_limit, :available_from, :available_until, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE assessments SET title = :title, description = :description, max_score = :max_score,
             passing_score = :passing_score, max_attempts = :max_attempts, time_limit = :time_limit,
             available_from = :available_from, available_until = :available_until, status = :status WHERE id = :id',
            $data
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        Connection::run('UPDATE assessments SET status = :s WHERE id = :id', [':s' => $status, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM assessments WHERE id = :id', [':id' => $id]);
    }

    // ===== Preguntas y opciones =====

    public function questions(int $assessmentId): array
    {
        $questions = Connection::fetchAll(
            'SELECT * FROM questions WHERE assessment_id = :a ORDER BY position ASC, id ASC',
            [':a' => $assessmentId]
        );
        foreach ($questions as &$q) {
            $q['options'] = Connection::fetchAll(
                'SELECT * FROM question_options WHERE question_id = :q ORDER BY position ASC, id ASC',
                [':q' => $q['id']]
            );
        }
        return $questions;
    }

    public function addQuestion(int $assessmentId, array $data, array $options): void
    {
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            $position = (int) (Connection::fetch(
                'SELECT COALESCE(MAX(position),0) AS m FROM questions WHERE assessment_id = :a',
                [':a' => $assessmentId]
            )['m'] ?? 0) + 1;
            $questionId = Connection::insert(
                'INSERT INTO questions (assessment_id, type, question_text, points, position) VALUES (:a, :type, :text, :points, :position)',
                [
                    ':a' => $assessmentId,
                    ':type' => $data['type'],
                    ':text' => $data['question_text'],
                    ':points' => $data['points'],
                    ':position' => $position,
                ]
            );
            $stmt = $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, position) VALUES (?, ?, ?, ?)');
            foreach ($options as $i => $opt) {
                $stmt->execute([$questionId, $opt['text'], $opt['correct'] ? 1 : 0, $i]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteQuestion(int $id): void
    {
        Connection::run('DELETE FROM questions WHERE id = :id', [':id' => $id]);
    }
}
