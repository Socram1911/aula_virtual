<?php

declare(strict_types=1);

namespace App\Modules\Activities\Services;

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Content\Models\ContentBlock;
use App\Modules\Content\Repositories\ContentBlockRepository;
use App\Modules\Grades\Services\GradeService;
use App\Modules\Progress\Services\ProgressService;

/**
 * Actividades interactivas: validación y corrección SERVIDOR (autoridad final).
 */
final class ActivityService
{
    public const TYPES = ['MULTIPLE_CHOICE', 'TRUE_FALSE', 'SINGLE_ANSWER'];

    private ContentBlockRepository $blocks;
    private ProgressService $progress;
    private GradeService $grades;

    public function __construct()
    {
        $this->blocks = new ContentBlockRepository();
        $this->progress = new ProgressService();
        $this->grades = new GradeService();
    }

    /** Valida la definición de una actividad. */
    public function validateSettings(array $settings): array
    {
        $type = strtoupper((string) ($settings['type'] ?? ''));
        $question = trim((string) ($settings['question'] ?? ''));
        $options = is_array($settings['options'] ?? null) ? array_values($settings['options']) : [];
        $classification = strtoupper((string) ($settings['classification'] ?? 'FORMATIVE'));

        $errors = [];
        if (! in_array($type, self::TYPES, true)) {
            $errors[] = 'Tipo de actividad inválido.';
        }
        if ($question === '') {
            $errors[] = 'La pregunta es obligatoria.';
        }
        if (count($options) < 2) {
            $errors[] = 'Debe definir al menos dos opciones.';
        }
        $correctCount = count(array_filter($options, fn ($o) => ! empty($o['correct'])));
        if ($type === 'SINGLE_ANSWER' || $type === 'TRUE_FALSE') {
            if ($correctCount !== 1) {
                $errors[] = 'Este tipo de actividad requiere exactamente una opción correcta.';
            }
        } elseif ($correctCount < 1) {
            $errors[] = 'Debe marcar al menos una opción correcta.';
        }
        if (! in_array($classification, ['FORMATIVE', 'GRADED'], true)) {
            $errors[] = 'Clasificación inválida.';
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija la actividad.', $errors);
        }

        return [
            'type' => $type,
            'question' => $question,
            'options' => array_map(fn ($o) => [
                'text' => trim((string) ($o['text'] ?? '')),
                'correct' => ! empty($o['correct']),
            ], $options),
            'classification' => $classification,
        ];
    }

    /**
     * Corrige una respuesta y devuelve el resultado (el servidor es la autoridad).
     */
    public function submit(int $studentId, int $blockId, mixed $answer): array
    {
        $block = $this->blocks->find($blockId);
        if ($block === null || $block->type !== 'ACTIVITY') {
            throw HttpException::notFound('Actividad no encontrada.');
        }
        $settings = $block->settingsArray();

        // Evita re-intentos de una actividad ya completada.
        $already = Connection::fetch(
            'SELECT id FROM student_block_progress WHERE student_id = :s AND block_id = :b AND status = :done',
            [':s' => $studentId, ':b' => $blockId, ':done' => 'COMPLETED']
        );
        if ($already !== null) {
            throw HttpException::conflict('Esta actividad ya fue completada.');
        }

        $type = (string) ($settings['type'] ?? '');
        $options = $settings['options'] ?? [];
        $isCorrect = $this->isCorrect($type, $options, $answer);

        $feedback = $isCorrect ? '¡Respuesta correcta!' : 'Respuesta incorrecta. Intente nuevamente.';

        if ($isCorrect) {
            $this->progress->markBlockCompleted($studentId, $blockId);

            $classification = strtoupper((string) ($settings['classification'] ?? 'FORMATIVE'));
            if ($classification === 'GRADED') {
                $this->recordGradedActivity($studentId, $block);
            }
        }

        return [
            'is_correct' => $isCorrect,
            'score' => $isCorrect ? 1 : 0,
            'feedback' => $feedback,
        ];
    }

    private function isCorrect(string $type, array $options, mixed $answer): bool
    {
        $correctIndexes = [];
        foreach ($options as $i => $o) {
            if (! empty($o['correct'])) {
                $correctIndexes[] = $i;
            }
        }

        if ($type === 'MULTIPLE_CHOICE') {
            $selected = is_array($answer) ? array_map('intval', $answer) : [];
            sort($selected);
            return $selected === $correctIndexes && $selected !== [];
        }

        // SINGLE_ANSWER y TRUE_FALSE: una sola selección.
        $index = (int) (is_array($answer) ? ($answer[0] ?? -1) : $answer);
        return $index === ($correctIndexes[0] ?? -1);
    }

    private function recordGradedActivity(int $studentId, ContentBlock $block): void
    {
        $unit = Connection::fetch('SELECT saber_id FROM units WHERE id = :id', [':id' => $block->unit_id]);
        if (! $unit) {
            return;
        }
        $assignments = $this->progress->assignmentsForSaber($studentId, (int) $unit['saber_id']);
        foreach ($assignments as $assignmentId) {
            $this->grades->record($assignmentId, $studentId, 'ACTIVITY', $block->id, 1, 100);
        }
    }
}
