<?php

declare(strict_types=1);

namespace App\Modules\Tracks\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Tracks\Models\Track;
use App\Modules\Tracks\Repositories\TrackRepository;

final class TrackService
{
    private TrackRepository $repository;

    public function __construct()
    {
        $this->repository = new TrackRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): Track
    {
        $track = $this->repository->find($id);
        if ($track === null) {
            throw HttpException::notFound('Trayecto no encontrado.');
        }
        return $track;
    }

    public function periodIds(int $id): array
    {
        return $this->repository->periodIds($id);
    }

    public function create(array $input): Track
    {
        $data = $this->validate($input);
        $id = $this->repository->create($data);
        $this->repository->syncPeriods($id, $this->normalizePeriods($input));
        AuditService::log('crear Trayecto', 'Track', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): Track
    {
        $before = $this->find($id)->toArray();
        $data = $this->validate($input, $id);
        $this->repository->update($id, $data);
        $this->repository->syncPeriods($id, $this->normalizePeriods($input));
        AuditService::log('modificar Trayecto', 'Track', $id, $before, $data);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $track = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Trayecto', 'Track', $id, $track->toArray(), null);
    }

    private function normalizePeriods(array $input): array
    {
        $periods = $input['period_ids'] ?? [];
        return is_array($periods) ? array_values(array_map('intval', $periods)) : [];
    }

    private function validate(array $input, ?int $ignoreId = null): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $position = (int) ($input['position'] ?? 0);
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
            ':position' => $position,
            ':status' => $status,
        ];
    }
}
