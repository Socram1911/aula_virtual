<?php

declare(strict_types=1);

namespace App\Modules\Students\Repositories;

use App\Core\Database\Connection;

final class EnrollmentRepository
{
    /** Estudiantes inscritos en una sección (con datos de usuario). */
    public function studentsForSection(int $sectionId): array
    {
        return Connection::fetchAll(
            'SELECT ss.id AS enrollment_id, ss.enrollment_date, ss.withdrawal_date, ss.status AS enrollment_status,
                    u.id AS student_id, u.first_name, u.last_name, u.email
             FROM section_students ss
             JOIN users u ON u.id = ss.student_id
             WHERE ss.section_id = :sid
             ORDER BY u.last_name ASC',
            [':sid' => $sectionId]
        );
    }

    public function isEnrolled(int $sectionId, int $studentId): bool
    {
        return Connection::fetch(
            'SELECT id FROM section_students WHERE section_id = :s AND student_id = :st',
            [':s' => $sectionId, ':st' => $studentId]
        ) !== null;
    }

    public function enroll(int $sectionId, int $studentId, string $enrollmentDate): void
    {
        Connection::run(
            'INSERT INTO section_students (section_id, student_id, enrollment_date, status) VALUES (:s, :st, :d, :status)',
            [':s' => $sectionId, ':st' => $studentId, ':d' => $enrollmentDate, ':status' => 'ENROLLED']
        );
    }

    public function withdraw(int $sectionId, int $studentId, string $date): void
    {
        Connection::run(
            'UPDATE section_students SET status = :status, withdrawal_date = :d
             WHERE section_id = :s AND student_id = :st',
            [':status' => 'WITHDRAWN', ':d' => $date, ':s' => $sectionId, ':st' => $studentId]
        );
    }

    /** Secciones en las que está inscrito un estudiante (para el dashboard). */
    public function sectionsForStudent(int $studentId): array
    {
        return Connection::fetchAll(
            'SELECT ss.id AS enrollment_id, s.id AS section_id, s.number, s.name,
                    p.name AS period_name, t.name AS track_name
             FROM section_students ss
             JOIN sections s ON s.id = ss.section_id
             JOIN academic_periods p ON p.id = s.academic_period_id
             JOIN tracks t ON t.id = s.track_id
             WHERE ss.student_id = :st AND ss.status = :status
             ORDER BY p.id DESC, t.position ASC',
            [':st' => $studentId, ':status' => 'ENROLLED']
        );
    }

    /** Todos los estudiantes (usuarios con rol STUDENT) y sus inscripciones. */
    public function allStudents(): array
    {
        return Connection::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.status,
                    COUNT(ss.id) AS enrollments
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id AND r.name = :role
             LEFT JOIN section_students ss ON ss.student_id = u.id AND ss.status = :enrolled
             GROUP BY u.id
             ORDER BY u.last_name ASC',
            [':role' => 'STUDENT', ':enrolled' => 'ENROLLED']
        );
    }
}
