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
     * Calcula la calificación compuesta usando los componentes configurados.
     * Si hay componentes por contenido H5P (block_id), promedia ponderado por
     * contenido; si hay componentes por tipo, promedia por tipo; sin nada,
     * promedia las evaluaciones. El resultado es un porcentaje (0-100).
     */
    public function computeComposite(int $saberAssignmentId, int $studentId): float
    {
        $components = Connection::fetchAll(
            'SELECT source_type, weight, block_id FROM grade_components WHERE saber_assignment_id = :a ORDER BY position ASC',
            [':a' => $saberAssignmentId]
        );

        if ($components) {
            $hasBlocks = false;
            foreach ($components as $c) {
                if (! empty($c['block_id'])) {
                    $hasBlocks = true;
                    break;
                }
            }

            if ($hasBlocks) {
                // Ponderado por contenido evaluable: solo cuenta lo ya calificado.
                $total = 0.0;
                $weightSum = 0.0;
                foreach ($components as $c) {
                    if (empty($c['block_id'])) {
                        continue;
                    }
                    $pct = $this->percentageForBlock($saberAssignmentId, $studentId, (int) $c['block_id']);
                    if ($pct === null) {
                        continue;
                    }
                    $weight = (float) $c['weight'];
                    $total += $pct * ($weight / 100);
                    $weightSum += $weight;
                }
                return $weightSum > 0 ? round($total / ($weightSum / 100), 2) : 0.0;
            }

            $total = 0.0;
            foreach ($components as $c) {
                $sourceType = match ($c['source_type']) {
                    'ACTIVITIES' => 'ACTIVITY',
                    'ASSESSMENTS' => 'ASSESSMENT',
                    'H5P' => 'H5P',
                    default => 'PROJECT',
                };
                $avg = $this->averagePercentage($saberAssignmentId, $studentId, $sourceType);
                $total += $avg * ((float) $c['weight'] / 100);
            }
            return round($total, 2);
        }

        // Sin configuración: promedio de evaluaciones.
        return $this->averagePercentage($saberAssignmentId, $studentId, 'ASSESSMENT');
    }

    /** Porcentaje (0-100) de un estudiante en un contenido evaluable, o null si aún no hay nota. */
    public function percentageForBlock(int $assignmentId, int $studentId, int $blockId): ?float
    {
        $row = Connection::fetch(
            'SELECT percentage FROM grades
             WHERE saber_assignment_id = :a AND student_id = :s AND source_type = :h5p AND source_id = :b',
            [':a' => $assignmentId, ':s' => $studentId, ':h5p' => 'H5P', ':b' => $blockId]
        );
        if ($row === null || $row['percentage'] === null) {
            return null;
        }
        return (float) $row['percentage'];
    }

    /** Convierte un porcentaje (0-100) a nota sobre 20. */
    public function to20(float $percentage): float
    {
        return round($percentage / 5, 2);
    }

    /** Indica si una nota sobre 20 aprueba (umbral 12). */
    public static function passes(float $note20): bool
    {
        return $note20 >= 12.0;
    }

    /** Calcula y guarda la calificación compuesta de un estudiante en una asignación. */
    public function recordComposite(int $saberAssignmentId, int $studentId): float
    {
        $percentage = $this->computeComposite($saberAssignmentId, $studentId);
        $this->record($saberAssignmentId, $studentId, 'COMPOSITE', null, $percentage, $percentage);
        return $percentage;
    }

    /** Registra el resultado de un bloque H5P (fuente H5P). */
    public function recordH5p(int $saberAssignmentId, int $studentId, int $blockId, float $score, float $percentage): void
    {
        $this->record($saberAssignmentId, $studentId, 'H5P', $blockId, $score, $percentage);
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
                'INSERT INTO grade_components (saber_assignment_id, name, source_type, weight, position, block_id) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $position = 0;
            foreach ($components as $c) {
                $name = trim((string) ($c['name'] ?? ''));
                $type = strtoupper((string) ($c['source_type'] ?? 'H5P'));
                $weight = (float) ($c['weight'] ?? 0);
                $blockId = ! empty($c['block_id']) ? (int) $c['block_id'] : null;
                if ($name !== '' && $weight > 0) {
                    $stmt->execute([$assignmentId, $name, $type, $weight, $position++, $blockId]);
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
