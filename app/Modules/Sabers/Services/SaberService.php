<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Sabers\Models\Saber;
use App\Modules\Sabers\Models\Unit;
use App\Modules\Sabers\Repositories\SaberRepository;

final class SaberService
{
    private SaberRepository $repository;

    public function __construct()
    {
        $this->repository = new SaberRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): Saber
    {
        $saber = $this->repository->find($id);
        if ($saber === null) {
            throw HttpException::notFound('Saber no encontrado.');
        }
        return $saber;
    }

    public function create(array $input): Saber
    {
        $data = $this->validateSaber($input);
        $id = $this->repository->create($data);
        AuditService::log('crear Saber', 'Saber', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): Saber
    {
        $before = $this->find($id)->toArray();
        $data = $this->validateSaber($input, $id);
        $this->repository->update($id, $data);
        AuditService::log('modificar Saber', 'Saber', $id, $before, $data);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $saber = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Saber', 'Saber', $id, $saber->toArray(), null);
    }

    // ===== Unidades =====

    public function units(int $saberId): array
    {
        $this->find($saberId);
        return $this->repository->units($saberId);
    }

    public function findUnit(int $id): Unit
    {
        $unit = $this->repository->findUnit($id);
        if ($unit === null) {
            throw HttpException::notFound('Unidad no encontrada.');
        }
        return $unit;
    }

    public function createUnit(int $saberId, array $input): Unit
    {
        $this->find($saberId);
        $data = $this->validateUnit($input, $saberId);
        $id = $this->repository->createUnit($data);
        AuditService::log('crear Unidad', 'Unit', $id, null, $data);
        return $this->findUnit($id);
    }

    public function updateUnit(int $id, array $input): Unit
    {
        $before = $this->findUnit($id)->toArray();
        $data = $this->validateUnit($input, $before['saber_id'], $id);
        $this->repository->updateUnit($id, $data);
        AuditService::log('modificar Unidad', 'Unit', $id, $before, $data);
        return $this->findUnit($id);
    }

    /** Publicar / despublicar una unidad. */
    public function setUnitStatus(int $id, string $status): Unit
    {
        $unit = $this->findUnit($id);
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'UNPUBLISHED'], true)) {
            throw HttpException::unprocessable('Estado de unidad inválido.');
        }
        $this->repository->updateUnitStatus($id, $status);
        AuditService::log('publicar Unidad', 'Unit', $id, ['status' => $unit->status], ['status' => $status]);
        return $this->findUnit($id);
    }

    public function deleteUnit(int $id): void
    {
        $unit = $this->findUnit($id);
        $this->repository->deleteUnit($id);
        AuditService::log('eliminar Unidad', 'Unit', $id, $unit->toArray(), null);
    }

    private function validateSaber(array $input, ?int $ignoreId = null): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $trackId = (int) ($input['track_id'] ?? 0);
        $unlockMode = (string) ($input['unlock_mode'] ?? 'FREE');
        $status = (string) ($input['status'] ?? 'ACTIVE');

        $errors = [];
        if ($code === '') {
            $errors[] = 'El código es obligatorio.';
        } elseif ($this->repository->existsCode($code, $ignoreId)) {
            $errors[] = 'El código ya está en uso.';
        }
        if ($name === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($trackId <= 0) {
            $errors[] = 'Seleccione un trayecto.';
        }
        if (! in_array($unlockMode, ['FREE', 'SEQUENTIAL'], true)) {
            $errors[] = 'Modo de desbloqueo inválido.';
        }
        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $errors[] = 'Estado inválido.';
        }
        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':code' => $code,
            ':name' => $name,
            ':description' => $description,
            ':track_id' => $trackId,
            ':unlock_mode' => $unlockMode,
            ':status' => $status,
        ];
    }

    private function validateUnit(array $input, int $saberId, ?int $ignoreId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $position = (int) ($input['position'] ?? 0);
        $status = (string) ($input['status'] ?? 'DRAFT');

        $errors = [];
        if ($title === '') {
            $errors[] = 'El título de la unidad es obligatorio.';
        }
        if (! in_array($status, ['DRAFT', 'PUBLISHED', 'UNPUBLISHED'], true)) {
            $errors[] = 'Estado de unidad inválido.';
        }
        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':saber_id' => $saberId,
            ':title' => $title,
            ':description' => $description,
            ':position' => $position,
            ':status' => $status,
        ];
    }
}
