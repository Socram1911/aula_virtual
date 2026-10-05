<?php

declare(strict_types=1);

namespace App\Modules\Files\Repositories;

use App\Core\Database\Connection;
use App\Modules\Files\Models\File;

final class FileRepository
{
    public function find(int $id): ?File
    {
        $row = Connection::fetch('SELECT * FROM files WHERE id = :id', [':id' => $id]);
        return $row === null ? null : File::fromArray($row);
    }

    public function create(array $data): int
    {
        return Connection::insert(
            'INSERT INTO files (original_name, stored_path, mime_type, size_bytes, uploader_id)
             VALUES (:original_name, :stored_path, :mime_type, :size_bytes, :uploader_id)',
            $data
        );
    }
}
