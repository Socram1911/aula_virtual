<?php

declare(strict_types=1);

namespace App\Modules\Assessments\Services;

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Assessments\Models\Assessment;
use App\Modules\Assessments\Repositories\AssessmentRepository;
use App\Modules\Assessments\Repositories\AttemptRepository;
use App\Modules\Grades\Services\GradeService;
use App\Modules\Progress\Services\ProgressService;

/**
 * Intentos de evaluación y corrección automática (el servidor es la autoridad).
 */
final class AttemptService
{
    private AssessmentRepository $assessments;
    private AttemptRepository $attempts;
    private GradeService $grades;
    private ProgressService $progress;

    public function __construct()
    {
        $this->assessments = new AssessmentRepository();
        $this->attempts = new AttemptRepository();
        $this->grades = new GradeService();
        $this->progress = new ProgressService();
    }

    /** Verifica elegibilidad (publicada, ventana, límite de intentos) y arranca un intento. */
    public function start(int $studentId, int $assessmentId): array
    {
        $assessment = $this->eligible($studentId, $assessmentId);

        $count = $this->attempts->countAttempts($assessmentId, $studentId);
        if ($assessment->max_attempts !== null && $count >= $assessment->max_attempts) {
            throw HttpException::conflict('Ha alcanzado el número máximo de intentos.');
        }

        // Expira intentos viejos en curso si el tiempo se agotó.
        $last = $this->attempts->lastAttempt($assessmentId, $studentId);
        if ($last !== null && $last['status'] === 'IN_PROGRESS' && $assessment->time_limit !== null) {
            $elapsed = time() - strtotime((string) $last['started_at']);
            if ($elapsed > $assessment->time_limit) {
                $this->attempts->expire((int) $last['id']);
            }
        }

        $attemptId = $this->attempts->create($assessmentId, $studentId, $count + 1);
        return $this->payload($assessment, $attemptId, $count + 1);
    }

    /** Corrige y finaliza un intento. */
    public function submit(int $studentId, int $attemptId, array $answers): array
    {
        $attempt = $this->attempts->find($attemptId);
        if ($attempt === null || (int) $attempt['student_id'] !== $studentId) {
            throw HttpException::notFound('Intento no encontrado.');
        }
        if ($attempt['status'] !== 'IN_PROGRESS') {
            throw HttpException::conflict('Este intento ya fue finalizado.');
        }

        $assessment = $this->assessments->find((int) $attempt['assessment_id']);
        if ($assessment === null) {
            throw HttpException::notFound('Evaluación no encontrada.');
        }

        // Límite de tiempo.
        if ($assessment->time_limit !== null) {
            $elapsed = time() - strtotime((string) $attempt['started_at']);
            if ($elapsed > $assessment->time_limit) {
                $this->attempts->expire($attemptId);
                throw HttpException::conflict('Se agotó el tiempo límite para este intento.');
            }
        }

        $questions = $this->assessments->questions($assessment->id);
        $total = 0.0;
        $max = 0.0;

        foreach ($questions as $q) {
            $max += (float) $q['points'];
            $correct = $this->isCorrect($q, $answers[$q['id']] ?? null);
            $score = $correct ? (float) $q['points'] : 0.0;
            $total += $score;
            $this->attempts->saveAnswer($attemptId, (int) $q['id'], json_encode($answers[$q['id']] ?? null), $correct, $score);
        }

        $percentage = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;
        $passed = $percentage >= $this->passingPercentage($assessment);

        $this->attempts->complete($attemptId, $total, $percentage);

        // Registrar calificación de evaluación (mejor intento: se sobrescribe con el más reciente aquí).
        if ($assessment->saber_assignment_id !== null) {
            $this->grades->record((int) $assessment->saber_assignment_id, $studentId, 'ASSESSMENT', $attemptId, $total, $percentage);
            $this->grades->recordComposite((int) $assessment->saber_assignment_id, $studentId);
        }

        // Marcar progreso del bloque/actividad de evaluación si está asociada a una unidad.
        if ($assessment->unit_id !== null) {
            $this->markAssessmentBlockProgress($studentId, $assessment);
        }

        return [
            'attempt_id' => $attemptId,
            'status' => 'COMPLETED',
            'score' => $total,
            'max_score' => $max,
            'percentage' => $percentage,
            'passed' => $passed,
        ];
    }

