<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Assessments\Models\Assessment;
use App\Modules\Assessments\Repositories\AssessmentRepository;
use App\Modules\Audit\Services\AuditService;

final class AssessmentService
{
    public const TYPES = ['MULTIPLE_CHOICE', 'TRUE_FALSE', 'SINGLE_ANSWER'];

    private AssessmentRepository $repository;

    public function __construct()
    {
        $this->repository = new AssessmentRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): Assessment
    {
        $assessment = $this->repository->find($id);
        if ($assessment === null) {
            throw HttpException::notFound('Evaluación no encontrada.');
        }
        return $assessment;
    }

    public function questions(int $id): array
    {
        $this->find($id);
        return $this->repository->questions($id);
    }

    public function create(array $input): Assessment
    {
        $data = $this->validate($input);
        $id = $this->repository->create($data);
        AuditService::log('crear Evaluación', 'Assessment', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): Assessment
    {
        $this->find($id);
        $data = $this->validate($input);
        $this->repository->update($id, $data);
        AuditService::log('modificar Evaluación', 'Assessment', $id, null, $data);
        return $this->find($id);
    }

    public function setStatus(int $id, string $status): Assessment
    {
        $assessment = $this->find($id);
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'CLOSED'], true)) {
            throw HttpException::unprocessable('Estado de evaluación inválido.');
        }
        $this->repository->updateStatus($id, $status);
        AuditService::log('publicar Evaluación', 'Assessment', $id, ['status' => $assessment->status], ['status' => $status]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $assessment = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Evaluación', 'Assessment', $id, ['title' => $assessment->title], null);
    }

    public function addQuestion(int $assessmentId, array $input): void
    {
        $this->find($assessmentId);
        $type = strtoupper((string) ($input['type'] ?? ''));
        $text = trim((string) ($input['question_text'] ?? ''));
        $points = (float) ($input['points'] ?? 1);
        $options = is_array($input['options'] ?? null) ? array_values($input['options']) : [];

        $errors = [];
        if (! in_array($type, self::TYPES, true)) {
            $errors[] = 'Tipo de pregunta inválido.';
        }
        if ($text === '') {
            $errors[] = 'El texto de la pregunta es obligatorio.';
        }
        if ($points < 0) {
            $errors[] = 'Los puntos deben ser un número positivo o cero.';
        }
        $cleanOptions = [];
        foreach ($options as $o) {
            $textOpt = trim((string) ($o['text'] ?? ''));
            if ($textOpt !== '') {
                $cleanOptions[] = ['text' => $textOpt, 'correct' => ! empty($o['correct'])];
            }
        }
        if (count($cleanOptions) < 2) {
            $errors[] = 'Debe definir al menos dos opciones.';
        }
        $correctCount = count(array_filter($cleanOptions, fn ($o) => $o['correct']));
        if (($type === 'SINGLE_ANSWER' || $type === 'TRUE_FALSE') && $correctCount !== 1) {
            $errors[] = 'Este tipo de pregunta requiere exactamente una opción correcta.';
        }
        if ($type === 'MULTIPLE_CHOICE' && $correctCount < 1) {
            $errors[] = 'Debe marcar al menos una opción correcta.';
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija la pregunta.', $errors);
        }

        $this->repository->addQuestion($assessmentId, [
            'type' => $type,
            'question_text' => $text,
            'points' => $points,
        ], $cleanOptions);
    }

    public function deleteQuestion(int $id): void
    {
        $this->repository->deleteQuestion($id);
    }

    private function validate(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $maxScore = (float) ($input['max_score'] ?? 10);
        $passingScore = (float) ($input['passing_score'] ?? 6);
        $maxAttempts = isset($input['max_attempts']) && $input['max_attempts'] !== '' && $input['max_attempts'] !== null ? (int) $input['max_attempts'] : null;
        $minutes = isset($input['time_limit_minutes']) && $input['time_limit_minutes'] !== '' ? (int) $input['time_limit_minutes'] : 0;
        $timeLimit = $minutes > 0 ? $minutes * 60 : null;
        $availableFrom = $input['available_from'] ?? '';
        $availableUntil = $input['available_until'] ?? '';
        $status = (string) ($input['status'] ?? 'DRAFT');
        $unitId = (int) ($input['unit_id'] ?? 0);
        $assignmentId = (int) ($input['saber_assignment_id'] ?? 0);

        $errors = [];
        if ($title === '') {
            $errors[] = 'El título es obligatorio.';
        }
        if ($maxScore <= 0) {
            $errors[] = 'El puntaje máximo debe ser mayor que cero.';
        }
        if ($passingScore > $maxScore) {
            $errors[] = 'El puntaje de aprobación no puede superar el máximo.';
        }
        if (($unitId > 0 && $assignmentId > 0) || ($unitId <= 0 && $assignmentId <= 0)) {
            $errors[] = 'Asocie la evaluación a una unidad O a una asignación de Saber (solo una).';
        }
        if ($availableFrom !== '' && $availableUntil !== '' && $availableUntil < $availableFrom) {
            $errors[] = 'La fecha de cierre no puede ser anterior a la de apertura.';
        }
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'CLOSED'], true)) {
            $errors[] = 'Estado inválido.';
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':unit_id' => $unitId > 0 ? $unitId : null,
            ':saber_assignment_id' => $assignmentId > 0 ? $assignmentId : null,
            ':title' => $title,
            ':description' => $description,
            ':max_score' => $maxScore,
            ':passing_score' => $passingScore,
            ':max_attempts' => $maxAttempts,
            ':time_limit' => $timeLimit,
            ':available_from' => $availableFrom !== '' ? $availableFrom : null,
            ':available_until' => $availableUntil !== '' ? $availableUntil : null,
            ':status' => $status,
        ];
    }
}
