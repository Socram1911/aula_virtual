<?php

declare(strict_types=1);

namespace App\Modules\Students\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Students\Repositories\EnrollmentRepository;

final class EnrollmentService
{
    private EnrollmentRepository $repository;

    public function __construct()
    {
        $this->repository = new EnrollmentRepository();
    }

    public function studentsForSection(int $sectionId): array
    {
        return $this->repository->studentsForSection($sectionId);
    }

    public function sectionsForStudent(int $studentId): array
    {
        return $this->repository->sectionsForStudent($studentId);
    }

    public function allStudents(): array
    {
        return $this->repository->allStudents();
    }

    public function enroll(int $sectionId, int $studentId, ?string $date = null): void
    {
        if ($this->repository->isEnrolled($sectionId, $studentId)) {
            throw HttpException::conflict('El estudiante ya está inscrito en esta sección.');
        }
        $enrollmentDate = $date ?: date('Y-m-d');
        $this->repository->enroll($sectionId, $studentId, $enrollmentDate);
        AuditService::log('inscribir Estudiante', 'SectionStudent', $sectionId, null, [
            'student_id' => $studentId,
            'enrollment_date' => $enrollmentDate,
        ]);
    }

    public function withdraw(int $sectionId, int $studentId): void
    {
        if (! $this->repository->isEnrolled($sectionId, $studentId)) {
            throw HttpException::notFound('El estudiante no está inscrito en esta sección.');
        }
        $this->repository->withdraw($sectionId, $studentId, date('Y-m-d'));
        AuditService::log('retirar Estudiante', 'SectionStudent', $sectionId, ['student_id' => $studentId], [
            'withdrawal_date' => date('Y-m-d'),
        ]);
    }
}
