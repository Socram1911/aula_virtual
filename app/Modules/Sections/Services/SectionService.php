<?php

declare(strict_types=1);

namespace App\Modules\Sections\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Sections\Models\Section;
use App\Modules\Sections\Repositories\SectionRepository;

final class SectionService
{
    private SectionRepository $repository;

    public function __construct()
    {
        $this->repository = new SectionRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): Section
    {
        $section = $this->repository->find($id);
        if ($section === null) {
            throw HttpException::notFound('Sección no encontrada.');
        }
        return $section;
    }

    public function create(array $input): Section
    {
        $data = $this->validate($input);
        $id = $this->repository->create($data);
        AuditService::log('crear Sección', 'Section', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): Section
    {
        $before = $this->find($id)->toArray();
        $data = $this->validate($input, $id);
        $this->repository->update($id, $data);
        AuditService::log('modificar Sección', 'Section', $id, $before, $data);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $section = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Sección', 'Section', $id, $section->toArray(), null);
    }

    private function validate(array $input, ?int $ignoreId = null): array
    {
        $periodId = (int) ($input['academic_period_id'] ?? 0);
        $trackId = (int) ($input['track_id'] ?? 0);
        $number = (int) ($input['number'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $status = (string) ($input['status'] ?? 'ACTIVE');

        $errors = [];
        if ($periodId <= 0) {
            $errors[] = 'Seleccione un período académico.';
        }
        if ($trackId <= 0) {
            $errors[] = 'Seleccione un trayecto.';
        }
        if ($number <= 0) {
            $errors[] = 'El número de sección debe ser un entero positivo.';
        } elseif ($this->repository->existsNumber($periodId, $trackId, $number, $ignoreId)) {
            $errors[] = "Ya existe la Sección {$number} en ese período y trayecto.";
        }
        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $errors[] = 'Estado inválido.';
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':academic_period_id' => $periodId,
            ':track_id' => $trackId,
            ':number' => $number,
            ':name' => $name !== '' ? $name : 'Sección ' . $number,
            ':description' => $description,
            ':status' => $status,
        ];
    }
}
