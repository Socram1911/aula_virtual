<?php

declare(strict_types=1);

namespace App\Modules\Sabers\Repositories;

use App\Core\Database\Connection;
use App\Modules\Sabers\Models\Saber;
use App\Modules\Sabers\Models\Unit;

final class SaberRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll(
            'SELECT s.*, t.name AS track_name
             FROM sabers s JOIN tracks t ON t.id = s.track_id
             ORDER BY s.id DESC'
        );
        return array_map([Saber::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?Saber
    {
        $row = Connection::fetch(
            'SELECT s.*, t.name AS track_name FROM sabers s JOIN tracks t ON t.id = s.track_id WHERE s.id = :id',
            [':id' => $id]
        );
        return $row === null ? null : Saber::fromArray($row);
    }

    public function existsCode(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM sabers WHERE code = :code';
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
            'INSERT INTO sabers (code, name, description, track_id, unlock_mode, status)
             VALUES (:code, :name, :description, :track_id, :unlock_mode, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE sabers SET code = :code, name = :name, description = :description, track_id = :track_id,
             unlock_mode = :unlock_mode, status = :status WHERE id = :id',
            $data
        );
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM sabers WHERE id = :id', [':id' => $id]);
    }

    // ===== Unidades =====

    public function units(int $saberId): array
    {
        $rows = Connection::fetchAll(
            'SELECT * FROM units WHERE saber_id = :sid ORDER BY position ASC, id ASC',
            [':sid' => $saberId]
        );
        return array_map([Unit::class, 'fromArray'], $rows);
    }

    public function findUnit(int $id): ?Unit
    {
        $row = Connection::fetch('SELECT * FROM units WHERE id = :id', [':id' => $id]);
        return $row === null ? null : Unit::fromArray($row);
    }

    public function createUnit(array $data): int
    {
        return Connection::insert(
            'INSERT INTO units (saber_id, title, description, position, status) VALUES (:saber_id, :title, :description, :position, :status)',
            $data
        );
    }

    public function updateUnit(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE units SET title = :title, description = :description, position = :position, status = :status WHERE id = :id',
            $data
        );
    }

    public function updateUnitStatus(int $id, string $status): void
    {
        Connection::run('UPDATE units SET status = :status WHERE id = :id', [':status' => $status, ':id' => $id]);
    }

    public function deleteUnit(int $id): void
    {
        Connection::run('DELETE FROM units WHERE id = :id', [':id' => $id]);
    }
}
