<?php

declare(strict_types=1);

namespace App\Modules\Sections\Repositories;

use App\Core\Database\Connection;
use App\Modules\Sections\Models\Section;

final class SectionRepository
{
    public function all(): array
    {
        $rows = Connection::fetchAll(
            'SELECT s.*, p.name AS period_name, t.name AS track_name
             FROM sections s
             JOIN academic_periods p ON p.id = s.academic_period_id
             JOIN tracks t ON t.id = s.track_id
             ORDER BY p.id DESC, t.position ASC, s.number ASC'
        );
        return array_map([Section::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?Section
    {
        $row = Connection::fetch(
            'SELECT s.*, p.name AS period_name, t.name AS track_name
             FROM sections s
             JOIN academic_periods p ON p.id = s.academic_period_id
             JOIN tracks t ON t.id = s.track_id
             WHERE s.id = :id',
            [':id' => $id]
        );
        return $row === null ? null : Section::fromArray($row);
    }

    public function existsNumber(int $periodId, int $trackId, int $number, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM sections WHERE academic_period_id = :p AND track_id = :t AND number = :n';
        $params = [':p' => $periodId, ':t' => $trackId, ':n' => $number];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        return (int) (Connection::fetch($sql, $params)['c'] ?? 0) > 0;
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO sections (academic_period_id, track_id, number, name, description, status)
             VALUES (:academic_period_id, :track_id, :number, :name, :description, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE sections SET academic_period_id = :academic_period_id, track_id = :track_id, number = :number,
             name = :name, description = :description, status = :status WHERE id = :id',
            $data
        );
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM sections WHERE id = :id', [':id' => $id]);
    }
}
