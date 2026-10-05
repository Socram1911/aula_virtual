<?php

declare(strict_types=1);

namespace App\Modules\Tracks\Repositories;

use App\Core\Database\Connection;
use App\Modules\Tracks\Models\Track;

final class TrackRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll('SELECT * FROM tracks ORDER BY position ASC, id ASC');
        return array_map([Track::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?Track
    {
        $row = Connection::fetch('SELECT * FROM tracks WHERE id = :id', [':id' => $id]);
        return $row === null ? null : Track::fromArray($row);
    }

    public function existsCode(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM tracks WHERE code = :code';
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
            'INSERT INTO tracks (code, name, description, position, status) VALUES (:code, :name, :description, :position, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE tracks SET code = :code, name = :name, description = :description, position = :position, status = :status WHERE id = :id',
            $data
        );
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM tracks WHERE id = :id', [':id' => $id]);
    }

    /** Períodos asociados al trayecto. */
    public function periodIds(int $trackId): array
    {
        $rows = Connection::fetchAll(
            'SELECT academic_period_id FROM academic_period_tracks WHERE track_id = :tid',
            [':tid' => $trackId]
        );
        return array_map('intval', array_column($rows, 'academic_period_id'));
    }

    public function syncPeriods(int $trackId, array $periodIds): void
    {
        $pdo = Connection::pdo();
        $pdo->beginTransaction();
        try {
            Connection::run('DELETE FROM academic_period_tracks WHERE track_id = :tid', [':tid' => $trackId]);
            $stmt = $pdo->prepare(
                'INSERT INTO academic_period_tracks (academic_period_id, track_id, status) VALUES (?, ?, ?)'
            );
            foreach ($periodIds as $pid) {
                $stmt->execute([(int) $pid, $trackId, 'ACTIVE']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
