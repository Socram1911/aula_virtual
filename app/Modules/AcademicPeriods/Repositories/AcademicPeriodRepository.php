<?php

declare(strict_types=1);

namespace App\Modules\AcademicPeriods\Repositories;

use App\Core\Database\Connection;
use App\Modules\AcademicPeriods\Models\AcademicPeriod;

final class AcademicPeriodRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll('SELECT * FROM academic_periods ORDER BY id DESC');
        return array_map([AcademicPeriod::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?AcademicPeriod
    {
        $row = Connection::fetch('SELECT * FROM academic_periods WHERE id = :id', [':id' => $id]);
        return $row === null ? null : AcademicPeriod::fromArray($row);
    }

    public function existsCode(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM academic_periods WHERE code = :code';
        $params = [':code' => $code];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        return (int) (Connection::fetch($sql, $params)['c'] ?? 0) > 0;
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO academic_periods (name, code, start_date, end_date, status) VALUES (:name, :code, :start_date, :end_date, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE academic_periods SET name = :name, code = :code, start_date = :start_date, end_date = :end_date, status = :status WHERE id = :id',
            $data
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        Connection::run('UPDATE academic_periods SET status = :status WHERE id = :id', [':status' => $status, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM academic_periods WHERE id = :id', [':id' => $id]);
    }
}