    /** Devuelve preguntas sin revelar respuestas correctas. */
    private function payload(Assessment $assessment, int $attemptId, int $number): array
    {
        $questions = array_map(function ($q) {
            $q['options'] = array_map(fn ($o) => ['id' => $o['id'], 'option_text' => $o['option_text']], $q['options'] ?? []);
            return $q;
        }, $this->assessments->questions($assessment->id));

        return [
            'attempt_id' => $attemptId,
            'attempt_number' => $number,
            'started_at' => date('Y-m-d H:i:s'),
            'time_limit_seconds' => $assessment->time_limit,
            'questions' => $questions,
        ];
    }

    private function eligible(int $studentId, int $assessmentId): Assessment
    {
        $assessment = $this->assessments->find($assessmentId);
        if ($assessment === null || $assessment->status !== 'PUBLISHED') {
            throw HttpException::notFound('La evaluación no está disponible.');
        }

        $now = date('Y-m-d H:i:s');
        if ($assessment->available_from !== null && $now < $assessment->available_from) {
            throw HttpException::forbidden('La evaluación aún no está disponible.');
        }
        if ($assessment->available_until !== null && $now > $assessment->available_until) {
            throw HttpException::forbidden('La evaluación ya no está disponible.');
        }

        // Verifica que el estudiante tenga acceso a través de su sección.
        $hasAccess = false;
        if ($assessment->saber_assignment_id !== null) {
            $row = Connection::fetch(
                'SELECT sa.id FROM saber_assignments sa
                 JOIN section_students ss ON ss.section_id = sa.section_id AND ss.student_id = :s AND ss.status = :enr
                 WHERE sa.id = :a AND sa.status = :published',
                [':s' => $studentId, ':enr' => 'ENROLLED', ':a' => $assessment->saber_assignment_id, ':published' => 'PUBLISHED']
            );
            $hasAccess = $row !== null;
        } elseif ($assessment->unit_id !== null) {
            // Evaluación asociada a una unidad: acceso si la unidad está publicada y el
            // estudiante pertenece a una sección con el Saber de esa unidad asignado.
            $row = Connection::fetch(
                'SELECT u.id FROM units u
                 JOIN saber_assignments sa ON sa.saber_id = u.saber_id AND sa.status = :published
                 JOIN section_students ss ON ss.section_id = sa.section_id AND ss.student_id = :s AND ss.status = :enr
                 WHERE u.id = :uid AND u.status = :upub LIMIT 1',
                [
                    ':s' => $studentId,
                    ':enr' => 'ENROLLED',
                    ':published' => 'PUBLISHED',
                    ':uid' => $assessment->unit_id,
                    ':upub' => 'PUBLISHED',
                ]
            );
            $hasAccess = $row !== null;
        }

        if (! $hasAccess) {
            throw HttpException::forbidden('No tiene acceso a esta evaluación.');
        }

        return $assessment;
    }

    private function isCorrect(array $question, mixed $answer): bool
    {
        $correctIds = [];
        foreach (($question['options'] ?? []) as $o) {
            if (! empty($o['is_correct'])) {
                $correctIds[] = (int) $o['id'];
            }
        }
        sort($correctIds);

        $selected = is_array($answer) ? array_map('intval', $answer) : [];
        sort($selected);

        if ($question['type'] === 'MULTIPLE_CHOICE') {
            return $selected === $correctIds && $selected !== [];
        }
        return count($selected) === 1 && $selected[0] === ($correctIds[0] ?? -1);
    }

    private function passingPercentage(Assessment $assessment): float
    {
        return $assessment->max_score > 0 ? round(($assessment->passing_score / $assessment->max_score) * 100, 2) : 0.0;
    }

    private function markAssessmentBlockProgress(int $studentId, Assessment $assessment): void
    {
        $block = Connection::fetch(
            'SELECT id FROM content_blocks WHERE type = :t AND content = :unit',
            [':t' => 'ASSESSMENT', ':unit' => (string) $assessment->unit_id]
        );
        if ($block) {
            $this->progress->markBlockCompleted($studentId, (int) $block['id']);
        }
    }
}
