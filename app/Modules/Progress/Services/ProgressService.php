<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Core\Database\Connection;

/**
 * Cálculo y registro de progreso a nivel de bloque, unidad y saber.
 */
final class ProgressService
{
    /** Marca un bloque como completado y recalcula unidad y saber. */
    public function markBlockCompleted(int $studentId, int $blockId): void
    {
        Connection::run(
            'INSERT INTO student_block_progress (student_id, block_id, status, completed_at)
             VALUES (:s, :b, :status, :at)
             ON DUPLICATE KEY UPDATE status = :status, completed_at = :at',
            [':s' => $studentId, ':b' => $blockId, ':status' => 'COMPLETED', ':at' => date('Y-m-d H:i:s')]
        );

        $block = Connection::fetch('SELECT unit_id FROM content_blocks WHERE id = :id', [':id' => $blockId]);
        if ($block) {
            $this->updateUnitProgress($studentId, (int) $block['unit_id']);
        }
    }

    /** Recalcula el progreso de una unidad a partir de sus bloques activos. */
    public function updateUnitProgress(int $studentId, int $unitId): void
    {
        $total = (int) (Connection::fetch(
            'SELECT COUNT(*) AS c FROM content_blocks WHERE unit_id = :u AND status = :active',
            [':u' => $unitId, ':active' => 'ACTIVE']
        )['c'] ?? 0);

        $completed = (int) (Connection::fetch(
            'SELECT COUNT(*) AS c FROM student_block_progress sbp
             JOIN content_blocks cb ON cb.id = sbp.block_id
             WHERE sbp.student_id = :s AND cb.unit_id = :u AND sbp.status = :done AND cb.status = :active',
            [':s' => $studentId, ':u' => $unitId, ':done' => 'COMPLETED', ':active' => 'ACTIVE']
        )['c'] ?? 0);

        $percentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0.00;
        $status = $total > 0 && $completed >= $total ? 'COMPLETED' : ($completed > 0 ? 'IN_PROGRESS' : 'NOT_STARTED');

        Connection::run(
            'INSERT INTO student_unit_progress (student_id, unit_id, percentage, completed_blocks, status)
             VALUES (:s, :u, :p, :cb, :st)
             ON DUPLICATE KEY UPDATE percentage = :p, completed_blocks = :cb, status = :st',
            [':s' => $studentId, ':u' => $unitId, ':p' => $percentage, ':cb' => $completed, ':st' => $status]
        );

        $unit = Connection::fetch('SELECT saber_id FROM units WHERE id = :id', [':id' => $unitId]);
        if ($unit) {
            $this->updateSaberProgress($studentId, (int) $unit['saber_id']);
        }
    }

    /** Recalcula el progreso de un saber (promedio de sus unidades) para las asignaciones del estudiante. */
    public function updateSaberProgress(int $studentId, int $saberId): void
    {
        $assignments = $this->assignmentsForSaber($studentId, $saberId);

        $rows = Connection::fetchAll(
            'SELECT sup.percentage, sup.status FROM student_unit_progress sup
             JOIN units u ON u.id = sup.unit_id
             WHERE sup.student_id = :s AND u.saber_id = :saber',
            [':s' => $studentId, ':saber' => $saberId]
        );

        $unitCount = count($rows);
        $percentage = $unitCount > 0 ? round(array_sum(array_column($rows, 'percentage')) / $unitCount, 2) : 0.00;
        $completedUnits = count(array_filter($rows, fn ($r) => $r['status'] === 'COMPLETED'));

        foreach ($assignments as $assignmentId) {
            $status = $unitCount > 0 && $completedUnits >= $unitCount ? 'COMPLETED' : ($completedUnits > 0 ? 'IN_PROGRESS' : 'NOT_STARTED');
            Connection::run(
                'INSERT INTO student_saber_progress (student_id, saber_assignment_id, percentage, completed_units, status)
                 VALUES (:s, :a, :p, :cu, :st)
                 ON DUPLICATE KEY UPDATE percentage = :p, completed_units = :cu, status = :st',
                [':s' => $studentId, ':a' => $assignmentId, ':p' => $percentage, ':cu' => $completedUnits, ':st' => $status]
            );
        }
    }

    /** Asignaciones de un estudiante para un saber (vía sus secciones). */
    public function assignmentsForSaber(int $studentId, int $saberId): array
    {
        return array_map('intval', array_column(Connection::fetchAll(
            'SELECT DISTINCT sa.id
             FROM saber_assignments sa
             JOIN section_students ss ON ss.section_id = sa.section_id AND ss.student_id = :s AND ss.status = :enr
             WHERE sa.saber_id = :saber',
            [':s' => $studentId, ':enr' => 'ENROLLED', ':saber' => $saberId]
        ), 'id'));
    }

    /** Resumen de progreso del estudiante por asignación (para la API). */
    public function summary(int $studentId): array
    {
        return Connection::fetchAll(
            'SELECT ssp.saber_assignment_id, s.name AS saber_name, ssp.percentage,
                    sa.saber_id, sec.number AS section_number
             FROM student_saber_progress ssp
             JOIN saber_assignments sa ON sa.id = ssp.saber_assignment_id
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             WHERE ssp.student_id = :s
             ORDER BY s.name ASC',
            [':s' => $studentId]
        );
    }
}
