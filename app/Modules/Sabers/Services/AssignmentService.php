<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Services;

use App\Core\Database\Connection;
use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Sabers\Models\SaberAssignment;
use App\Modules\Sabers\Repositories\AssignmentRepository;

final class AssignmentService
{
    private AssignmentRepository $repository;

    public function __construct()
    {
        $this->repository = new AssignmentRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): SaberAssignment
    {
        $assignment = $this->repository->find($id);
        if ($assignment === null) {
            throw HttpException::notFound('Asignación no encontrada.');
        }
        return $assignment;
    }

    public function teachers(int $id): array
    {
        return $this->repository->teachers($id);
    }

    public function create(array $input): SaberAssignment
    {
        $data = $this->validate($input);
        $id = $this->repository->create($data);
        $this->syncTeachers($id, $input);
        AuditService::log('asignar Saber', 'SaberAssignment', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): SaberAssignment
    {
        $before = $this->find($id);
        $data = $this->validate($input, $id, $before);
        $this->repository->update($id, $data);
        $this->syncTeachers($id, $input);
        AuditService::log('modificar Asignación', 'SaberAssignment', $id, null, $data);
        return $this->find($id);
    }

    public function setStatus(int $id, string $status): SaberAssignment
    {
        $assignment = $this->find($id);
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'CLOSED'], true)) {
            throw HttpException::unprocessable('Estado de asignación inválido.');
        }
        $this->repository->updateStatus($id, $status);
        AuditService::log('publicar Asignación', 'SaberAssignment', $id, ['status' => $assignment->status], ['status' => $status]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $assignment = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Asignación', 'SaberAssignment', $id, null, null);
    }

    public function forStudent(int $studentId): array
    {
        return $this->repository->forStudent($studentId);
    }

    public function teacherHasSaber(int $teacherId, int $saberId): bool
    {
        return $this->repository->teacherHasSaber($teacherId, $saberId);
    }

    public function forTeacher(int $teacherId): array
    {
        return $this->repository->forTeacher($teacherId);
    }

    private function syncTeachers(int $assignmentId, array $input): void
    {
        $teachers = [];
        $teacherIds = $input['teacher_ids'] ?? [];
        $primaryId = (int) ($input['primary_teacher_id'] ?? 0);
        if (is_array($teacherIds)) {
            foreach (array_map('intval', $teacherIds) as $tid) {
                if ($tid > 0) {
                    $teachers[] = ['teacher_id' => $tid, 'is_primary' => $tid === $primaryId];
                }
            }
        }
        // Al menos un docente principal.
        if ($teachers !== [] && ! in_array(true, array_column($teachers, 'is_primary'), true)) {
            $teachers[0]['is_primary'] = true;
        }
        $this->repository->syncTeachers($assignmentId, $teachers);
    }

    private function validate(array $input, ?int $ignoreId = null, ?SaberAssignment $before = null): array
    {
        $saberId = (int) ($input['saber_id'] ?? ($before?->saber_id ?? 0));
        $sectionId = (int) ($input['section_id'] ?? ($before?->section_id ?? 0));
        $periodId = (int) ($input['academic_period_id'] ?? ($before?->academic_period_id ?? 0));
        $start = $input['start_date'] ?? '';
        $end = $input['end_date'] ?? '';
        $status = (string) ($input['status'] ?? 'DRAFT');

        $errors = [];
        if ($saberId <= 0 || $sectionId <= 0 || $periodId <= 0) {
            $errors[] = 'Saber, sección y período son obligatorios.';
        }
        if ($saberId > 0 && $sectionId > 0 && $periodId > 0 && $this->repository->exists($saberId, $sectionId, $periodId, $ignoreId)) {
            $errors[] = 'Ya existe una asignación de ese Saber a esa sección en ese período.';
        }
        if ($start !== '' && $end !== '' && $end < $start) {
            $errors[] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        }
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'CLOSED'], true)) {
            $errors[] = 'Estado inválido.';
        }
        // El trayecto de la sección debe coincidir con el trayecto del saber.
        if ($saberId > 0 && $sectionId > 0) {
            $saberTrack = Connection::fetch('SELECT track_id FROM sabers WHERE id = :id', [':id' => $saberId]);
            $sectionTrack = Connection::fetch('SELECT track_id FROM sections WHERE id = :id', [':id' => $sectionId]);
            if ($saberTrack && $sectionTrack && (int) $saberTrack['track_id'] !== (int) $sectionTrack['track_id']) {
                $errors[] = 'El Saber y la sección pertenecen a trayectos diferentes.';
            }
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':saber_id' => $saberId,
            ':section_id' => $sectionId,
            ':academic_period_id' => $periodId,
            ':start_date' => $start !== '' ? $start : null,
            ':end_date' => $end !== '' ? $end : null,
            ':status' => $status,
        ];
    }
}
