<?php

declare(strict_types=1);

namespace App\Modules\AcademicPeriods\Services;

use App\Core\Exceptions\HttpException;
use App\Modules\AcademicPeriods\Models\AcademicPeriod;
use App\Modules\AcademicPeriods\Repositories\AcademicPeriodRepository;
use App\Modules\Audit\Services\AuditService;

final class AcademicPeriodService
{
    private AcademicPeriodRepository $repository;

    public function __construct()
    {
        $this->repository = new AcademicPeriodRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): AcademicPeriod
    {
        $period = $this->repository->find($id);
        if ($period === null) {
            throw HttpException::notFound('Período académico no encontrado.');
        }
        return $period;
    }

    public function create(array $input): AcademicPeriod
    {
        $data = $this->validate($input);
        $id = $this->repository->create($data);
        AuditService::log('crear Período', 'AcademicPeriod', $id, null, $data);
        return $this->find($id);
    }

    public function update(int $id, array $input): AcademicPeriod
    {
        $before = $this->find($id)->toArray();
        $data = $this->validate($input, $id);
        $this->repository->update($id, $data);
        AuditService::log('modificar Período', 'AcademicPeriod', $id, $before, $data);
        return $this->find($id);
    }

    /** Transición de estado DRAFT → ACTIVE → CLOSED → ARCHIVED (solo hacia adelante). */
    public function transition(int $id, string $target): AcademicPeriod
    {
        $period = $this->find($id);
        $order = ['DRAFT' => 0, 'ACTIVE' => 1, 'CLOSED' => 2, 'ARCHIVED' => 3];
        if (! isset($order[$target]) || $order[$target] <= $order[$period->status]) {
            throw HttpException::unprocessable('Transición de estado inválida para el período.');
        }
        $this->repository->updateStatus($id, $target);
        AuditService::log('cambiar estado Período', 'AcademicPeriod', $id, ['status' => $period->status], ['status' => $target]);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $period = $this->find($id);
        $this->repository->delete($id);
        AuditService::log('eliminar Período', 'AcademicPeriod', $id, $period->toArray(), null);
    }

    private function validate(array $input, ?int $ignoreId = null): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));
        $start = (string) ($input['start_date'] ?? '');
        $end = (string) ($input['end_date'] ?? '');
        $status = (string) ($input['status'] ?? 'DRAFT');

        $errors = [];
        if ($name === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($code === '') {
            $errors[] = 'El código es obligatorio.';
        } elseif ($this->repository->existsCode($code, $ignoreId)) {
            $errors[] = 'El código ya está en uso.';
        }
        if ($start === '' || $end === '') {
            $errors[] = 'Las fechas de inicio y fin son obligatorias.';
        } elseif ($end < $start) {
            $errors[] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        }
        if (! in_array($status, AcademicPeriod::STATUSES, true)) {
            $errors[] = 'Estado inválido.';
        }

        if ($errors) {
            throw HttpException::unprocessable('Corrija los errores del formulario.', $errors);
        }

        return [
            ':name' => $name,
            ':code' => $code,
            ':start_date' => $start,
            ':end_date' => $end,
            ':status' => $status,
        ];
    }
}
