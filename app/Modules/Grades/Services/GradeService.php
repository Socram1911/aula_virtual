<?php

declare(strict_types=1);

namespace App\Modules\Grades\Services;

use App\Core\Database\Connection;

/**
 * Registro y cálculo de calificaciones por asignación de Saber.
 */
final class GradeService
{
    /**
     * Registra (o actualiza) una calificación de un componente o compuesta.
     */
    public function record(
        int $saberAssignmentId,
        int $studentId,
        string $sourceType,
        ?int $sourceId,
        float $score,
        float $percentage,
        ?int $gradedBy = null,
        ?string $comments = null
    ): void {
        $gradeValue = number_format($percentage, 2, '.', '');

        if ($sourceId === null) {
            // Fuentes compuestas/manuales: reemplaza la existente.
            Connection::run(
                'DELETE FROM grades WHERE saber_assignment_id = :a AND student_id = :s AND source_type = :st AND source_id IS NULL',
                [':a' => $saberAssignmentId, ':s' => $studentId, ':st' => $sourceType]
            );
            Connection::run(
                'INSERT INTO grades (saber_assignment_id, student_id, source_type, source_id, score, percentage, grade_value, comments, graded_by, graded_at)
                 VALUES (:a, :s, :st, NULL, :score, :pct, :gv, :c, :gb, :at)',
                [
                    ':a' => $saberAssignmentId, ':s' => $studentId, ':st' => $sourceType,
                    ':score' => $score, ':pct' => $percentage, ':gv' => $gradeValue,
                    ':c' => $comments, ':gb' => $gradedBy, ':at' => date('Y-m-d H:i:s'),
                ]
            );
            return;
        }

        Connection::run(
            'INSERT INTO grades (saber_assignment_id, student_id, source_type, source_id, score, percentage, grade_value, comments, graded_by, graded_at)
             VALUES (:a, :s, :st, :sid, :score, :pct, :gv, :c, :gb, :at)'
            . Connection::onConflict('saber_assignment_id, student_id, source_type, source_id')
            . 'score = :score, percentage = :pct, grade_value = :gv, graded_at = :at',
            [
                ':a' => $saberAssignmentId, ':s' => $studentId, ':st' => $sourceType, ':sid' => $sourceId,
                ':score' => $score, ':pct' => $percentage, ':gv' => $gradeValue,
                ':c' => $comments, ':gb' => $gradedBy, ':at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Calcula la calificación compuesta de una asignación para un estudiante.
     *
     * Con evaluaciones H5P (block_id): devuelve [puntos obtenidos, puntos totales]
     * (suma directa de los valores de cada evaluación). Sin ellas, mantiene el
     * comportamiento por pesos/tipos devolviendo [porcentaje, 100].
     *
     * @return array{0: float, 1: float} [obtenido, total]
     */
    public function computeComposite(int $saberAssignmentId, int $studentId): array
    {
        $components = Connection::fetchAll(
            'SELECT source_type, weight, value, block_id FROM grade_components WHERE saber_assignment_id = :a ORDER BY position ASC',
            [':a' => $saberAssignmentId]
        );

        $hasBlocks = false;
        foreach ($components as $c) {
            if (! empty($c['block_id'])) {
                $hasBlocks = true;
                break;
            }
        }

        if ($hasBlocks) {
            // Modelo de puntos: la nota final es la suma de las notas de cada
            // evaluación H5P, y el total es la suma de sus valores.
            $total = 0.0;
            $obtained = 0.0;
            foreach ($components as $c) {
                if (empty($c['block_id'])) {
                    continue;
                }
                $value = (float) $c['value'];
                $total += $value;
                $score = $this->scoreForBlock($saberAssignmentId, $studentId, (int) $c['block_id']);
                if ($score !== null) {
                    $obtained += $value > 0 ? min($score, $value) : $score;
                }
            }
            return [round($obtained, 2), round($total, 2)];
        }

        if ($components) {
            // Fallback: componentes por tipo con pesos (porcentaje).
            $pct = 0.0;
            foreach ($components as $c) {
                $sourceType = match ($c['source_type']) {
                    'ACTIVITIES' => 'ACTIVITY',
                    'ASSESSMENTS' => 'ASSESSMENT',
                    'H5P' => 'H5P',
                    default => 'PROJECT',
                };
                $avg = $this->averagePercentage($saberAssignmentId, $studentId, $sourceType);
                $pct += $avg * ((float) $c['weight'] / 100);
            }
            return [round($pct, 2), 100.0];
        }

        // Sin configuración: promedio de evaluaciones.
        return [$this->averagePercentage($saberAssignmentId, $studentId, 'ASSESSMENT'), 100.0];
    }

    /** Puntos obtenidos (0..valor) de un estudiante en un contenido evaluable, o null. */
    public function scoreForBlock(int $assignmentId, int $studentId, int $blockId): ?float
    {
        $row = Connection::fetch(
            'SELECT score FROM grades
             WHERE saber_assignment_id = :a AND student_id = :s AND source_type = :h5p AND source_id = :b',
            [':a' => $assignmentId, ':s' => $studentId, ':h5p' => 'H5P', ':b' => $blockId]
        );
        if ($row === null || $row['score'] === null) {
            return null;
        }
        return (float) $row['score'];
    }

    /** Valor en puntos asignado a un contenido evaluable en una asignación, o null. */
    public function componentValue(int $assignmentId, int $blockId): ?float
    {
        $row = Connection::fetch(
            'SELECT value FROM grade_components WHERE saber_assignment_id = :a AND block_id = :b',
            [':a' => $assignmentId, ':b' => $blockId]
        );
        if ($row === null || $row['value'] === null) {
            return null;
        }
        return (float) $row['value'];
    }

    /** Indica si una nota aprueba (umbral 12). */
    public static function passes(float $note): bool
    {
        return $note >= 12.0;
    }

    /** Calcula y guarda la calificación compuesta de un estudiante en una asignación. */
    public function recordComposite(int $saberAssignmentId, int $studentId): float
    {
        [$obtained, $total] = $this->computeComposite($saberAssignmentId, $studentId);
        $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0.0;
        $this->record($saberAssignmentId, $studentId, 'COMPOSITE', null, $obtained, $percentage);
        return $obtained;
    }

    /** Registra la nota (puntos) de un bloque H5P para una asignación. */
    public function recordH5p(int $saberAssignmentId, int $studentId, int $blockId, float $grade): void
    {
        $value = $this->componentValue($saberAssignmentId, $blockId);
        $percentage = ($value !== null && $value > 0) ? round(($grade / $value) * 100, 2) : 0.0;
        $this->record($saberAssignmentId, $studentId, 'H5P', $blockId, $grade, $percentage);
    }

    private function averagePercentage(int $assignmentId, int $studentId, string $sourceType): float
    {
        $row = Connection::fetch(
            'SELECT AVG(percentage) AS pct FROM grades
             WHERE saber_assignment_id = :a AND student_id = :s AND source_type = :st',
            [':a' => $assignmentId, ':s' => $studentId, ':st' => $sourceType]
        );
        return round((float) ($row['pct'] ?? 0), 2);
    }

    /** Calificaciones de un estudiante (por asignación), incluyendo compuesta. */
    public function gradesForStudent(int $studentId): array
    {
        return Connection::fetchAll(
            'SELECT g.*, s.name AS saber_name, sec.number AS section_number
             FROM grades g
             JOIN saber_assignments sa ON sa.id = g.saber_assignment_id
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             WHERE g.student_id = :s
             ORDER BY s.name ASC, g.graded_at DESC',
            [':s' => $studentId]
        );
    }

    /** Todas las calificaciones compuestas con datos de estudiante/saber/sección. */
    public function allComposite(): array
    {
        return Connection::fetchAll(
            'SELECT g.*, u.first_name, u.last_name, s.name AS saber_name, sec.number AS section_number
             FROM grades g
             JOIN users u ON u.id = g.student_id
             JOIN saber_assignments sa ON sa.id = g.saber_assignment_id
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             WHERE g.source_type = :comp AND g.source_id IS NULL
             ORDER BY g.graded_at DESC',
            [':comp' => 'COMPOSITE']
        );
    }

    /** Componentes de calificación configurados para una asignación. */
    public function components(int $assignmentId): array
    {
        return Connection::fetchAll(
            'SELECT * FROM grade_components WHERE saber_assignment_id = :a ORDER BY position ASC, id ASC',
            [':a' => $assignmentId]
        );
    }

    /** Reemplaza los componentes de calificación de una asignación. */
    public function saveComponents(int $assignmentId, array $components): void
    {
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            Connection::run('DELETE FROM grade_components WHERE saber_assignment_id = :a', [':a' => $assignmentId]);
            $stmt = $pdo->prepare(
                'INSERT INTO grade_components (saber_assignment_id, name, source_type, value, position, block_id) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $position = 0;
            foreach ($components as $c) {
                $name = trim((string) ($c['name'] ?? ''));
                $type = strtoupper((string) ($c['source_type'] ?? 'H5P'));
                $value = (float) ($c['value'] ?? 0);
                $blockId = ! empty($c['block_id']) ? (int) $c['block_id'] : null;
                if ($name !== '' && $value > 0) {
                    $stmt->execute([$assignmentId, $name, $type, $value, $position++, $blockId]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Progreso y calificación compuesta de cada estudiante de una asignación. */
    public function studentsForAssignment(int $assignmentId): array
    {
        return Connection::fetchAll(
            'SELECT u.id AS student_id, u.first_name, u.last_name, u.email,
                    COALESCE(ssp.percentage, 0) AS progress,
                    (SELECT score FROM grades g WHERE g.saber_assignment_id = :a AND g.student_id = u.id AND g.source_type = :comp AND g.source_id IS NULL) AS grade_score,
                    (SELECT percentage FROM grades g WHERE g.saber_assignment_id = :a AND g.student_id = u.id AND g.source_type = :comp AND g.source_id IS NULL) AS grade_pct,
                    (SELECT grade_value FROM grades g WHERE g.saber_assignment_id = :a AND g.student_id = u.id AND g.source_type = :comp AND g.source_id IS NULL) AS grade_value
             FROM section_students ss
             JOIN users u ON u.id = ss.student_id
             LEFT JOIN student_saber_progress ssp ON ssp.student_id = u.id AND ssp.saber_assignment_id = :a
             WHERE ss.section_id = (SELECT section_id FROM saber_assignments WHERE id = :a) AND ss.status = :enr
             ORDER BY u.last_name ASC',
            [':a' => $assignmentId, ':comp' => 'COMPOSITE', ':enr' => 'ENROLLED']
        );
    }
}
