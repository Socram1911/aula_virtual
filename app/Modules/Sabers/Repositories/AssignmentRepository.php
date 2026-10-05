<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Repositories;

use App\Core\Database\Connection;
use App\Modules\Sabers\Models\SaberAssignment;

final class AssignmentRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll(
            'SELECT sa.*, s.name AS saber_name, sec.number AS section_number,
                    p.name AS period_name, t.name AS track_name
             FROM saber_assignments sa
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             JOIN academic_periods p ON p.id = sa.academic_period_id
             JOIN tracks t ON t.id = sec.track_id
             ORDER BY sa.id DESC'
        );
        return array_map([SaberAssignment::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?SaberAssignment
    {
        $row = Connection::fetch(
            'SELECT sa.*, s.name AS saber_name, sec.number AS section_number,
                    p.name AS period_name, t.name AS track_name
             FROM saber_assignments sa
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             JOIN academic_periods p ON p.id = sa.academic_period_id
             JOIN tracks t ON t.id = sec.track_id
             WHERE sa.id = :id',
            [':id' => $id]
        );
        return $row === null ? null : SaberAssignment::fromArray($row);
    }

    public function exists(int $saberId, int $sectionId, int $periodId, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM saber_assignments WHERE saber_id = :s AND section_id = :sec AND academic_period_id = :p';
        $params = [':s' => $saberId, ':sec' => $sectionId, ':p' => $periodId];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        return (int) (Connection::fetch($sql, $params)['c'] ?? 0) > 0;
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO saber_assignments (saber_id, section_id, academic_period_id, start_date, end_date, status)
             VALUES (:saber_id, :section_id, :academic_period_id, :start_date, :end_date, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        Connection::run(
            'UPDATE saber_assignments SET start_date = :start_date, end_date = :end_date, status = :status WHERE id = :id',
            [
                ':start_date' => $data[':start_date'] ?? null,
                ':end_date' => $data[':end_date'] ?? null,
                ':status' => $data[':status'] ?? 'DRAFT',
                ':id' => $id,
            ]
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        Connection::run('UPDATE saber_assignments SET status = :status WHERE id = :id', [':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM saber_assignments WHERE id = :id', [':id' => $id]);
    }

    /** Docentes de una asignación. */
    public function teachers(int $assignmentId): array
    {
        return Connection::fetchAll(
            'SELECT at.id, at.is_primary, u.id AS teacher_id, u.first_name, u.last_name, u.email
             FROM assignment_teachers at
             JOIN users u ON u.id = at.teacher_id
             WHERE at.saber_assignment_id = :aid
             ORDER BY at.is_primary DESC, u.last_name ASC',
            [':aid' => $assignmentId]
        );
    }

    public function syncTeachers(int $assignmentId, array $teachers): void
    {
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            Connection::run('DELETE FROM assignment_teachers WHERE saber_assignment_id = :aid', [':aid' => $assignmentId]);
            $stmt = $pdo->prepare(
                'INSERT INTO assignment_teachers (saber_assignment_id, teacher_id, is_primary) VALUES (?, ?, ?)'
            );
            foreach ($teachers as $t) {
                $stmt->execute([$assignmentId, (int) $t['teacher_id'], $t['is_primary'] ? 1 : 0]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Asignaciones de un estudiante (vía su sección activa). */
    public function forStudent(int $studentId): array
    {
        $rows = Connection::fetchAll(
            'SELECT sa.*, s.name AS saber_name, sec.number AS section_number, p.name AS period_name
             FROM saber_assignments sa
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             JOIN academic_periods p ON p.id = sa.academic_period_id
             JOIN section_students ss ON ss.section_id = sa.section_id AND ss.student_id = :st AND ss.status = :enr
             WHERE sa.status = :published
             ORDER BY s.name ASC',
            [':st' => $studentId, ':enr' => 'ENROLLED', ':published' => 'PUBLISHED']
        );
        return array_map([SaberAssignment::class, 'fromArray'], $rows);
    }

    /** ¿El docente está asignado a algún grupo de este Saber? */
    public function teacherHasSaber(int $teacherId, int $saberId): bool
    {
        return Connection::fetch(
            'SELECT sa.id FROM saber_assignments sa
             JOIN assignment_teachers at ON at.saber_assignment_id = sa.id
             WHERE at.teacher_id = :t AND sa.saber_id = :s LIMIT 1',
            [':t' => $teacherId, ':s' => $saberId]
        ) !== null;
    }

    /** Asignaciones visibles para un docente. */
    public function forTeacher(int $teacherId): array
    {
        $rows = Connection::fetchAll(
            'SELECT sa.*, s.name AS saber_name, sec.number AS section_number, p.name AS period_name
             FROM saber_assignments sa
             JOIN sabers s ON s.id = sa.saber_id
             JOIN sections sec ON sec.id = sa.section_id
             JOIN academic_periods p ON p.id = sa.academic_period_id
             JOIN assignment_teachers at ON at.saber_assignment_id = sa.id AND at.teacher_id = :tid
             ORDER BY sa.id DESC',
            [':tid' => $teacherId]
        );
        return array_map([SaberAssignment::class, 'fromArray'], $rows);
    }
}
