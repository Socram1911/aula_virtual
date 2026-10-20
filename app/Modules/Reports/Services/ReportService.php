<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Core\Database\Connection;

final class ReportService
{
    public function studentsPerSection(): array
    {
        return Connection::fetchAll(
            'SELECT p.name AS period_name, t.name AS track_name, s.number, COUNT(ss.id) AS total
             FROM sections s
             JOIN academic_periods p ON p.id = s.academic_period_id
             JOIN tracks t ON t.id = s.track_id
             LEFT JOIN section_students ss ON ss.section_id = s.id AND ss.status = :enr
             GROUP BY s.id, p.name, t.name, s.number
             ORDER BY p.id DESC, t.position, s.number',
            [':enr' => 'ENROLLED']
        );
    }

    public function studentsPerTrack(): array
    {
        return Connection::fetchAll(
            'SELECT t.name AS track_name, COUNT(DISTINCT ss.student_id) AS total
             FROM tracks t
             JOIN sections s ON s.track_id = t.id
             JOIN section_students ss ON ss.section_id = s.id AND ss.status = :enr
             GROUP BY t.id, t.name
             ORDER BY t.position',
            [':enr' => 'ENROLLED']
        );
    }

    public function sabersPerTrack(): array
    {
        return Connection::fetchAll(
            'SELECT t.name AS track_name, COUNT(s.id) AS total
             FROM tracks t LEFT JOIN sabers s ON s.track_id = t.id
             GROUP BY t.id, t.name ORDER BY t.position'
        );
    }

    public function teachersPerSaber(): array
    {
        return Connection::fetchAll(
            'SELECT s.name AS saber_name, COUNT(DISTINCT at.teacher_id) AS total
             FROM sabers s
             JOIN saber_assignments sa ON sa.saber_id = s.id
             JOIN assignment_teachers at ON at.saber_assignment_id = sa.id
             GROUP BY s.id, s.name ORDER BY s.name'
        );
    }

    public function academicResults(): array
    {
        return Connection::fetchAll(
            'SELECT u.last_name, u.first_name, s.name AS saber_name, g.percentage, g.grade_value
             FROM grades g
             JOIN users u ON u.id = g.student_id
             JOIN saber_assignments sa ON sa.id = g.saber_assignment_id
             JOIN sabers s ON s.id = sa.saber_id
             WHERE g.source_type = :comp AND g.source_id IS NULL
             ORDER BY g.graded_at DESC LIMIT 200',
            [':comp' => 'COMPOSITE']
        );
    }

    public function evaluationResults(): array
    {
        return Connection::fetchAll(
            'SELECT a.title, u.last_name, u.first_name, aa.percentage, aa.score, aa.status, aa.completed_at
             FROM assessment_attempts aa
             JOIN assessments a ON a.id = aa.assessment_id
             JOIN users u ON u.id = aa.student_id
             ORDER BY aa.id DESC LIMIT 200'
        );
    }

    /**
     * Detalle de visualización de contenido por unidad:
     * devuelve la unidad, sus bloques y, por cada estudiante inscrito en las
     * secciones del Saber, el estado de visualización de cada bloque.
     */
    public function unitContentProgress(int $unitId): array
    {
        $unit = Connection::fetch(
            'SELECT u.id, u.title, u.saber_id, s.name AS saber_name
             FROM units u JOIN sabers s ON s.id = u.saber_id WHERE u.id = :id',
            [':id' => $unitId]
        );
        if ($unit === null) {
            throw new \App\Core\Exceptions\HttpException(404, 'Unidad no encontrada.');
        }

        $blocks = Connection::fetchAll(
            'SELECT id, type, title, position, status FROM content_blocks WHERE unit_id = :u ORDER BY position, id',
            [':u' => $unitId]
        );

        // Estudiantes inscritos en las secciones que tienen asignado este Saber.
        $students = Connection::fetchAll(
            'SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
             FROM users u
             JOIN section_students ss ON ss.student_id = u.id AND ss.status = :enr
             JOIN saber_assignments sa ON sa.section_id = ss.section_id
             WHERE sa.saber_id = :saber
             ORDER BY u.last_name, u.first_name',
            [':enr' => 'ENROLLED', ':saber' => (int) $unit['saber_id']]
        );

        foreach ($students as &$student) {
            $progress = Connection::fetchAll(
                'SELECT block_id, status, viewed_at, completed_at, video_percent
                 FROM student_block_progress WHERE student_id = :s',
                [':s' => (int) $student['id']]
            );
            $student['blocks'] = [];
            foreach ($progress as $p) {
                $student['blocks'][(int) $p['block_id']] = $p;
            }
        }

        return [
            'unit' => $unit,
            'blocks' => $blocks,
            'students' => $students,
        ];
    }
}
