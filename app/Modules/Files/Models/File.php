<?php

declare(strict_types=1);

namespace App\Modules\Files\Models;

final class File
{
    public ?int $id = null;
    public string $original_name = '';
    public string $stored_path = '';
    public string $mime_type = '';
    public int $size_bytes = 0;
    public int $uploader_id = 0;

    public static function fromArray(array $row): self
    {
        $m = new self();
        $m->id = isset($row['id']) ? (int) $row['id'] : null;
        $m->original_name = (string) ($row['original_name'] ?? '');
        $m->stored_path = (string) ($row['stored_path'] ?? '');
        $m->mime_type = (string) ($row['mime_type'] ?? '');
        $m->size_bytes = (int) ($row['size_bytes'] ?? 0);
        $m->uploader_id = (int) ($row['uploader_id'] ?? 0);
        return $m;
    }
}
