<?php

declare(strict_types=1);

namespace App\Modules\Progress\Services;

use App\Core\Database\Connection;

/**
 * Cálculo y registro de progreso a nivel de bloque, unidad y saber.
 */
final class ProgressService
{
    /** Marca un bloque como completado, registra el evento y recalcula unidad y saber. */
    public function markBlockCompleted(int $studentId, int $blockId): void
    {
        Connection::run(
            'INSERT INTO student_block_progress (student_id, block_id, status, completed_at, viewed_at)
             VALUES (:s, :b, :status, :at, :at)'
            . Connection::onConflict('student_id, block_id')
            . 'status = :status, completed_at = COALESCE(student_block_progress.completed_at, :at), viewed_at = COALESCE(student_block_progress.viewed_at, :at)',
            [':s' => $studentId, ':b' => $blockId, ':status' => 'COMPLETED', ':at' => date('Y-m-d H:i:s')]
        );

        $this->logEvent($studentId, $blockId, 'COMPLETED', null);

        $block = Connection::fetch('SELECT unit_id FROM content_blocks WHERE id = :id', [':id' => $blockId]);
        if ($block) {
            $this->updateUnitProgress($studentId, (int) $block['unit_id']);
        }
    }

    /** Registra que el estudiante vio un bloque (primera vez) y lo marca como EN PROGRESO. */
    public function recordView(int $studentId, int $blockId): void
    {
        $already = Connection::fetch(
            'SELECT id FROM content_view_logs WHERE student_id = :s AND block_id = :b AND action = :a LIMIT 1',
            [':s' => $studentId, ':b' => $blockId, ':a' => 'VIEWED']
        );
        if ($already === null) {
            $this->logEvent($studentId, $blockId, 'VIEWED', null);
        }

        Connection::run(
            'INSERT INTO student_block_progress (student_id, block_id, status, viewed_at)
             VALUES (:s, :b, :st, :at)'
            . Connection::onConflict('student_id, block_id')
            . 'viewed_at = COALESCE(student_block_progress.viewed_at, :at), status = CASE WHEN student_block_progress.status = :notstarted THEN :st ELSE student_block_progress.status END',
            [
                ':s' => $studentId,
                ':b' => $blockId,
                ':st' => 'IN_PROGRESS',
                ':notstarted' => 'NOT_STARTED',
                ':at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** Registra el avance de un video (porcentaje visto). Si supera el 90%, se marca completado. */
    public function recordVideoProgress(int $studentId, int $blockId, float $percent): void
    {
        $percent = max(0.0, min(100.0, $percent));

        Connection::run(
            'INSERT INTO student_block_progress (student_id, block_id, status, video_percent, viewed_at)
             VALUES (:s, :b, :st, :p, :at)'
            . Connection::onConflict('student_id, block_id')
            . 'video_percent = GREATEST(COALESCE(student_block_progress.video_percent, 0), :p), viewed_at = COALESCE(student_block_progress.viewed_at, :at), status = CASE WHEN student_block_progress.status = :notstarted THEN :st ELSE student_block_progress.status END',
            [
                ':s' => $studentId,
                ':b' => $blockId,
                ':st' => 'IN_PROGRESS',
                ':notstarted' => 'NOT_STARTED',
                ':p' => $percent,
                ':at' => date('Y-m-d H:i:s'),
            ]
        );

        // Hitos: solo se registran avances significativos para no saturar la bitácora.
        $milestone = (int) $percent;
        if (in_array($milestone, [25, 50, 75, 100], true)) {
            $this->logEvent($studentId, $blockId, 'VIDEO_PROGRESS', ['percent' => $milestone]);
        }

        if ($percent >= 90.0) {
            $this->markBlockCompleted($studentId, $blockId);
        }
    }

    /** Inserta un evento en la bitácora de visualización (evitando duplicados de COMPLETED). */
    private function logEvent(int $studentId, int $blockId, string $action, ?array $meta): void
    {
        if ($action === 'COMPLETED') {
            $already = Connection::fetch(
                'SELECT id FROM content_view_logs WHERE student_id = :s AND block_id = :b AND action = :a LIMIT 1',
                [':s' => $studentId, ':b' => $blockId, ':a' => 'COMPLETED']
            );
            if ($already !== null) {
                return;
            }
        }
        Connection::run(
            'INSERT INTO content_view_logs (student_id, block_id, action, meta) VALUES (:s, :b, :a, :m)',
            [
                ':s' => $studentId,
                ':b' => $blockId,
                ':a' => $action,
                ':m' => $meta === null ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]
        );
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
             VALUES (:s, :u, :p, :cb, :st)'
            . Connection::onConflict('student_id, unit_id')
            . 'percentage = :p, completed_blocks = :cb, status = :st',
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
                 VALUES (:s, :a, :p, :cu, :st)'
                . Connection::onConflict('student_id, saber_assignment_id')
                . 'percentage = :p, completed_units = :cu, status = :st',
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
