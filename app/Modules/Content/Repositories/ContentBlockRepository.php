<?php

declare(strict_types=1);

namespace App\Modules\Content\Repositories;

use App\Core\Database\Connection;
use App\Modules\Content\Models\ContentBlock;

final class ContentBlockRepository
{
    public function blocks(int $unitId): array
    {
        $rows = Connection::fetchAll(
            'SELECT * FROM content_blocks WHERE unit_id = :uid ORDER BY position ASC, id ASC',
            [':uid' => $unitId]
        );
        return array_map([ContentBlock::class, 'fromArray'], $rows);
    }

    public function find(int $id): ?ContentBlock
    {
        $row = Connection::fetch('SELECT * FROM content_blocks WHERE id = :id', [':id' => $id]);
        return $row === null ? null : ContentBlock::fromArray($row);
    }

    public function nextPosition(int $unitId): int
    {
        $row = Connection::fetch('SELECT COALESCE(MAX(position), 0) AS m FROM content_blocks WHERE unit_id = :uid', [':uid' => $unitId]);
        return (int) ($row['m'] ?? 0) + 1;
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO content_blocks (unit_id, type, position, title, content, settings, status)
             VALUES (:unit_id, :type, :position, :title, :content, :settings, :status)',
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        Connection::run(
            'UPDATE content_blocks SET title = :title, content = :content, settings = :settings, status = :status WHERE id = :id',
            $data
        );
    }

    public function updatePosition(int $id, int $position): void
    {
        Connection::run('UPDATE content_blocks SET position = :p WHERE id = :id', [':p' => $position, ':id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        Connection::run('UPDATE content_blocks SET status = :s WHERE id = :id', [':s' => $status, ':id' => $id]);
    }

    public function delete(int $id): void
    {
        Connection::run('DELETE FROM content_blocks WHERE id = :id', [':id' => $id]);
    }
}
